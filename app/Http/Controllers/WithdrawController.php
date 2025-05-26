<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Transaction;
use App\Models\Withdraw;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Log;

class WithdrawController extends Controller
{
     public function initiateWithdrawal(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'email' => 'required|email',
            'amount' => 'required|numeric|min:1',
            'bank_name' => 'required|string',
            'account_number' => 'required|string',
            'account_name' => 'required|string',
            'withdraw_from' => 'required|in:balance,referral_bonus',
        ]);
           Log::info('Withdraw From:', ['value' => $request->input('withdraw_from')]);
           $withdrawFrom = $request->input('withdraw_from');
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::find($request->user_id);

        if ($user->email !== $request->email) {
            return response()->json(['message' => 'Email does not match our records.'], 400);
        }

        if ( $withdrawFrom  === 'balance' && $user->balance < $request->amount) {
            return response()->json(['message' => 'Insufficient balance.'], 400);
        }

        if ( $withdrawFrom  === 'referral_bonus' && $user->referral_bonus < $request->amount) {
            return response()->json(['message' => 'Insufficient referral bonus.'], 400);
        }

        $banksResponse = Http::withToken(config('services.paystack.secretKey'))
            ->get('https://api.paystack.co/bank');

        if (!$banksResponse->successful()) {
            return response()->json(['message' => 'Unable to fetch bank list. Try again.'], 500);
        }

        $banks = $banksResponse->json()['data'];
        $matchedBank = collect($banks)->firstWhere('name', $request->bank_name);

        if (!$matchedBank) {
            return response()->json(['message' => 'Invalid bank name provided.'], 400);
        }

        $bankCode = $matchedBank['code'];
        $refNo = 'WD-' . strtoupper(Str::random(10));

        $transaction = Transaction::create([
            'user_id' => $user->id,
            'amount' => $request->amount,
            'transaction_type' => 'withdraw',
            'status' => 'pending',
            'ref_no' => $refNo,
            'meta' => json_encode(['withdraw_from' => $request->withdraw_from])
        ]);

        $withdraw = Withdraw::create([
            'transaction_id' => $transaction->id,
            'bank_name' => $request->bank_name,
            'account_name' => $request->account_name,
            'account_number' => $request->account_number,
            'status' => 'pending',
        ]);

        logActivity('Withdrawal', 'User placed a withdrawal request');

        return response()->json([
            'message' => 'Withdrawal request initiated. Awaiting confirmation.',
            'reference' => $refNo,
            'bank_code' => $bankCode,
            'transaction' => $transaction,
            'withdraw' => $withdraw
        ]);
    }

    public function confirmWithdrawal(Request $request)
{
    $request->validate([
        'ref_no' => 'required|string|exists:transactions,ref_no',
        'bank_code' => 'required|string',
    ]);

    $transaction = Transaction::where('ref_no', $request->ref_no)
        ->where('transaction_type', 'withdraw')
        ->first();

    if (!$transaction || $transaction->status !== 'pending') {
        return response()->json(['message' => 'Invalid or already processed transaction.'], 400);
    }

    $user = User::find($transaction->user_id);
    $meta = json_decode($transaction->meta, true);
    $withdrawFrom = $meta['withdraw_from'] ?? 'balance'; 

    Log::info('Withdraw From:', ['value' => $withdrawFrom]);

    if (
        ($withdrawFrom === 'balance' && $user->balance < $transaction->amount) ||
        ($withdrawFrom === 'referral_bonus' && $user->referral_bonus < $transaction->amount)
    ) {
        return response()->json(['message' => 'Insufficient funds.'], 400);
    }

    $withdraw = Withdraw::where('transaction_id', $transaction->id)->first();

    $recipientResponse = Http::withToken( config('services.paystack.secretKey'))->post(
        'https://api.paystack.co/transferrecipient',
        [
            'type' => 'nuban',
            'name' => $withdraw->account_name,
            'account_number' => $withdraw->account_number,
            'bank_code' => $request->bank_code,
            'currency' => 'NGN',
        ]
    );

    if (!$recipientResponse->successful()) {
        return response()->json(['message' => 'Failed to create transfer recipient.'], 500);
    }

    $recipientCode = $recipientResponse->json()['data']['recipient_code'];

    $transferResponse = Http::withToken(config('services.paystack.secretKey'))->post(
        'https://api.paystack.co/transfer',
        [
            'source' => 'balance',
            'amount' => $transaction->amount * 100,
            'recipient' => $recipientCode,
            'reason' => 'User Withdrawal',
            'reference' => $transaction->ref_no,
        ]
    );

    if (!$transferResponse->successful()) {
        return response()->json(['message' => 'Transfer failed.'], 500);
    }

    DB::transaction(function () use ($transaction, $user, $withdraw, $withdrawFrom) {
        $transaction->status = 'successful';
        $transaction->save();

        if ($withdrawFrom === 'referral_bonus') {
            $user->referral_bonus -= $transaction->amount;
            Log::info("Deducted from referral_bonus: {$transaction->amount}");
        } elseif ($withdrawFrom === 'balance') {
            $user->balance -= $transaction->amount;
            Log::info("Deducted from balance: {$transaction->amount}");
        } else {
            Log::error("Unexpected withdraw_from value: " . $withdrawFrom);
        }

        $user->save();

        $withdraw->status = 'confirmed';
        $withdraw->save();
    });

    logActivity('Withdrawal_verification', 'User withdrawal successfully confirmed and disbursed');

    return response()->json([
        'message' => 'Withdrawal confirmed and processed.',
        'transaction' => $transaction,
        'withdraw' => $withdraw
    ]);
}

    
    
    public function getUserActivityLogs($userId)
    {
        $logs = ActivityLog::where('user_id', $userId)
                    ->orderBy('created_at', 'desc')
                    ->get();
    
        if ($logs->isEmpty()) {
            return response()->json(['message' => 'No activity logs found for this user'], 404);
        }
    
        return response()->json([
            'user_id' => $userId,
            'logs' => $logs,
        ]);
    }
}
