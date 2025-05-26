<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\Transaction;
use App\Models\Property;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    public function manualInfo(Request $request)
{
    return response()->json([
        'account_name' => config('services.manual_payment.account_name'),
        'account_number' => config('services.manual_payment.account_number'),
        'bank_name' => config('services.manual_payment.bank_name'),
        'message' => 'Account details fetched successfully.'
    ], 200);
}

    public function handlePurchase(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'email' => 'required|email|exists:users,email',
            'payment_method' => 'required|in:manual,paystack',
            'amount' => 'required|numeric|min:1',
            'units' => 'required|numeric|min:1',
            'property_purchased_id' => 'required|exists:properties,id',
            'payment_proof' => 'required_if:payment_method,manual|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        $ref_no = uniqid();

        $property = Property::findOrFail($request->property_purchased_id);

        if ($request->payment_method === 'manual') {
            $proofPath = $request->file('payment_proof')->store('payment_proofs', 'public');

            DB::transaction(function () use ($request, $ref_no, $proofPath, $property) {
                Transaction::create([
                    'user_id' => $request->user_id,
                    'amount' => $request->amount,
                    'transaction_type' => 'purchase',
                    'ref_no' => $ref_no,
                    'units' => $request->units,
                    'property_purchased_id' => $request->property_purchased_id,
                    'proof_of_payment' => $proofPath,
                    'status' => 'pending',
                ]);

                $property->increment('unit_sold', $request->units);
            });

            return response()->json([
                'message' => 'Manual payment submitted successfully. Pending verification.',
                'reference' => $ref_no,
            ], 201);
        } else {
            $paystackResponse = Http::withToken(env('PAYSTACK_SECRET_KEY'))
                ->post('https://api.paystack.co/transaction/initialize', [
                    'email' => $request->email,
                    'amount' => $request->amount * 100,
                    'reference' => $ref_no,
                ]);

            if (!$paystackResponse->successful()) {
                return response()->json(['message' => 'Failed to initialize Paystack transaction.'], 500);
            }

            $responseData = $paystackResponse->json()['data'];

            DB::transaction(function () use ($request, $responseData, $property) {
                Transaction::create([
                    'user_id' => $request->user_id,
                    'amount' => $request->amount,
                    'transaction_type' => 'purchase',
                    'ref_no' => $responseData['reference'],
                    'units' => $request->units,
                    'property_purchased_id' => $request->property_purchased_id,
                    'status' => 'pending',
                ]);
            });

            logActivity('land_purchase', 'User successfully purchased a land and still on pending');

            return response()->json([
                'message' => 'Paystack payment initialized successfully.',
                'payment_url' => $responseData['authorization_url'],
                'reference' => $responseData['reference'],
            ], 200);
        }
    }
}
