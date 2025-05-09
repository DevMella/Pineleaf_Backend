<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\User;
use App\Models\Transaction;
use App\Models\Payment;
use App\Models\Referral;
use App\Models\Installment;
use Illuminate\Support\Facades\DB;

class ManualController extends Controller
{
    public function manualVerify(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);
    
        $user = User::find($request->user_id);
    
        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }
    
        if ($user->enabled) {
            return response()->json(['message' => 'User already activated.'], 400);
        }
    
        $transaction = Transaction::where('user_id', $user->id)
            ->where('transaction_type', 'registration')
            ->where('status', 'pending')
            ->latest()
            ->first();
    
        if (!$transaction) {
            return response()->json(['message' => 'No pending transaction found for this user.'], 404);
        }
    
        $user->enabled = true;
        $user->save();
    
        $transaction->status = 'success';
        $transaction->save();
    
        Payment::updateOrCreate(
            ['transaction_id' => $transaction->id],
            [
                'user_id' => $user->id,
                'ref_no' => $transaction->ref_no,
                'transaction_id' => $transaction->id,
            ]
        );
    
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
    
        logActivity('manual_verification', 'User manually verified and activated by admin');
    
        return response()->json([
            'message' => 'User manually verified and activated successfully.',
            'user' => $user,
            'transaction' => $transaction,
        ], 200);
    }
    public function confirmManualPayment(Request $request)
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

        $transaction->status = 'successful';
        $transaction->save();

        Payment::create([
            'user_id' => $transaction->user_id,
            'ref_no' => $transaction->ref_no,
            'transaction_id' => $transaction->id,
        ]);

        $user = User::find($transaction->user_id);
        $amount = $transaction->amount;

        $user->balance += $amount * 0.10;
        $user->save();

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

        logActivity('manual_verification', 'Manual payment verified successfully');

        return response()->json([
            'message' => 'Manual payment confirmed and commissions awarded successfully',
            'transaction' => $transaction
        ]);
    }
    public function confirmInstallmentPayment(Request $request){
        $request->validate([
            'ref_no' => 'required|string',
        ]);
    
        $transaction = Transaction::where('ref_no', $request->ref_no)->first();
    
        if (!$transaction) {
            return response()->json(['message' => 'Transaction not found'], 404);
        }
    
        if ($transaction->status !== 'pending') {
            return response()->json(['message' => 'Transaction already confirmed.'], 400);
        }
    
        $installment = Installment::where('transaction_id', $transaction->id)->first();
        if (!$installment) {
            return response()->json(['message' => 'Installment record not found.'], 404);
        }
    
        $installment->paid_count++;
    
        if ($installment->paid_count == 1) {
            $startDate = now();
            $endDate = $startDate->copy()->addMonth();
    
            $installment->start_date = $startDate;
            $installment->end_date = $endDate;
        }
    
        $installment->save();
    
        $transaction->status = 'successful';
        $transaction->save();
    
        Payment::create([
            'user_id' => $transaction->user_id,
            'transaction_id' => $transaction->id,
            'amount' => $transaction->amount,
            'ref_no' => $transaction->ref_no,
            'payment_date' => now(),
            'status' => 'confirmed',
        ]);
    
        $user = User::find($transaction->user_id);
        $amount = $transaction->amount;
        $user->balance += $amount * 0.10;
        $user->save();
    
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
    
        logActivity('manual_installment_verification', 'Manual installment payment verified successfully.');
    
        return response()->json([
            'message' => 'Installment payment confirmed and commissions awarded successfully.',
            'transaction' => $transaction,
        ]);
    }
}
