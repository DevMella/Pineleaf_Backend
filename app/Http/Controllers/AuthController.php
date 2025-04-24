<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function investorregister(Request $request){
        $fields = $request->validate([
            'fullName' => 'required|max:100',
            'email' =>'required|email|unique:users',
            'number' => 'required|unique:users,number',
            'referral_code' => 'nullable',
            'userName' => 'required|max:50|unique:users,userName',
            'password' =>'required|confirmed'
        ]);
        $user = User::create($fields);
        $token = $user->createToken($request->fullName);
        return [
            'user' => $user,
            'token' => $token->plainTextToken
        ];
    }
    public function login(Request $request){
        return 'login';
    }
    public function logout(Request $request){
        return 'logout';
    }
}
