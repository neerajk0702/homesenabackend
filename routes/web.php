<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\BookingController;
use App\Http\Controllers\Admin\ExpertController;
use App\Http\Controllers\Admin\TrainingController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\ServiceVariantController;
use App\Http\Controllers\Admin\TimeSlotController;
use App\Http\Controllers\Admin\CmsPagesController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\HomePromotionController;
use App\Http\Controllers\Admin\InstantBookingController;
use App\Http\Controllers\Admin\UserSupportController;
use App\Http\Controllers\Admin\ExpertSupportController;
use App\Http\Controllers\Admin\ReferEarnController;
use App\Http\Controllers\Admin\ReferEarnSettingController;
use App\Http\Controllers\Admin\ServiceLocationController;
use App\Http\Controllers\Admin\ExpertSosController;
use App\Http\Controllers\Admin\BookingCancelReasonController;
use App\Http\Controllers\Admin\BookingRejectReasonController;
use App\Http\Controllers\Admin\ServiceNotifyController;
use App\Http\Controllers\Admin\PushNotificationController;
use App\Http\Controllers\Admin\InvoiceController;
use App\Http\Controllers\Frontend\FrontendController;
use App\Http\Controllers\Frontend\UserController as FrontendUserController;


/****************** Frontend Routes ******************/
Route::get('/', function () {
    return view('frontend.pages.home');
});

Route::get('/delete-account', function () {
return view('frontend.pages.delete-account');
})->name('delete.account');
Route::post('/delete-account', [FrontendUserController::class, 'deleteAccountWeb'])->name('web.delete.account');
Route::get('/support', function () {
    return view('frontend.pages.support');
})->name('support');
// Route::get('/delete-account', [FrontendUserController::class, 'deleteAccount'])->name('delete.account');


/*****        Admin Routes *************/
Route::get('admin/login', function () {
    // If already logged in AND admin
    if (auth()->check() && auth()->user()->role === 'admin') {
        return redirect('/admin/dashboard');
    }
    return view('admin.login');
})->name('admin.login');
Route::post('admin/login', [AuthController::class, 'adminLogin']);
/* forgot password  */
Route::get('/admin/forgot-password', [AuthController::class, 'showForgot'])->name('admin.forgot');
Route::post('/admin/otp', [AuthController::class, 'sendOtp'])->name('admin.sendOtp');
Route::get('/admin/otp', [AuthController::class, 'showOtp'])->name('admin.otp');
Route::post('/admin/verify-otp', [AuthController::class, 'verifyOtp'])->name('admin.verifyOtp');
Route::get('/admin/reset-password', [AuthController::class, 'showResetPassword'])->name('admin.resetPassword');
Route::post('/admin/reset-password', [AuthController::class, 'resetPassword'])->name('admin.resetPasswordSubmit');
/*  ----------------- admin   ---------------------*/
Route::middleware(['admin'])->prefix('admin')->name('admin.')->group(function () {
    // Admin Change Password Routes
    Route::get('/change-password', [AuthController::class, 'showChangePasswordForm'])->name('change_password');
    Route::post('/change-password', [AuthController::class, 'updatePassword'])->name('update_password');
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/profile', [AuthController::class, 'profile'])->name('profile');
    Route::post('/profile/update', [AuthController::class, 'updateProfile'])->name('profile.update');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    // Users 
    Route::resource('users', UserController::class);
    Route::delete('/admin/device/{id}', [UserController::class, 'deleteDevice'])->name('device.delete');
    Route::resource('experts', ExpertController::class);
    Route::post('update-approve-status', [ExpertController::class, 'updateApproveStatus']);
    // Route::get('expert/view/{id}', [ExpertController::class, 'view'])->name('view.expert');
    Route::resource('services', ServiceController::class);
    Route::resource('bookings', BookingController::class);
    Route::resource('instant_bookings', InstantBookingController::class);
    Route::resource('training_centers', TrainingController::class);
    Route::resource('service_variants', ServiceVariantController::class);
    Route::resource('time_slots', TimeSlotController::class);
    Route::resource('payments', PaymentController::class);
    Route::resource('cms_pages', CmsPagesController::class);
    Route::resource('reviews', ReviewController::class);
    Route::get('reviews-expert', [ReviewController::class, 'expertIndex'])->name('reviews.expert_index');
    Route::resource('home_promotion', HomePromotionController::class);
    Route::resource('user_supports', UserSupportController::class);
    Route::resource('expert_supports', ExpertSupportController::class);
    Route::resource('refer_earn', ReferEarnController::class);
    Route::resource('refer_earn_settings', ReferEarnSettingController::class);
    Route::resource('service_locations', ServiceLocationController::class);
    Route::resource('service_notify', ServiceNotifyController::class);
    Route::resource('expert_sos', ExpertSosController::class);
    Route::resource('push_notifications', PushNotificationController::class);
    Route::resource('booking_cancel_reasons', BookingCancelReasonController::class);
    Route::resource('booking_reject_reasons', BookingRejectReasonController::class);
    Route::get('bookings/{id}/assign-expert', [BookingController::class, 'assignExpertPage'])
        ->name('bookings.assignExpertPage');

    Route::post('bookings/{id}/assign-expert', [BookingController::class, 'assignExpertSubmit'])
        ->name('bookings.assignExpertSubmit');
    Route::get('bookings/{id}/slot-logs', [BookingController::class, 'slotLogs'])
        ->name('bookings.slot_logs');
    Route::get('bookings/{id}/slot-notifications', [BookingController::class, 'slotNotifications'])
        ->name('bookings.slot_notifications');
    Route::get('/download-invoice/{id}', [InvoiceController::class, 'bookingInvoice'])
        ->name('download.invoice');
    Route::post('/refund/slot/{id}', [BookingController::class, 'refundBookingSlot'])->name('refund.process');
    

});
Route::get('page/{slug}', [FrontendController::class, 'page'])->name('page');
