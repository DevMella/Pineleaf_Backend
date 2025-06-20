<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\Payment;
use App\Models\Property;
use Carbon\Carbon;
use App\Models\Transaction;
use Illuminate\Support\Facades\Log;
use App\Models\Referral;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Controller;
use App\Mail\RegistrationSuccessMail;
use Illuminate\Support\Facades\Mail;


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
        'amount' => 'required|numeric',
    ]);

    // Handle referral
    $referrer = null;
    if ($request->filled('referral_code')) {
        $referrer = User::where('my_referral_code', $request->referral_code)->first();
        if (!$referrer) {
            return response()->json(['message' => 'Invalid referral code.'], 422);
        }
    }

    $myReferralCode = Str::upper(Str::random(6));

    $user = User::create([
        'fullName' => $request->fullName,
        'email' => $request->email,
        'number' => $request->number,
        'password' => Hash::make($request->password),
        'my_referral_code' => $myReferralCode,
        'referral_code' => $request->referral_code,
        'enabled' => $request->payment_method === 'manual' ? true : false,
    ]);
    $firstName = explode(' ', $user->fullName)[0];
    // Mail::to($user->email)->send(new RegistrationSuccessMail($user));
    // Handle manual payment upload
    if ($request->paymmaent_method === 'manual' && $request->hasFile('payment')) {
        $paymentFile = $request->file('payment');
        $fileName = time() . '_' . $paymentFile->getClientOriginalName();
        $paymentPath = $paymentFile->storeAs('payments', $fileName, 'public');

        $transaction = Transaction::create([
            'user_id' => $user->id,
            'amount' => $request->amount,
            'transaction_type' => 'registration',
            'ref_no' => 'REG_' . now()->format('Ymd') . '_' . strtoupper(Str::random(6)),
            'property_purchased_id' => null,
            'units' => null,
            'proof_of_payment' => $paymentPath,
            'status' => 'pending',
        ]);

        Payment::create([
            'user_id' => $user->id,
            'ref_no' => $transaction->ref_no,
            'transaction_id' => $transaction->id,
        ]);

        if ($referrer) {
            $level = 1;
            $currentReferrer = $referrer;
            while ($currentReferrer && $level <= 2) {
                Referral::firstOrCreate([
                    'referral_id' => $currentReferrer->id,
                    'referee_id' => $user->id,
                    'level' => $level,
                ]);
                $currentReferrer = User::where('my_referral_code', $currentReferrer->referral_code)->first();
                $level++;
            }
        }

        $token = $user->createToken($request->fullName);

        return response()->json([
            'user' => $user,
            'token' => $token->plainTextToken,
            'message' => 'User registered successfully.',
        ], 201);
    }

    // Handle Paystack payment
    if ($request->payment_method === 'paystack') {
     $paystackSecret = config('services.paystack.secretKey');

        if (!$paystackSecret) {
            Log::error('Paystack secret key is missing.');
            return response()->json(['message' => 'Payment configuration error.'], 500);
        }

        $paystackResponse = Http::withHeaders([
            'Authorization' => 'Bearer ' . $paystackSecret,
        ])->post('https://api.paystack.co/transaction/initialize', [
            'email' => $request->email,
            'amount' => $request->amount * 100,
            'callback_url' => 'https://dashboard.pineleafestates.com',
        ]);

        if (!$paystackResponse->successful()) {
            Log::error('Paystack Init Failed', [
                'status' => $paystackResponse->status(),
                'response' => $paystackResponse->body(),
            ]);

            return response()->json(['message' => 'Failed to initialize Paystack payment.'], 500);
        }

        $paymentData = $paystackResponse->json()['data'];

        $transaction = Transaction::create([
            'user_id' => $user->id,
            'amount' => $request->amount,
            'transaction_type' => 'registration',
            'ref_no' => $paymentData['reference'],
            'proof_of_payment' => $paymentData['reference'],
            'status' => 'pending',
        ]);

        Payment::create([
            'user_id' => $user->id,
            'ref_no' => $paymentData['reference'],
            'transaction_id' => $transaction->id,
        ]);

        logActivity('registration', 'User registered with Paystack pending payment.');

        return response()->json([
            'status' => 'pending_payment',
            'message' => 'Please complete the payment via Paystack.',
            'payment_url' => $paymentData['authorization_url'],
            'reference' => $paymentData['reference'],
            'user_id' => $user->id,
        ]);
    }
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
                    'enabled' => true,
                ]);
            } else {
                $admin->role = 'admin';
                $admin->save();
            }
            $admin->tokens()->delete(); // Revoke all previous tokens
            $token = $admin->createToken('admin-token')->plainTextToken;

            $no_users = User::where('role', '!=', 'admin')->count();
            $no_properties = Property::count();
            $no_purchases = Payment::count();
            $total_balance = Transaction::where('transaction_type', '=', 'purchase')->sum('amount');
            $total_bonus = Referral::sum('bonus');

            return response()->json([
                'success' => true,
                'message' => 'Admin data retrieved successfully',
                'data' => [
                    'user' => $admin,
                    'token' => $token,
                    'no_users' => $no_users,
                    'no_properties' => $no_properties,
                    'no_purchases' => $no_purchases,
                    'total_balance' => $total_balance,
                    'total_bonus' => $total_bonus
                ]
            ], 200);
        }

        $user = User::where('email', $fields['login'])
            ->orWhere('number', $fields['login'])
            ->first();

        if (!$user || !Hash::check($fields['password'], $user->password)) {
            return response([
                'message' => 'Invalid Credentials'
            ], 401);
        }

        if (!$user->enabled) {
    $ref_no = 'REG_' . uniqid();

    // Step 2: Prepare Paystack payment data
    $paystackData = [
        'email' => $user->email,
        'amount' => 5000000, // in kobo
        'reference' => $ref_no,
        
    ];

    // Step 3: Initialize Paystack payment
    $paystackResponse = Http::withToken(env('PAYSTACK_SECRET_KEY'))
        ->post('https://api.paystack.co/transaction/initialize', $paystackData);

    $result = $paystackResponse->json();

    if (!$result['status']) {
        return response([
            'message' => 'Failed to initialize payment. Please try again.'
        ], 500);
    }

    // Step 4: Save transaction with 'pending' status
    Transaction::create([
        'user_id' => $user->id,
        'ref_no' => $ref_no,
        'amount' => 50000, // In naira for storage
        'transaction_type' => 'registration',
        'status' => 'pending'
    ]);

    // Step 5: Return payment URL
    return response([
        'message' => 'Your payment is not successful and your account is not activated yet. Please complete payment.',
        'authorization_url' => $result['data']['authorization_url']
    ], 403);
}


        $token = $user->createToken($user->fullName);
        logActivity('login', 'User logged in successfully');
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
        logActivity('logout', 'User logged out successfully');
        return response([
            'message' => 'Successfully logged out.'
        ], 200);
    }
}