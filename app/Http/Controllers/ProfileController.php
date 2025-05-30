<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User; 

class ProfileController extends Controller
{
    public function updateProfile(Request $request)
{
    $request->validate([
        'bank_name' => 'nullable|string|max:255',
        'account_name' => 'nullable|string|max:255',
        'account_number' => 'nullable|string|max:20',
    ]);

    /** @var \App\Models\User $user */
    $user = Auth::user();

    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized'
        ], 401);
    }

    $user->bank_name = $request->bank_name ?? $user->bank_name;
    $user->account_name = $request->account_name ?? $user->account_name;
    $user->account_number = $request->account_number ?? $user->account_number;

    $user->save(); // now $user is a known model, and your IDE won't complain

    return response()->json([
        'success' => true,
        'message' => 'Profile updated successfully',
        'data' => $user
    ]);
}

}
