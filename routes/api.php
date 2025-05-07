<?php

use App\Http\Controllers\PropertyController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\PaystackController;
use Illuminate\Container\Attributes\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('documentation');
})->name('home');

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// AUTHENTICATION ROUTES
Route::post('/realtor/register', [AuthController::class, 'register']);
Route::post('/realtor/login', [AuthController::class, 'login']);
Route::middleware('throttle:3,1')->post('/realtor/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::get('/manual-purchase-info', [PurchaseController::class, 'manualInfo'])->middleware('auth:sanctum');
Route::post('/manual-purchase-upload', [PurchaseController::class, 'uploadProof'])->middleware('auth:sanctum');


// PAYMENT ROUTES
Route::get('/manual-deposit-info', [PurchaseController::class, 'manualInfo'])->middleware('auth:sanctum');
Route::post('/manual-deposit-upload', [PurchaseController::class, 'uploadProof'])->middleware('auth:sanctum');
Route::post('/initiate-registration-payment', [AuthController::class, 'initiatePaystackPayment']);
Route::post('/paystack/callback', [PaystackController::class, 'verify']);
Route::post('/purchase', [PurchaseController::class, 'handlePurchase']);
Route::post('/confirm-payment', [PaystackController::class, 'confirmPayment']);

// ALL ADMIN ROUTES
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/admin/allusers', [UserController::class, 'index']);
    Route::get('/admin/users/search', [UserController::class, 'search']);
    Route::delete('/admin/users/{id}', [UserController::class, 'destroy']);
});

// PROPERTIES ROUTES
Route::get('/properties/search', [PropertyController::class, 'search']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/properties/create', [PropertyController::class, 'create']);
    Route::get('/properties', [PropertyController::class, 'index']);
    Route::get('/latest-properties', [PropertyController::class, 'latest']);
    Route::put('/properties/{id}', [PropertyController::class, 'update']);
    Route::get('/properties/{id}', [PropertyController::class, 'show']);
    Route::delete('/properties/{id}', [PropertyController::class, 'destroy']);
});
