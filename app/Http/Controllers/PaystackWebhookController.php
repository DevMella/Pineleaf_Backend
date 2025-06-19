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
use App\Mail\InstallmentSuccessMail;

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
                Mail::to($user->email)->send(new PurchaseSuccessMail($transaction, $firstName));
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
    $propertyId = $transaction->property_purchased_id;
    $amount = $transaction->amount;
    $installmentCount = (int) $transaction->installment_count;

    // 1. Create the Installment record
    $installment = Installment::create([
        'user_id' => $userId,
        'property_purchased_id' => $propertyId,
        'transaction_id' => $transaction->id,
        'installment_count' => $installmentCount,
        'paid_count' => 1, // Always 1 for each transaction
        'start_date' => now(),
        'end_date' => now()->addMonths($installmentCount),
        'client_name'=>$transaction->client_name,
        'amount'=>$transaction->amount,
    ]);


    // 3. Update unit_sold on the property
    if ($transaction->units > 0) {
        $property = Property::find($propertyId);
        if ($property) {
            $property->unit_sold += $transaction->units;
            $property->save();
        }
    }

    // 4. Save payment record
    Payment::create([
        'user_id' => $userId,
        'ref_no' => $transaction->ref_no,
        'transaction_id' => $transaction->id,
    ]);

    // 5. Give 10% commission to the user
    $user = User::find($userId);
    if ($user) {
        $user->balance += $amount * 0.10;
        $user->save();
         $firstName = explode(' ', $user->fullName)[0];
        Mail::to($user->email)->send(new InstallmentSuccessMail($transaction, $firstName));
    }

    // 6. Referral bonuses (if referral exists)
    $referrals = Referral::where('referee_id', $userId)->get();
    foreach ($referrals as $referral) {
        $bonus = 0;
        if ($referral->level == 1) {
            $bonus = $amount * 0.04;
        } elseif ($referral->level == 2) {
            $bonus = $amount * 0.02;
        }

        $referral->bonus += $bonus;
        $referral->save();
    }

    // 7. Update total referral_bonus for each referrer
    $referralBonuses = DB::table('referrals')
        ->select('referral_id', DB::raw('SUM(bonus) as total_bonus'))
        ->groupBy('referral_id')
        ->get();

    foreach ($referralBonuses as $bonusData) {
        DB::table('users')
            ->where('id', $bonusData->referral_id)
            ->update(['referral_bonus' => $bonusData->total_bonus]);
    }

    // 8. Log the action
    logActivity('installment_purchase', 'Installment recorded, transaction marked successful, and bonuses calculated.');

    return response()->json(['message' => 'Installment purchase processed successfully.'], 200);
}

elseif ($transaction->transaction_type === 'continue_installment') {
    Log::info('Transaction Data:', $transaction->toArray());

    $userId = $transaction->user_id;
    $amount = $transaction->amount;

    // Get parent transaction ID
    $parentId = $transaction->parent_transaction_id;

    // Use parent ID to get the installment record
    $installment = Installment::where('transaction_id', $parentId)->first();
    if (!$installment) {
        Log::warning("Installment record not found for parent transaction_id: " . $parentId);
        return response()->json(['message' => 'Installment not found.'], 404);
    }

    // Already completed?
    if ($installment->paid_count >= $installment->installment_count) {
        Log::info("Installment already completed for transaction_id: {$parentId}");
        return response()->json(['message' => 'Installment already completed.'], 200);
    }

    // ✅ Increase paid count
    $installment->paid_count += 1;
    $installment->save();

    // ✅ Save payment
    Payment::create([
        'user_id' => $userId,
        'ref_no' => $transaction->ref_no,
        'transaction_id' => $transaction->id,
    ]);

    // ✅ Give direct commission (10%)
    $user = User::find($userId);
    if ($user) {
        $user->balance += $amount * 0.10;
        $user->save();
         $firstName = explode(' ', $user->fullName)[0];
                Mail::to($user->email)->send(new PurchaseSuccessMail($transaction, $firstName));
    }

    // ✅ Referral bonuses
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

    // ✅ Update user's total referral bonus
    $referralBonuses = DB::table('referrals')
        ->select('referral_id', DB::raw('SUM(bonus) as total_bonus'))
        ->groupBy('referral_id')
        ->get();

    foreach ($referralBonuses as $bonusData) {
        DB::table('users')
            ->where('id', $bonusData->referral_id)
            ->update(['referral_bonus' => $bonusData->total_bonus]);
    }

    // ✅ Update transaction status
    $transaction->status = 'success';
    $transaction->save();

    // ✅ Log activity
    logActivity('continue_installment', "Installment continued using parent transaction_id {$parentId}. Paid count updated.");

    return response()->json(['message' => 'Installment payment processed successfully.'], 200);
}



        }
        return response()->json(['message' => 'Event not handled'], 200);
    }
}
