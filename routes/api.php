<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/realtor/register', [AuthController::class, 'register']);
Route::post('/realtor/login', [AuthController::class, 'login']);
Route::middleware('throttle:3,1')->post('/realtorlogout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
