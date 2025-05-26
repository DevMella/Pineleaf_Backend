<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use App\Models\Transaction;
use Illuminate\Support\Facades\Storage;

class TransferController extends Controller
{
    public function transferToInvestment(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'proof_of_payment' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $user = Auth::user();
        $amount = $request->amount;

        if ($user->balance < $amount) {
            return response()->json(['error' => 'Insufficient balance'], 400);
        }

        // Deduct balance temporarily
        $user->balance -= $amount;
        $user->save();

        // Upload proof of payment if exists
        $proofPath = null;
        if ($request->hasFile('proof_of_payment')) {
            $proofPath = $request->file('proof_of_payment')->store('proofs', 'public');
        }

        // Save transaction (pending approval)
        $transaction = new Transaction();
        $transaction->user_id = $user->id;
        $transaction->amount = $amount;
        $transaction->status = 'pending';
        $transaction->type = 'investment';
        $transaction->proof_of_payment = $proofPath;
        $transaction->save();

        // Send data to investment API
        $token = 'your_jwt_token_here'; // Replace with real JWT if needed

        $apiPayload = [
            'amount' => $amount,
            'fullname' => $user->name,
            'email' => $user->email,
        ];

        $response = Http::withToken($token)
            ->attach('proof_of_payment', $request->file('proof_of_payment')->get(), $request->file('proof_of_payment')->getClientOriginalName())
            ->post('https://investment-site.com/api/manual-deposit', $apiPayload);

        if ($response->failed()) {
            return response()->json(['error' => 'Failed to notify investment site'], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Transfer submitted and pending approval',
            'transaction_id' => $transaction->id,
        ]);
    }
}
