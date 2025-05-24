<?php

use App\Http\Controllers\PropertyController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\InstallmentController;
use App\Http\Controllers\ManualController;
use App\Http\Controllers\PaystackWebhookController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\PaystackController;
use App\Models\Installment;
use App\Http\Controllers\WithdrawController;
use Illuminate\Container\Attributes\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('documentation');
})->name('home');

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
Route::get('/activity-logs/user/{id}', [WithdrawController::class, 'getUserActivityLogs'])->middleware('auth:sanctum');
Route::get('/notification/user/{id}', [UserController::class, 'getUserNotification'])->middleware('auth:sanctum');

// AUTHENTICATION ROUTES
Route::post('/realtor/register', [AuthController::class, 'register']);
Route::post('/realtor/login', [AuthController::class, 'login']);
Route::middleware('throttle:3,1')->post('/realtor/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::get('/manual-purchase-info', [PurchaseController::class, 'manualInfo'])->middleware('auth:sanctum');
Route::post('/manual-purchase-upload', [PurchaseController::class, 'uploadProof'])->middleware('auth:sanctum');


// PAYMENT ROUTES
Route::get('/manual-deposit-info', [PurchaseController::class, 'manualInfo'])->middleware('auth:sanctum');
Route::post('/manual-deposit-upload', [PurchaseController::class, 'uploadProof'])->middleware('auth:sanctum');
Route::post('/paystack/callback', [PaystackController::class, 'verify']);
Route::post('/manual/verify', [ManualController::class, 'manualVerify']);
Route::post('/purchase', [PurchaseController::class, 'handlePurchase'])->middleware('auth:sanctum');
Route::post('/confirm-payment', [PaystackController::class, 'confirmPayment'])->middleware('auth:sanctum');
Route::post('/manual-confirm-payment', [ManualController::class, 'confirmManualPayment']);
Route::post('/installment', [InstallmentController::class, 'handleInstallment'])->middleware('auth:sanctum');
Route::post('/manual-confirm-installment', [ManualController::class, 'confirmInstallmentPayment']);
Route::post('/confirm-installment', [PaystackController::class, 'installmentPayment'])->middleware('auth:sanctum');
Route::post('/withdraw', [WithdrawController::class, 'initiateWithdrawal'])->middleware('auth:sanctum');
Route::post('/withdraw/confirm', [WithdrawController::class, 'confirmWithdrawal'])->middleware('auth:sanctum');
Route::post('/paystack/webhook', [PaystackWebhookController::class, 'handle']);


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
