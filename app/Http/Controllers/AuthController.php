<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $fields = $request->validate([
            'fullName' => 'required|max:100',
            'email' => 'required|email|unique:users',
            'number' => 'required|unique:users,number|max:13',
            'referral_code' => 'nullable|max:6',
            'payment' => 'required|file|mimes:png,jpeg,jpg|max:2048',
            'password' => 'required|confirmed'
        ]);
        if ($request->hasFile('payment')) {
            $paymentFile = $request->file('payment');
            $fileName = time() . '_' . $paymentFile->getClientOriginalName();
            $filePath = $paymentFile->storeAs('payments', $fileName, 'public');
            $fields['payment'] = $filePath;
        }
        $fields['my_referral_code'] = Str::upper(Str::random(6));
        $user = User::create($fields);
        $token = $user->createToken($request->fullName);
        return [
            'user' => $user,
            'token' => $token->plainTextToken
        ];
    }
    public function login(Request $request)
    {
        $fields = $request->validate([
            'login' => 'required',
            'password' => 'required'
        ]);

        // Check if login matches ADMIN ENV credentials
        $adminEmail = env('ADMIN_EMAIL', '');
        $adminPassword = env('ADMIN_PASSWORD', '');

        // In your login method, replace the firstOrCreate block with:
        if ($fields['login'] === $adminEmail && $fields['password'] === $adminPassword) {
            // Find or create admin user
            $admin = User::where('email', $adminEmail)->first();

            // If admin doesn't exist, create it
            if (!$admin) {
                $admin = User::create([
                    'email' => $adminEmail,
                    'fullName' => env('ADMIN_NAME', 'Admin'),
                    'password' => bcrypt($adminPassword),
                    'role' => 'admin',
                ]);
            } else {
                // Ensure existing user has admin role
                $admin->role = 'admin';
                $admin->save();
            }

            // Issue a real Sanctum token
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