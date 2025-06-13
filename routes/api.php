<?php

use App\Http\Controllers\AdminActions;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\subscribersController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\TestimonialsController;
use App\Http\Controllers\ConsultationController;
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
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\InspectionController;
use App\Http\Controllers\RealtorStarsController;
use App\Http\Controllers\LandController;
use App\Http\Controllers\PasswordResetController;
use App\Mail\PasswordResetMail;
use App\Http\Controllers\ReferralController;

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
// Route::post('/confirm-payment', [PaystackController::class, 'confirmPayment'])->middleware('auth:sanctum');
Route::post('/manual-confirm-payment', [ManualController::class, 'confirmManualPayment']);
Route::post('/installment', [InstallmentController::class, 'handleInstallment'])->middleware('auth:sanctum');
Route::post('/continue_installment', [InstallmentController::class, 'continueInstallment'])->middleware('auth:sanctum');
Route::get('/all_installment', [InstallmentController::class, 'index'])->middleware('auth:sanctum');
Route::get('/each_installment', [InstallmentController::class, 'userInstallments'])->middleware('auth:sanctum');
Route::post('/manual-confirm-installment', [ManualController::class, 'confirmInstallmentPayment']);
// Route::post('/confirm-installment', [PaystackController::class, 'installmentPayment'])->middleware('auth:sanctum');
Route::post('/withdraw', [WithdrawController::class, 'initiateWithdrawal'])->middleware('auth:sanctum');
Route::post('/withdraw/confirm', [WithdrawController::class, 'confirmWithdrawal'])->middleware('auth:sanctum');

Route::post('/paystack/webhook', [PaystackWebhookController::class, 'handle']);
// Route::post('/paystack/webhook', [PaystackWebhookController::class], 'handle');

Route::middleware('auth:sanctum')->post('/profile/update', [ProfileController::class, 'updateProfile']);



// ALL ADMIN ROUTES

// USER ROUTES

// USER ROUTES
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/admin/allusers', [UserController::class, 'index']);
    Route::get('/admin/users/search', [UserController::class, 'search']);
    Route::delete('/admin/users/{id}', [UserController::class, 'destroy']);
    Route::get('/admin/users/{id}', [UserController::class, 'each']);
    Route::get('/admin/users/{id}', [UserController::class, 'each']);
});

// PROPERTIES ROUTES
Route::get('/properties/search', [PropertyController::class, 'search']);
Route::get('/properties/search/{id}', [PropertyController::class, 'each']);
Route::get('/properties/search/{id}', [PropertyController::class, 'each']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/properties/create', [PropertyController::class, 'create']);
    Route::get('/properties', [PropertyController::class, 'index']);
    Route::get('/latest-properties', [PropertyController::class, 'latest']);
    Route::get('/properties/{id}', [PropertyController::class, 'show']);
    Route::delete('/properties/{id}', [PropertyController::class, 'destroy']);
    Route::put('/properties/{id}', [PropertyController::class, 'update']);
    Route::put('/properties/{id}', [PropertyController::class, 'update']);
});

// TRANSACTION ROUTES
// TRANSACTION ROUTES
Route::get('/admin/transactions', [AdminActions::class, 'transactions'])->middleware('auth:sanctum');
Route::get('/admin/transactions/{id}', [AdminActions::class, 'eachTransaction'])->middleware('auth:sanctum');
Route::delete('/admin/transactions/{id}', [AdminActions::class, 'deleteEachTransaction'])->middleware('auth:sanctum');

// PURCHASE ROUTES
Route::get('/admin/transactions/{id}', [AdminActions::class, 'eachTransaction'])->middleware('auth:sanctum');
Route::delete('/admin/transactions/{id}', [AdminActions::class, 'deleteEachTransaction'])->middleware('auth:sanctum');

// PURCHASE ROUTES
Route::get('/admin/purchase', [AdminActions::class, 'purchase'])->middleware('auth:sanctum');
Route::get('/admin/purchase/{id}', [AdminActions::class, 'eachPurchase'])->middleware('auth:sanctum');
Route::delete('/admin/purchase/{id}', [AdminActions::class, 'deleteEachPurchase'])->middleware('auth:sanctum');

// REFERRAL ROUTES
Route::get('/admin/purchase/{id}', [AdminActions::class, 'eachPurchase'])->middleware('auth:sanctum');
Route::delete('/admin/purchase/{id}', [AdminActions::class, 'deleteEachPurchase'])->middleware('auth:sanctum');

// REFERRAL ROUTES
Route::get('/admin/referrals', [AdminActions::class, 'Referral'])->middleware('auth:sanctum');
Route::get('/admin/referrals/{id}', [AdminActions::class, 'eachReferral'])->middleware('auth:sanctum');
Route::delete('/admin/referrals/{id}', [AdminActions::class, 'deleteEachReferral'])->middleware('auth:sanctum');
Route::get('/admin/referrals/{id}', [AdminActions::class, 'eachReferral'])->middleware('auth:sanctum');
Route::delete('/admin/referrals/{id}', [AdminActions::class, 'deleteEachReferral'])->middleware('auth:sanctum');

// SUBSCRIBERS ROUTES
Route::post('/subscribers', [subscribersController::class, 'store']);
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/subscribers', [subscribersController::class, 'index']);
    Route::get('/subscribers/{id}', [subscribersController::class, 'show']);
    Route::delete('/subscribers/{id}', [subscribersController::class, 'destroy']);
});

// TESTIMONIALS ROUTES
Route::get('/testimonials/{id}', [TestimonialsController::class, 'show']);
Route::get('/testimonials', [TestimonialsController::class, 'index']);
Route::middleware('auth:sanctum')->group(function () {
    Route::delete('/testimonials/{id}', [TestimonialsController::class, 'destroy']);
    Route::post('/testimonials', [TestimonialsController::class, 'store']);
});

// LOCATION ROUTES
Route::get('/locations', [LocationController::class, 'index']);
Route::get('/locations/{id}', [LocationController::class, 'show']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/locations', [LocationController::class, 'store']);
    Route::put('/locations/{id}', [LocationController::class, 'update']);
    Route::delete('/locations/{id}', [LocationController::class, 'destroy']);
});


// CONTACT ROUTES
Route::post('/contacts', [ContactController::class, 'create']);
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/contacts', [ContactController::class, 'index']);
    Route::get('/contacts/{id}', [ContactController::class, 'show']);
    Route::delete('/contacts/{id}', [ContactController::class, 'destroy']);
    Route::get('/contact/search', [ContactController::class, 'search']);
});


// GALLERY ROUTES
Route::get('/gallery', [GalleryController::class, 'index']);
Route::get('/gallery/type', [GalleryController::class, 'types']);
Route::get('/gallery/{id}', [GalleryController::class, 'show']);
Route::get('/gallery', [GalleryController::class, 'search']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/gallery', [GalleryController::class, 'store']);
    Route::delete('/gallery/{id}', [GalleryController::class, 'destroy']);
});


// INSPECTION ROUTES
Route::post('/inspections', [InspectionController::class, 'store']);
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/inspections', [InspectionController::class, 'index']);
    Route::put('/inspections/{id}', [InspectionController::class, 'complete']);
    Route::patch('/inspections/{id}', [InspectionController::class, 'cancelled']);
    Route::delete('/inspections/{id}', [InspectionController::class, 'destroy']);
    Route::get('/inspections/{id}', [InspectionController::class, 'show']);
});

// CONSULTATION ROUTES
Route::post('/consultations', [ConsultationController::class, 'store']);
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/consultations', [ConsultationController::class, 'index']);
    Route::put('/consultations/{id}', [ConsultationController::class, 'complete']);
    Route::patch('/consultations/{id}', [ConsultationController::class, 'cancelled']);
    Route::delete('/consultations/{id}', [ConsultationController::class, 'destroy']);
    Route::get('/consultations/{id}', [ConsultationController::class, 'show']);
});

// REALTOR STARS ROUTES
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/realtor-stars', [RealtorStarsController::class, 'index']);
    Route::delete('/realtor-stars/{id}', [UserController::class, 'destroy']);
});
