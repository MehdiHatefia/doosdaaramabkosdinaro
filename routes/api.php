<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdvertisementController;
use App\Http\Controllers\BankController;
use App\Http\Controllers\BookmarkController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\UserVerificationController;
use Illuminate\Support\Facades\Route;

Route::get('advertisements', [AdvertisementController::class, 'index']);
Route::get('advertisements/{id}', [AdvertisementController::class, 'show']);
Route::post('advertisements', [AdvertisementController::class, 'store'])
    ->middleware(['auth:sanctum', 'verified.user']);
Route::post('advertisements/{advertisement}/contact', [AdvertisementController::class, 'contact'])
    ->middleware(['auth:sanctum', 'verified.user']);
Route::get('banks', [BankController::class, 'index']);
Route::get('banks/{id}/plans', [BankController::class, 'plans']);
Route::get('locations/provinces', [LocationController::class, 'provinces']);
Route::get('locations/{provinceId}/cities', [LocationController::class, 'cities']);

Route::prefix('auth')->group(function () {
    Route::post('send-otp', [AuthController::class, 'sendOtp']);
    Route::post('verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('complete-registration', [AuthController::class, 'completeRegistration']);
    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
});

Route::middleware('auth:sanctum')->prefix('auth')->group(function () {
    Route::get('profile', [ProfileController::class, 'show']);
    Route::put('profile', [ProfileController::class, 'update']);
    Route::post('profile/avatar', [ProfileController::class, 'uploadAvatar']);
    Route::post('kyc', [ProfileController::class, 'submitKyc']);
    Route::get('kyc/status', [ProfileController::class, 'kycStatus']);
});

Route::middleware('auth:sanctum')->prefix('user')->group(function () {
    Route::get('profile', [ProfileController::class, 'show']);
    Route::put('profile', [ProfileController::class, 'update']);
    Route::post('profile/avatar', [ProfileController::class, 'uploadAvatar']);
    Route::get('profile/avatar', [ProfileController::class, 'showAvatar'])->name('user.profile.avatar');
    Route::post('kyc/submit', [ProfileController::class, 'submitKyc']);
    Route::get('kyc/status', [ProfileController::class, 'kycStatus']);
    Route::get('kyc/history', [ProfileController::class, 'kycHistory']);
    Route::get('kyc/{verificationId}/details', [ProfileController::class, 'kycDetails'])->name('user.kyc.details');
    Route::get('kyc/{verificationId}/documents/{type}', [ProfileController::class, 'kycDocument'])->name('user.kyc.document');
});

Route::middleware('auth:sanctum')->prefix('admin')->group(function () {
    Route::get('ads', fn () => response()->json(['data' => []]))->middleware(['role.audit', 'role:super-admin|admin|operator']);
    Route::get('verifications', [UserVerificationController::class, 'index'])->name('admin.verifications.index')->middleware(['role.audit', 'permission:review-kyc']);
    Route::get('verifications/{verification}', [UserVerificationController::class, 'show'])->name('admin.verifications.show')->middleware(['role.audit', 'permission:review-kyc']);
    Route::get('verifications/{verification}/documents/{field}', [UserVerificationController::class, 'document'])->name('admin.verifications.document')->middleware(['role.audit', 'permission:review-kyc']);
    Route::patch('verifications/{verification}/review', [UserVerificationController::class, 'review'])->name('admin.verifications.review')->middleware(['role.audit', 'permission:review-kyc']);
});

Route::middleware('auth:sanctum')->prefix('operator')->group(function () {
    Route::get('ads', fn () => response()->json(['data' => []]))->middleware(['role.audit', 'role:super-admin|operator']);
});

Route::middleware(['auth:sanctum', 'verified.user'])->prefix('user')->group(function () {
    Route::get('ads', [AdvertisementController::class, 'userAds'])->middleware(['role.audit', 'role:buyer|seller']);
    Route::get('advertisements', [AdvertisementController::class, 'userAds'])->middleware(['role.audit', 'role:buyer|seller']);
    Route::put('ads/{advertisement}', [AdvertisementController::class, 'update'])->middleware('verified.user');
    Route::put('advertisements/{advertisement}', [AdvertisementController::class, 'update'])->middleware('verified.user');
    Route::patch('ads/{advertisement}/status', [AdvertisementController::class, 'changeStatus']);
    Route::patch('advertisements/{advertisement}/status', [AdvertisementController::class, 'changeStatus']);
    Route::delete('ads/{advertisement}', [AdvertisementController::class, 'destroy']);
    Route::delete('advertisements/{advertisement}', [AdvertisementController::class, 'destroy']);
    Route::get('bookmarks', [BookmarkController::class, 'index']);
    Route::post('bookmarks/{advertisementId}', [BookmarkController::class, 'toggle']);
    Route::post('advertisements/{advertisement}/bookmark', [BookmarkController::class, 'toggleByModel']);
    Route::delete('bookmarks/{advertisementId}', [BookmarkController::class, 'destroy']);
});