<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\BookingController;

// Route::post('send-otp', [AuthController::class, 'sendOtp']);
Route::post('login', [AuthController::class, 'sendOtp']);
Route::post('verifyotp', [AuthController::class, 'verifyOtp']);
// service route
Route::get('services', [ServiceController::class, 'getServices']);
Route::get('services/{id}', [ServiceController::class, 'getServiceById']);
// athenticated routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    // user details and address
    Route::get('user-details', [AuthController::class, 'userDetails']);
    Route::post('save-address', [AuthController::class, 'saveAddress']);
    Route::get('address-list', [AuthController::class, 'addressList']);
    // boonking route
    Route::post('/booking/create', [BookingController::class, 'store']);
    Route::get('/booking/user', [BookingController::class, 'getUserBookings']);
    Route::get('/booking/{id}', [BookingController::class, 'getBookingById']);
    Route::put('/booking/{id}/cancel', [BookingController::class, 'cancelBooking']);
    Route::put('/booking/{id}/reschedule', [BookingController::class, 'rescheduleBooking']);
    Route::post('/booking/{id}/confirmOTP', [BookingController::class, 'confirmOtp']);
   

});
