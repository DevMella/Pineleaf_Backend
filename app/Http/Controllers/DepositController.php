<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DepositController extends Controller
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

    // public function uploadProof(Request $request)
    // {
    //     $request->validate([
    //         'user_id' => 'required|integer',
    //         'amount' => 'required|numeric',
    //         'payment_proof' => 'required|image|mimes:jpg,jpeg,png|max:2048',
    //     ]);

    //     $path = $request->file('payment_proof')->store('proofs', 'public');

    //     // Save to DB if needed (e.g., ManualDeposit model)

    //     return response()->json([
    //         'message' => 'Proof uploaded successfully',
    //         'path' => $path
    //     ]);
    // }

}
