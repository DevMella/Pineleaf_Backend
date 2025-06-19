<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use App\Mail\PasswordResetMail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Mail\LoginNotificationMail;

class PasswordResetController extends Controller
{
    public function sendResetLink(Request $request)
{
    $request->validate([
        'email' => 'required|email|exists:users,email',
    ]);

    $user = User::where('email', $request->email)->first();

    // Check if user exists
    if (!$user) {
        return response()->json(['message' => 'User not found.'], 404);
    }

    // Generate token and expiry
    $token = bin2hex(random_bytes(32));
    $expiresAt = now()->addMinutes(30);

    // Save to DB
    $user->reset_token = $token;
    $user->reset_expires_at = $expiresAt;
    $user->save(); // CRITICAL!

    // Send mail
    Mail::to($user->email)->send(new PasswordResetMail($user, $token));

    return response()->json(['message' => 'Password reset link sent to your email.']);
}


    public function updatePassword(Request $request)
{
    $request->validate([
        'password' => 'required|confirmed|min:6',
    ]);
    $token = $request->query('token') ?? $request->token;

    if (!$token) {
        return response()->json(['message' => 'Reset token is missing.'], 400);
    }

    // Find user by valid token
    $user = User::where('reset_token', $request->token)
        ->where('reset_expires_at', '>', Carbon::now())
        ->first();

    if (!$user) {
        return response()->json(['message' => 'Invalid or expired token'], 400);
    }

    // Update password and clear reset token
    $user->password = Hash::make($request->password);
    $user->reset_token = null;
    $user->reset_expires_at = null;
    $user->save();

    $browser = $request->header('User-Agent'); // or use a parser like jenssegers/agent
    $location = $request->ip(); // You can use IP-based location services here
    $loginTime = now()->format('l, F j, Y h:i A');

    // Send login notification mail
    Mail::to($user->email)->send(new LoginNotificationMail($user, $browser, $location, $loginTime));

    return response()->json(['message' => 'Password successfully updated.']);
}
}