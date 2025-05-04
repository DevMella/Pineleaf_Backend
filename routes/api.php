<?php

use App\Http\Controllers\PropertyController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DepositController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    // create a documentation page
    return view('welcome');
})->name('home');

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// AUTHENTICATION ROUTES
Route::post('/realtor/register', [AuthController::class, 'register']);
Route::post('/realtor/login', [AuthController::class, 'login']);
Route::middleware('throttle:3,1')->post('/realtor/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

// PAYMENT ROUTES
Route::get('/manual-deposit-info', [DepositController::class, 'manualInfo'])->middleware('auth:sanctum');
Route::post('/manual-deposit-upload', [DepositController::class, 'uploadProof'])->middleware('auth:sanctum');


// ALL ADMIN ROUTES
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/admin/allusers', [UserController::class, 'index']);
    Route::get('/admin/users/search', [UserController::class, 'search']);
    Route::delete('/admin/users/{id}', [UserController::class, 'destroy']);
});

// PROPERTIES ROUTES
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/properties', [PropertyController::class, 'index']);
    Route::get('/latest-properties', [PropertyController::class, 'latest']);
    Route::post('/properties/create', [PropertyController::class, 'create']);
    Route::get('/properties/{id}', [PropertyController::class, 'show']);
    Route::delete('/properties/{id}', [PropertyController::class, 'destroy']);
    Route::get('/properties/search', [PropertyController::class, 'search']);
});