<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\User\UserController;
use App\Http\Controllers\Api\User\ServiceController;
use App\Http\Controllers\Api\User\BookingController;
use App\Http\Controllers\Api\User\LocationController;
use App\Http\Controllers\Api\User\ReviewController;
use App\Http\Controllers\Api\User\PaymentController;
use App\Http\Controllers\Api\User\UserSupportController;
use App\Http\Controllers\Api\User\UserHomeController;
use App\Http\Controllers\Api\User\CmsPageController;
use App\Http\Controllers\Api\User\AddressController;
use App\Http\Controllers\Api\User\InvoiceController;
use App\Http\Controllers\Api\Expert\EmergencyContactController;
use App\Http\Controllers\Api\Expert\BookingController as ExpertBookingController;
use App\Http\Controllers\Api\Expert\TrainingCenterController;
use App\Http\Controllers\Api\Expert\ExpertSOSController;
use App\Http\Controllers\Api\Expert\ExpertCmsPageController;
use App\Http\Controllers\Api\Expert\ExpertController;
use App\Http\Controllers\Api\User\ServiceNotifyController;
use App\Http\Controllers\Api\User\CouponController;
use App\Http\Controllers\Api\Expert\RazorpayController;




Route::post('login', [AuthController::class, 'sendOtp']);
Route::post('verifyotp', [AuthController::class, 'verifyOtp']);
Route::post('resend-otp', [AuthController::class, 'resendOtp']);

// service route
Route::get('services', [ServiceController::class, 'getServices']);
Route::get('services/{id}', [ServiceController::class, 'getServiceById']);
Route::get('time-slots', [ServiceController::class, 'timeSlot']);
Route::get('instant-booking-duration', [ServiceController::class, 'instantBookingSetting']);
Route::get('/cms/{slug}', [CmsPageController::class, 'getCmsPage']);
Route::get('/cms/expert/{slug}', [ExpertCmsPageController::class, 'getCmsPage']);

// athenticated routes
Route::middleware('auth:sanctum')->group(function () {
     Route::middleware('role:user,expert')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('delete-account', [AuthController::class, 'deleteAccount']);
        // user details
        Route::post('profile', [UserController::class, 'profile']);
        Route::get('user-details', [UserController::class, 'userDetails']);
        // -------- address route  user/expert both can use this route -----------
        Route::post('save-address', [AddressController::class, 'saveAddress']);
        Route::post('update-address/{id}', [AddressController::class, 'updateAddress']);
        Route::get('address-list', [AddressController::class, 'addressList']);
        Route::delete('delete-address/{id}', [AddressController::class, 'deleteAddress']);
        // cms page route

     });
   
    Route::get('user-home', [UserHomeController::class, 'userHome']);
    // boonking route
    Route::post('booking/create', [BookingController::class, 'storeBooking']);
    Route::post('booking/payment', [BookingController::class, 'handlePayment']);
    Route::post('slots/{id}/accept', [BookingController::class, 'accept']); // Expert accepts a specific slot
    Route::get('bookings', [BookingController::class, 'userSlotBookingListing']);
    Route::get('booking/{id}', [BookingController::class, 'userSlotBookingById']);
    // Route::get('booking/{id}', [BookingController::class, 'getBookingById']);
    // Route::put('/slots/{id}/cancel', [BookingController::class, 'cancelBookingSlots']);
    // Route::put('/booking/{id}/cancel', [BookingController::class, 'cancelBookingSlot']);
    // Route::put('slots/{id}/reschedule', [BookingController::class, 'rescheduleBookingSlots']);
    // Route::post('slots/{id}/confirmOTP', [BookingController::class, 'confirmOtp']);
    Route::put('slot/{id}/cancel', [BookingController::class, 'cancelBookingSlot']);
    Route::put('booking/{id}/cancel', [BookingController::class, 'cancelBooking']);
    Route::get('booking-cancel-reasons', [BookingController::class, 'bookingCancelReason']);
    // Route::post('booking/payment', [BookingController::class, 'handlePayment']);
    Route::post('reschedule-booking', [BookingController::class, 'rescheduleBooking']);
    Route::post('reschedule-booking-slot', [BookingController::class, 'rescheduleBookingSlot']);
    
    
    //location route
    Route::get('location/available-services', [LocationController::class, 'nearbyServices']);  
    Route::post('location/update', [LocationController::class, 'updateLocation']); // user update location
    Route::post('location/expert-update', [LocationController::class, 'expertUpdateLocation']); // expert update location
    Route::get('location/expert-tracking/{slotId}', [LocationController::class, 'expertTracking']);
    // rating and review
    Route::post('rating/slot/{id}', [ReviewController::class, 'submitReview']);
    Route::get('rating/user', [ReviewController::class, 'getUserGivenReviews']);  

    //payment route
    Route::get('payment/methods',[PaymentController::class, 'paymentMethods']);
    Route::post('payment/pay',[PaymentController::class, 'initiatePayment']);
    Route::post('payment/verify',[PaymentController::class, 'initiatePayment']);
    // contact-us
    Route::post('contact-us',[UserSupportController::class, 'store']);
    // service Available
    Route::post('service-available',[ServiceController::class, 'serviceAvailable']);
    // generate invoice
    Route::get('booking/{id}/invoice', [InvoiceController::class, 'generateBookingInvoice']);
    // Route::get('/slot-booking/{id}/invoice', [InvoiceController::class, 'generateSlotInvoice']);
     Route::post('notify-me',[ServiceNotifyController::class, 'storeNotifyRequest']);
     Route::get('refund-status/{bookingSlotId}',[BookingController::class, 'refundStatus']);
    //  coupon
     Route::post('apply-coupon',[CouponController::class, 'applyCoupon']);
     Route::get('coupon-list',[CouponController::class, 'couponList']);
     
     Route::get('upcoming-booking',[UserHomeController::class, 'upcomingBooking']);
     Route::get('home-rating',[UserHomeController::class,'homePageRating']);
     Route::post('skip-rating-popup/{slotId}', [UserHomeController::class, 'skipRatingPopup']);
     Route::put('slot/{id}/complete', [BookingController::class, 'completeBookingSlot']);
    /*-------------  expert api ---------------------------------------**/
     Route::middleware('role:expert')->prefix('expert')->group(function(){
        
        Route::post('profile',[ExpertController::class,'profile']);
        Route::post('is-online-status-update', [ExpertController::class, 'isOnlineStatusUpdate']);
        Route::get('earning-history', [ExpertController::class, 'earningHistory']);
        // Route::post('details',[ExpertController::class,'storeDetails']);
        Route::post('save-emergency-contacts',[EmergencyContactController::class,'storeEmergencyContacts']);
        Route::get('emergency-contact-list',[EmergencyContactController::class,'getEmergencyContacts']);
        Route::delete('emergency-contact/{id}',[EmergencyContactController::class,'deleteEmergencyContact']);
        Route::put('update-emergency-contact/{id}',[EmergencyContactController::class,'updateEmergencyContact']);
            // booking
        Route::get('bookings', [ExpertBookingController::class, 'bookingSlotList']);
        Route::get('booking/{id}', [ExpertBookingController::class, 'bookingSlotDetail']);
        Route::post('booking-slot/accept', [ExpertBookingController::class, 'acceptSlot']);
        Route::post('booking-slot/reject', [ExpertBookingController::class, 'rejectSlot']);
        Route::get('upcoming-booking', [ExpertBookingController::class, 'upcomingBooking']); 
      
        Route::get('training-centers', [TrainingCenterController::class, 'trainingCenterList']);
        // expert sos
        Route::post('sos', [ExpertSOSController::class, 'sendSOS']);
        Route::post('slots/{id}/verify-otp', [ExpertBookingController::class, 'verifyOtp']);
        Route::get('booking-reject-reason', [ExpertBookingController::class, 'bookingRejectReason']);
        // Route::put('slot/{id}/complete', [ExpertBookingController::class, 'completeBookingSlot']);
    });

        // Route::post('/generate-qr', [RazorpayController::class, 'generateQr']);
        // Route::get('/check-payment/{qrId}', [RazorpayController::class, 'checkPayment']);
        // Route::post('/razorpay-webhook', [RazorpayController::class, 'webhook']);

});
        Route::post('/generate-qr', [RazorpayController::class, 'generateQr']);
        Route::get('/check-payment/{qrId}', [RazorpayController::class, 'checkPayment']);
        Route::post('/razorpay-webhook', [RazorpayController::class, 'webhook']);
