<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\Payment;
use Carbon\Carbon;
use App\Models\Transaction;
use App\Models\Referral;

class AuthController extends Controller
{
    public function register(Request $request)
{
    $fields = $request->validate([
        'fullName' => 'required|max:100',
        'email' => 'required|email|unique:users',
        'number' => 'required|unique:users,number|max:13',
        'referral_code' => 'nullable|max:6',
        'payment_method' => 'required|in:manual,paystack',
        'password' => 'required|confirmed',
        'payment' => 'required_if:payment_method,manual|file|mimes:png,jpeg,jpg|max:2048',
        'amount' => 'required_if:payment_method,manual|nullable|numeric',
        'paystack_ref' => 'required_if:payment_method,paystack|nullable|string',
    ]);

    $referrer = null;
    if ($request->filled('referral_code')) {
        $referrer = User::where('my_referral_code', $request->referral_code)->first();

        if (!$referrer) {
            return response()->json(['message' => 'Invalid referral code.'], 422);
        }
    }

    $paymentPath = null;
    if ($request->payment_method === 'manual' && $request->hasFile('payment')) {
        $paymentFile = $request->file('payment');
        $fileName = time() . '_' . $paymentFile->getClientOriginalName();
        $paymentPath = $paymentFile->storeAs('payments', $fileName, 'public');
    }

    $amount = $request->payment_method === 'manual'
        ? $request->amount
        : $this->getAmountFromPaystack($request->paystack_ref);

    $myReferralCode = Str::upper(Str::random(6));

    $user = User::create([
        'fullName' => $request->fullName,
        'email' => $request->email,
        'number' => $request->number,
        'password' => Hash::make($request->password),
        'my_referral_code' => $myReferralCode,
        'referral_code' => $request->referral_code, // store who referred this user
    ]);

    // Handle 3-level referral chain
    $currentRefCode = $user->referral_code;
    $level = 1;

    while ($currentRefCode && $level <= 3) {
        $referrer = User::where('my_referral_code', $currentRefCode)->first();

        if (!$referrer || $referrer->id == $user->id) {
            break;
        }

        // Prevent duplicate referrals
        if (!Referral::where('referral_id', $referrer->id)->where('referee_id', $user->id)->exists()) {
            Referral::create([
                'referral_id' => $referrer->id,
                'referee_id' => $user->id,
                'level' => $level,
            ]);
        }

        $currentRefCode = $referrer->referral_code; // Go up the chain
        $level++;
    }

    Payment::create([
        'user_id' => $user->id,
        'payment_type' => 'registration_payment',
        'amount' => $amount,
        'gateway_ref' => $request->payment_method === 'manual' ? $paymentPath : $request->paystack_ref,
        'ref_no' => 'REG_' . Carbon::now()->format('Ymd') . '_' . strtoupper(Str::random(5)),
    ]);

    Transaction::create([
        'user_id' => $user->id,
        'amount' => $amount,
        'transaction_type' => 'registration',
        'ref_no' => 'REG_' . Carbon::now()->format('Ymd') . '_' . strtoupper(Str::random(6)),
        'property_purchased_id' => null,
        'units' => 1,
        'proof_of_payment' => $request->payment_method === 'manual' ? $paymentPath : $request->paystack_ref,
        'status' => 'pending',
    ]);

    $token = $user->createToken($request->fullName);

    return response()->json([
        'user' => $user,
        'token' => $token->plainTextToken,
        'message' => 'Registration successful with ' . $request->payment_method . ' payment recorded.',
    ], 201);
}

    public function login(Request $request)
    {
        $fields = $request->validate([
            'login' => 'required',
            'password' => 'required'
        ]);

        $adminEmail = env('ADMIN_EMAIL', '');
        $adminPassword = env('ADMIN_PASSWORD', '');

        if ($fields['login'] === $adminEmail && $fields['password'] === $adminPassword) {
            $admin = User::where('email', $adminEmail)->first();

            if (!$admin) {
                $admin = User::create([
                    'email' => $adminEmail,
                    'fullName' => env('ADMIN_NAME', 'Admin'),
                    'password' => bcrypt($adminPassword),
                    'role' => 'admin',
                 ]);
            } else {
                $admin->role = 'admin';
                $admin->save();
            }

            $token = $admin->createToken('admin-token')->plainTextToken;

            return [
                'user' => $admin,
                'token' => $token
            ];
        }

        $user = User::where('email', $fields['login'])
            ->orWhere('number', $fields['login'])
            ->first();

        if (!$user || !Hash::check($fields['password'], $user->password)) {
            return response([
                'message' => 'Invalid Credentials'
            ], 401);
        }

        $token = $user->createToken($user->fullName);

        return [
            'user' => $user,
            'token' => $token->plainTextToken
        ];
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        $user->tokens->each(function ($token) {
            $token->delete();
        });

        return response([
            'message' => 'Successfully logged out.'
        ], 200);
    }
}