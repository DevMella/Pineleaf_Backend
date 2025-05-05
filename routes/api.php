<?php

use App\Http\Controllers\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\PurchaseControllerController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/realtor/register', [AuthController::class, 'register']);
Route::post('/realtor/login', [AuthController::class, 'login']);
Route::middleware('throttle:3,1')->post('/realtor/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::get('/manual-purchase-info', [PurchaseController::class, 'manualInfo'])->middleware('auth:sanctum');
Route::post('/manual-purchase-upload', [PurchaseController::class, 'uploadProof'])->middleware('auth:sanctum');



Route::middleware('auth:sanctum')->group(function () {
    Route::get('/admin/allusers', [UserController::class, 'index']);
    Route::get('/admin/users/search', [UserController::class, 'search']);
    Route::delete('/admin/users/{id}', [UserController::class, 'destroy']);
});
