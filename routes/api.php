<?php

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DepositController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/realtor/register', [AuthController::class, 'register']);
Route::post('/realtor/login', [AuthController::class, 'login']);
Route::middleware('throttle:3,1')->post('/realtor/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::get('/manual-deposit-info', [DepositController::class, 'manualInfo'])->middleware('auth:sanctum');
Route::post('/manual-deposit-upload', [DepositController::class, 'uploadProof'])->middleware('auth:sanctum');

Route::get('/admin/allusers', [UserController::class, 'index'])->middleware('auth:sanctum');
Route::delete('/admin/deleteuser/{id}', function (Request $request, $id) {
    $user = $request->user();
    if (!$user || $user->role !== 'admin') {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    return app(UserController::class)->destroy($id);
})->middleware('auth:sanctum');