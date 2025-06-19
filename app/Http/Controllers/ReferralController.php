<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Referral; 
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ReferralController extends Controller
{
   public function allReferrals()
    {
        try {
            $referrals = Referral::orderBy('created_at', 'desc')->get();

            return response()->json([
                'status' => 'success',
                'data' => $referrals,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch referrals',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
     public function userReferrals(Request $request)
    {
        try {
             $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized',
                ], 401);
            }

            $referrals = Referral::where('referral_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $referrals,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch user referrals',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

}

