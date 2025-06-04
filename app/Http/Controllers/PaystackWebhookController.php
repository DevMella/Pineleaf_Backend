<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Transaction;
use App\Models\Referral;
use App\Models\Property;
use App\Models\Installment;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\RegistrationSuccessMail;
use App\Mail\PurchaseSuccessMail;

class PaystackWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // Verify Paystack signature
        $secret = env('PAYSTACK_SECRET_KEY');
        $signature = $request->header('X-Paystack-Signature');

        if (!$signature || !hash_equals($signature, hash_hmac('sha512', $request->getContent(), $secret))) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $payload = $request->all();

        // Handle charge.success
        if ($payload['event'] === 'charge.success') {
            $ref_no = $payload['data']['reference'];
            $amount = $payload['data']['amount'] / 100;

            $transaction = Transaction::where('ref_no', $ref_no)->first();

            if (!$transaction) {
                Log::error("Transaction not found for reference: " . $ref_no);
                return response()->json(['message' => 'Transaction not found'], 404);
            }

            if ($transaction->status === 'success') {
                return response()->json(['message' => 'Transaction already processed.'], 200);
            }

            $user = User::find($transaction->user_id);
            if (!$user) {
                Log::error("User not found for transaction reference: " . $ref_no);
                return response()->json(['message' => 'User not found'], 404);
            }

            $transaction->status = 'success';
            $transaction->save();

            // Registration payment
            if ($transaction->transaction_type === 'registration') {
                if (!$user->enabled) {
                    $user->enabled = true;
                    $user->save();
                    Mail::to($user->email)->send(new RegistrationSuccessMail($user));

                }

                Payment::create([
                    'user_id' => $user->id,
                    'ref_no' => $ref_no,
                    'transaction_id' => $transaction->id,
                ]);

                if ($user->referral_code) {
                    $referrer = User::where('my_referral_code', $user->referral_code)->first();
                    $level = 1;

                    $currentReferrer = $referrer;
                    while ($currentReferrer && $level <= 2) {
                        Referral::firstOrCreate([
                            'referral_id' => $currentReferrer->id,
                            'referee_id' => $user->id,
                            'level' => $level,
                        ]);

                        $currentReferrer = User::where('my_referral_code', $currentReferrer->referral_code)->first();
                        $level++;
                    }
                }

                logActivity('registration_verification', 'User registration payment is verified successfully');
                return response()->json(['message' => 'User activated and registration payment confirmed.'], 200);
            }

            // Land/manual purchase
            elseif ($transaction->transaction_type === 'purchase') {
                $property = Property::find($transaction->property_purchased_id);
                if ($property) {
                    $property->increment('unit_sold', $transaction->units);
                } else {
                    Log::warning("Property not found for purchase transaction ref: " . $ref_no);
                }

                Payment::create([
                    'user_id' => $transaction->user_id,
                    'ref_no' => $transaction->ref_no,
                    'transaction_id' => $transaction->id,
                ]);

                $amount = $transaction->amount;
                $user->balance += $amount * 0.10;
                $user->save();
                $firstName = explode(' ', $user->fullName)[0];
                Mail::to($user->email)->send(new \App\Mail\PurchaseSuccessMail($firstName, $amount, $ref_no));
                // Referral bonuses
                $referrals = Referral::where('referee_id', $user->id)->get();

                foreach ($referrals as $referral) {
                    if ($referral->level == 1) {
                        $bonus = $amount * 0.04;
                    } elseif ($referral->level == 2) {
                        $bonus = $amount * 0.02;
                    } else {
                        continue;
                    }

                    $referral->bonus += $bonus;
                    $referral->save();
                }

                $referralBonuses = DB::table('referrals')
                    ->select('referral_id', DB::raw('SUM(bonus) as total_bonus'))
                    ->groupBy('referral_id')
                    ->get();

                foreach ($referralBonuses as $bonusData) {
                    DB::table('users')
                        ->where('id', $bonusData->referral_id)
                        ->update(['referral_bonus' => $bonusData->total_bonus]);
                }

                logActivity('land_verification', 'User land/manual purchase payment is verified successfully');
                return response()->json(['message' => 'Purchase confirmed and commissions awarded.'], 200);
            }
            elseif ($transaction->transaction_type === 'installment_purchase') {
    $userId = $transaction->user_id;
    $propertyId = $transaction->prxoperty_purchased_id;
    $amount = $transaction->amount;

    // Check for ongoing installment
    $installment = Installment::where('user_id', $userId)
        ->where('property_purchased_id', $propertyId)
        ->whereColumn('paid_count', '<', 'installment_count')
        ->orderByDesc('created_at')
        ->first();

    if ($installment) {
        // Continuing an existing installment
        $installment->paid_count += 1;
        $installment->save();

        Log::info("Installment payment continued. User: $userId, Property: $propertyId, Paid Count: {$installment->paid_count}/{$installment->installment_count}");
    } else {
        // Start a new installment cycle
        $installment = new Installment();
        $installment->user_id = $userId;
        $installment->property_purchased_id = $propertyId;
        $installment->transaction_id = $transaction->id;
        $installment->installment_count = $transaction->installment_count ?? 3; // Default to 3 if not provided
        $installment->paid_count = 1;
        $installment->save();

        // Increase unit_sold only for new installment cycle
        $property = Property::find($propertyId);
        if ($property) {
            $property->unit_sold += $transaction->units;
            $property->save();
        } else {
            Log::warning("Property not found for ID $propertyId (Transaction Ref: {$transaction->ref_no})");
        }

        Log::info("New installment cycle started. User: $userId, Property: $propertyId");
    }

    // Save the payment record once
    Payment::create([
        'user_id' => $userId,
        'ref_no' => $transaction->ref_no,
        'transaction_id' => $transaction->id,
    ]);

    // Commission (10%) for user (add only once)
    $user->balance += $amount * 0.10;
    $user->save();

    // Referral bonuses
    $referrals = Referral::where('referee_id', $userId)->get();
    foreach ($referrals as $referral) {
        $bonus = 0;
        if ($referral->level == 1) {
            $bonus = $amount * 0.04;
        } elseif ($referral->level == 2) {
            $bonus = $amount * 0.02;
        }

        if ($bonus > 0) {
            $referral->bonus += $bonus;
            $referral->save();
        }
    }

    // Update total bonuses for each referrer
    $referralBonuses = DB::table('referrals')
        ->select('referral_id', DB::raw('SUM(bonus) as total_bonus'))
        ->groupBy('referral_id')
        ->get();

    foreach ($referralBonuses as $bonusData) {
        DB::table('users')
            ->where('id', $bonusData->referral_id)
            ->update(['referral_bonus' => $bonusData->total_bonus]);
    }

    logActivity('installment_verification', 'User installment payment verified and commission awarded.');

    return response()->json(['message' => 'Installment payment confirmed. Commission and bonuses awarded.'], 200);
}


        }



        return response()->json(['message' => 'Event not handled'], 200);
    }
}
