<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function profile(Request $request){
        $fields = $request->validate([
            'new_password' => 'required|confirmed',
        ]);
        $user = $request->user();

        
    }
}
