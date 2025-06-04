<?php

namespace App\Http\Controllers;

use App\Models\Referral;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use App\Models\User;

class AdminActions extends Controller
{
    /**
     * Referral
     */

    public function Referral(Request $request)
    {
        $user = $request->user();
        if (!$user || $user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $query = Referral::query();
            $perPage = max((int) $request->input('per_page', 50), 1);

            // Filter by date (created_at)
            if ($request->has('from_date')) {
                $query->whereDate('created_at', '>=', $request->input('from_date'));
            }

            if ($request->has('to_date')) {
                $query->whereDate('created_at', '<=', $request->input('to_date'));
            }

            // Filter by exact level
            if ($request->filled('level')) {
                $query->where('level', $request->input('level'));
            }

            // Filter by exact amount
            if ($request->filled('amount')) {
                $query->where('bonus', $request->input('amount'));
            }

            // Filter by amount range
            if ($request->filled('min_amount')) {
                $query->where('bonus', '>=', $request->input('min_amount'));
            }

            if ($request->filled('max_amount')) {
                $query->where('bonus', '<=', $request->input('max_amount'));
            }

            $query->orderBy('created_at', 'desc');

            return response()->json($query->paginate($perPage));
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve referrals',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     *
     */
    public function transactions(Request $request)
    {
        $user = $request->user();
        if (!$user || $user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $query = Transaction::query();
            $perPage = max((int) $request->input('per_page', 50), 1);

            // Filter by date (created_at)
            if ($request->has('from_date')) {
                $query->whereDate('created_at', '>=', $request->input('from_date'));
            }
            if ($request->has('to_date')) {
                $query->whereDate('created_at', '<=', $request->input('to_date'));
            }

            // Filter by status
            if ($request->filled('status')) {
                $query->where('status', $request->input('status'));
            }
            if ($request->filled('transaction_type')) {
                $query->where('transaction_type', $request->input('transaction_type'));
            }

            // Filter by exact amount
            if ($request->filled('amount')) {
                $query->where('amount', $request->input('amount'));
            }

            // Filter by amount range
            if ($request->filled('min_amount')) {
                $query->where('amount', '>=', $request->input('min_amount'));
            }
            if ($request->filled('max_amount')) {
                $query->where('amount', '<=', $request->input('max_amount'));
            }

            $query->orderBy('created_at', 'desc');

            return response()->json($query->paginate($perPage));
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve transactions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     *
     */
    public function purchase(Request $request)
    {
        $user = $request->user();
        if (!$user || $user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $query = Transaction::where('transaction_type', 'purchase');
            $perPage = max((int) $request->input('per_page', 50), 1);

            // Filter by date (created_at)
            if ($request->has('from_date')) {
                $query->whereDate('created_at', '>=', $request->input('from_date'));
            }
            if ($request->has('to_date')) {
                $query->whereDate('created_at', '<=', $request->input('to_date'));
            }

            // Filter by status
            if ($request->filled('status')) {
                $query->where('status', $request->input('status'));
            }


            // Filter by exact amount
            if ($request->filled('amount')) {
                $query->where('amount', $request->input('amount'));
            }

            // Filter by amount range
            if ($request->filled('min_amount')) {
                $query->where('amount', '>=', $request->input('min_amount'));
            }
            if ($request->filled('max_amount')) {
                $query->where('amount', '<=', $request->input('max_amount'));
            }

            $query->orderBy('created_at', 'desc');

            return response()->json($query->paginate($perPage));
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve transactions',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    public function updateUserDetails(Request $request)
{
    $admin = $request->user();

    if (!$admin || $admin->role !== 'admin') {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    $request->validate([
        'user_id' => 'required|exists:users,id',
        'email' => 'required|email',
        'number' => 'required',
        'fullName' => 'required|string|max:255',
    ]);

    $user = User::find($request->user_id);

    // Check if email already exists (except current user)
    if (User::where('email', $request->email)->where('id', '!=', $user->id)->exists()) {
        return response()->json(['message' => 'Email already in use'], 400);
    }

    // Check if phone already exists (except current user)
    if (User::where('number', $request->number)->where('id', '!=', $user->id)->exists()) {
        return response()->json(['message' => 'Phone number already in use'], 400);
    }

    // Update user
    $user->email = $request->email;
    $user->number = $request->number;
    $user->fullName = $request->fullName;
    $user->save();

    return response()->json([
        'message' => 'User details updated successfully',
        'user' => $user,
    ]);
}
}