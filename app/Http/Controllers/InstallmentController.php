<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Installment;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class InstallmentController extends Controller
{
    public function handleInstallment(Request $request)
    {
        Log::info('Installment request received:', $request->all());

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'email' => 'required|email|exists:users,email',
            'client_name'=>'required|string',
            'payment_method' => 'required|in:manual,paystack',
            'amount' => 'required|numeric|min:1',
            'units' => 'required|numeric|min:1',
            'property_purchased_id' => 'required|exists:properties,id',
            'installment_count' => 'required|in:1,2,3',
            'payment_proof' => 'required_if:payment_method,manual|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        $now = now();
        $refNo = uniqid('TXN-');

        $transactionData = [
            'user_id' => $request->user_id,
            'email' => $request->email,
            'amount' => $request->amount,
            'units' => $request->units,
            'transaction_type' => 'installment_purchase',
            'ref_no' => $refNo,
            'client_name'=> $request->client_name,
            'property_purchased_id' => $request->property_purchased_id,
            'status' => 'pending',
            'installment_count'=>$request->installment_count,
        ];

        if ($request->payment_method === 'manual') {
            // Handle manual payment proof upload
            $proofPath = $request->file('payment_proof')->store('payment_proofs', 'public');
            $transactionData['proof_of_payment'] = $proofPath;

            $transaction = Transaction::create($transactionData);

                Installment::create([
                    'user_id' => $request->user_id,
                    'transaction_id' => $transaction->id,
                    'property_purchased_id' => $request->property_purchased_id,
                    'start_date' => $now,
                    'end_date' => $now->copy()->addMonth(),
                    'installment_count' => $request->installment_count,
                    'paid_count' => 0,
                    'amount'=> $request->amount,
                    'client_name'=> $request->client_name,
                ]);

            logActivity('installment_payment', 'User purchased land with manual installment payment');

            return response()->json([
                'message' => 'Manual payment submitted successfully. Pending verification.',
                'transaction' => $transaction,
                'reference' => $refNo,
            ], 201);
        }

        // Initialize Paystack paymen
          $paystackResponse = Http::withHeaders([
    'Authorization' => 'Bearer ' . config('services.paystack.secretKey'),
    'Content-Type' => 'application/json',
])->post('https://api.paystack.co/transaction/initialize', [
    'email' => $request->email,
    'amount' => $request->amount * 100,
    'reference' => $refNo,
]);
        
        

        if (!$paystackResponse->successful() || !isset($paystackResponse['data'])) {
            Log::error('Paystack initialization failed:', [
                'response' => $paystackResponse->body()
            ]);
            return response()->json([
                'message' => 'Failed to initialize Paystack transaction.',
            ], 500);
        }

        $responseData = $paystackResponse['data'];
        // Update ref_no with Paystack reference for accuracy
        $transactionData['ref_no'] = $responseData['reference'];

        $transaction = Transaction::create($transactionData);


        logActivity('installment_payment', 'User initiated a Paystack installment payment');

        return response()->json([
            'message' => 'Paystack payment initialized successfully.',
            'transaction' => $transaction,
            'payment_url' => $responseData['authorization_url'],
            'reference' => $responseData['reference'],
        ], 201);
    }
    public function index()
    {
        try {
            // Retrieve all installments with optional relationships (e.g. user or property)
            $installments = Installment::with(['user', 'property'])->get();

            return response()->json([
                'status' => 'success',
                'data' => $installments
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch installment data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function userInstallments(Request $request)
    {
        try {
                $user = Auth::user();
    
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized',
                ], 401);
            }
    
            $installments = Installment::with('property')
                ->where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get();
    
            return response()->json([
                'status' => 'success',
                'data' => $installments,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch user installments',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
   public function continueInstallment(Request $request)
{
    $request->validate([
        'transaction_id' => 'required|exists:transactions,id',
        'amount' => 'required|numeric|min:1',
        'email'=>'required|email|exists:users,email',
    ]);

    $originalTransaction = Transaction::find($request->transaction_id);

    if (!$originalTransaction) {
        return response()->json(['message' => 'Transaction not found.'], 404);
    }

    $installment = Installment::where('transaction_id', $originalTransaction->id)->first();

    if (!$installment) {
        return response()->json(['message' => 'Installment record not found.'], 404);
    }

    if ($installment->paid_count >= $installment->installment_count) {
        return response()->json(['message' => 'Installment already completed.'], 400);
    }

    $user = $originalTransaction->user;
    if (!$user) {
    return response()->json(['message' => 'User not found for the original transaction.'], 404);
}
    $email = $request->email;
    $amountInKobo = $request->amount * 100; // Convert to Kobo for Paystack
    $ref_no = 'REF-' . strtoupper(uniqid());

    // 1. Initiate Paystack payment
    $response = Http::withToken(env('PAYSTACK_SECRET_KEY'))->post('https://api.paystack.co/transaction/initialize', [
        'email' => $email,
        'amount' => $amountInKobo,
        'reference' => $ref_no,
        // 'callback_url' => url('/api/paystack/webhook'), 
    ]);

    if (!$response->successful()) {
        return response()->json(['message' => 'Failed to initialize Paystack payment.'], 500);
    }

    $paystackData = $response->json()['data'];

    // 2. Save the transaction to the database
    $newTransaction = Transaction::create([
        'user_id' => $user->id,
        'property_purchased_id' => $originalTransaction->property_purchased_id,
        'transaction_type' => 'continue_installment',
        'amount' => $request->amount,
        'status' => 'pending',
        'units' => $originalTransaction->units,
        'installment_count' => $originalTransaction->installment_count,
        'ref_no' => $ref_no,
         'parent_transaction_id' => $originalTransaction->id,
    ]);

    return response()->json([
        'message' => 'Installment transaction created and payment link generated.',
        'transaction_id' => $request->transaction_id,
        'amount' => $request->amount,
        'email' => $request->email,
        'payment_url' => $paystackData['authorization_url'],
    ], 201);
}

}
