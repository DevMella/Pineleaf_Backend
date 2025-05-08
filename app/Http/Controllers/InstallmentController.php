<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Installment;
use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InstallmentController extends Controller
{
    public function handleInstallment(Request $request)
    {
        Log::info($request->all()); 
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'email' => 'required|email|exists:users,email',
            'payment_method' => 'required|in:manual,paystack',
            'amount' => 'required|numeric|min:1',
            'units' => 'required|numeric|min:1',
            'property_purchased_id' => 'required|exists:properties,id',
            'installment_count' => 'required|in:1,2,3',
            'payment_proof' => 'required_if:payment_method,manual|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);
    
        $refNo = uniqid('TXN-');
    
        $transactionData = [
            'user_id' => $request->user_id,
            'email' => $request->email,
            'amount' => $request->amount,
            'units' => $request->units,
            'transaction_type' => 'installment_purchase',
            'ref_no' => $refNo,
            'property_purchased_id' => $request->property_purchased_id,
            'status' => 'pending', 
        ];
    
        if ($request->payment_method === 'manual') {
            $proofPath = $request->file('payment_proof')->store('payment_proofs', 'public');
            $transactionData['proof_of_payment'] = $proofPath;
    
            $transaction = Transaction::create($transactionData);
    
            Installment::create([
                'user_id' => $request->user_id,
                'transaction_id' => $transaction->id,
                'start_date' => null,
                'end_date' => null,
                'installment_count' => $request->installment_count,
                'paid_count' => 0,
            ]);
    
            return response()->json([
                'message' => 'Manual payment submitted successfully. Pending verification.',
                'transaction' => $transaction,
                'reference' => $refNo,
            ], 201);
        }
    
        $paystackResponse = Http::withToken(env('PAYSTACK_SECRET_KEY'))->post(
            'https://api.paystack.co/transaction/initialize',
            [
                'email' => $request->email,
                'amount' => $request->amount * 100, 
                'reference' => $refNo,
                'callback_url' => url('/paystack/callback'),
            ]
        );
    
        if (!$paystackResponse->successful()) {
            return response()->json(['message' => 'Failed to initialize Paystack transaction.'], 500);
        }
    
        $responseData = $paystackResponse->json()['data'];
    
        $transactionData['ref_no'] = $responseData['reference'];
        $transaction = Transaction::create($transactionData);
    
        Installment::create([
            'user_id' => $request->user_id,
            'transaction_id' => $transaction->id,
            'start_date' => null,
            'end_date' => null,
            'installment_count' => $request->installment_count,
            'paid_count' => 0,
        ]);
    
        return response()->json([
            'message' => 'Paystack payment initialized successfully.',
            'transaction' => $transaction,
            'payment_url' => $responseData['authorization_url'],
            'reference' => $responseData['reference'],
        ], 201);
    }
    
}
