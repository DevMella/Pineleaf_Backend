<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\Transaction; 
use App\Models\Payment; 
class PurchaseController extends Controller
{
    public function manualInfo(Request $request){
        $user = $request->user(); 

        return response()->json([
            'account_name' => env('MANUAL_ACCOUNT_NAME'),
            'account_number' => env('MANUAL_ACCOUNT_NUMBER'),
            'bank_name' => env('MANUAL_BANK_NAME'),
            'message' => 'Account details fetched successfully.'
        ], 200);
    }

    public function uploadProof(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'amount' => 'required|numeric',
            'property_purchased_id' => 'nullable|integer',
            'units' => 'required|numeric',
            'payment_proof' => 'required|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $path = $request->file('payment_proof')->store('proofs', 'public');

        $ref_no = 'D_' . Carbon::now()->format('Ymd') . '_' . Str::upper(Str::random(6));

        $transaction = Transaction::create([
            'user_id' => $request->user_id,
            'amount' => $request->amount,
            'transaction_type' => 'purchase',
            'ref_no' => $ref_no,
            'property_purchased_id' => $request->property_purchased_id,
            'units' => $request->units, 
            'proof_of_payment' => $path,
            'status' => 'pending',  
        ]);
        Payment::create([
            'user_id' => $request->user_id,
            'payment_type' => 'purchase',
            'amount' => $request->amount,
            'gateway_ref' => $path,
            'ref_no' => $ref_no, 
        ]);
        return response()->json([
            'message' => 'Transaction submitted successfully',
            'ref_no' => $ref_no,
            'path' => $path
        ]);
    }
}
