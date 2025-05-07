<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\User;
use App\Models\Transaction;
use App\Models\Payment;
use App\Models\Referral;

class PaystackController extends Controller
{
    public function verify(Request $request)
    {
        $reference = $request->reference;
    
        $response = Http::withToken(env('PAYSTACK_SECRET_KEY'))
            ->get("https://api.paystack.co/transaction/verify/{$reference}");
    
        if (!$response->successful()) {
            return response()->json(['message' => 'Unable to verify transaction.'], 500);
        }
    
        $data = $response->json()['data'];
    
        if ($data['status'] === 'success') {
            $email = $data['customer']['email'];
            $amount = $data['amount'] / 100;
            $ref_no = $data['reference'];
    
            $user = User::where('email', $email)->first();
    
            if (!$user) {
    
                return response()->json(['message' => 'User not found.'], 404);
            }
    
            if ($user->enabled) {
                return response()->json(['message' => 'User already activated.'], 400);
            }
            $user->enabled = true;
            $user->save();
            $transaction = Transaction::create([
                'user_id' => $user->id,
                'amount' => $amount,
                'transaction_type' => 'registration',
                'ref_no' => $ref_no,
                'status' => 'success',
                'proof_of_payment' => $ref_no,
            ]);
    
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
    
            return response()->json([
                'message' => 'User activated and payment confirmed.',
                'user' => $user,
                'transaction' => $transaction
            ], 200);
        }
    
        return response()->json(['message' => 'Payment not successful.'], 400);
    }

    public function confirmPayment(Request $request)
    {
        $request->validate([
            'ref_no' => 'required|string'
        ]);

        $transaction = Transaction::where('ref_no', $request->ref_no)->first();

        if (!$transaction) {
            return response()->json(['message' => 'Transaction not found'], 404);
        }

        if ($transaction->status === 'successful') {
            return response()->json(['message' => 'Payment already confirmed.']);
        }

        if (str_contains($transaction->ref_no, 'PAYSTACK')) {
            $response = Http::withToken(env('PAYSTACK_SECRET_KEY'))->get(
                "https://api.paystack.co/transaction/verify/{$transaction->ref_no}"
            );

            if (!$response->successful()) {
                return response()->json(['message' => 'Failed to verify Paystack payment'], 500);
            }

            $paystackData = $response->json()['data'];
            if ($paystackData['status'] !== 'success') {
                return response()->json(['message' => 'Payment not completed yet'], 400);
            }
        }

        $transaction->status = 'successful';
        $transaction->save();

        Payment::create([
            'user_id' => $transaction->user_id,
            'ref_no' => $transaction->ref_no,
            'transaction_id' => $transaction->id,
        ]);

        return response()->json([
            'message' => 'Payment confirmed successfully',
            'transaction' => $transaction
        ]);
    }

}
