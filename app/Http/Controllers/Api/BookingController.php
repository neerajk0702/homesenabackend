<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\Auth\BookingRequest;
use App\Models\Booking;

class BookingController extends Controller
{
    public function store(BookingRequest $request)
        {
            $otp = rand(100000, 999999);
            $booking = $request->user()->bookings()->create([
                'service_id' => $request->serviceId,
                'expert_id' => 1, // For simplicity, assigning a default expert. In real scenario, you would assign based on availability.
                'address_id' => $request->addressId,
                'scheduled_at' => $request->scheduledAt,
                'duration_hours' => $request->durationHours,
                'payment_method' => $request->paymentMethod,
                'notes' => $request->notes,
                'total_amount' => 100.00, // For simplicity, assigning a default amount. In real scenario, you would calculate based on service and duration.
                'otp_code' => $otp,
            ]);
        if(!$booking) {
            return response()->json([
                'code' => 500,
                'status' => false,
                'message' => 'Failed to create booking'
            ], 500);
        } else {
           return response()->json([
                    'code'=> 200,
                    'status'=> true, 
                    'message'=>'Booking created successfully', 
                    'body' =>  $booking
                ]);
            }
        }

        // get booking by id
    public function getBookingById($id)
    {
        $booking = Booking::with('user', 'service', 'address')->find($id);
        // $booking = Booking::find($id);
        if (!$booking) {
            return response()->json([
                'code' => 404,
                'status' => false,
                'message' => 'Booking not found'
            ], 404);
        } else {
            return response()->json([
                'code' => 200,
                'status' => true,
                'message' => 'Booking retrieved successfully',
                'body' => $booking
            ]);
        }
    }

    // get auth user bookings
    public function getUserBookings(Request $request)
    {     
         $query = auth()->user()
                ->bookings()
                ->join('services', 'bookings.service_id', '=', 'services.id')
                ->select('services.name as service_name', 'bookings.*');

            if ($request->filled('status')) {
                $query->where('bookings.status', $request->status);
             }
           $bookings = $query->latest()->paginate(10);
           if($bookings->isEmpty()) {
            return response()->json([
                'code' => 404,
                'status' => false,
                'message' => 'No bookings found for this user'
            ], 404);
           } else {
            return response()->json([
                'code' => 200,
                'status' => true,
                'message' => 'User bookings retrieved successfully',
                // 'body' => $bookings
                'body' => [
                    'current_page' => $bookings->currentPage(),
                    'per_page' => $bookings->perPage(),
                    'total_bookings' => $bookings->total(),
                    'bookings' => $bookings->items(),
                ]
            ]);
        }         
            
      }    

      // auth user cancel booking
      public function cancelBooking(Request $request, $id)
        {
          $booking = Booking::find($id);
          if (!$booking) {
              return response()->json([
                  'code' => 404,
                  'status' => false,
                  'message' => 'Booking not found'
              ], 404);
          }
  
          if ($booking->user_id !== auth()->id()) {
              return response()->json([
                  'code' => 403,
                  'status' => false,
                  'message' => 'Unauthorized to cancel this booking'
              ], 403);
          }
     // only allow cancellation for bookings with status PENDING or CONFIRMED and scheduled date is after today
        if(($booking->scheduled_at->toDateString() > now()->toDateString()) && ($booking->status === 'PENDING' || $booking->status === 'CONFIRMED') ){
            $booking->status = 'CANCELLED';
            $booking->cancel_reason = $request->reason;
            $booking->save();
            if(!$booking) {
                return response()->json([
                    'code' => 500,
                    'status' => false,
                    'message' => 'Failed to cancel booking'
                ], 500);
            } else {
            return response()->json([
                'code' => 200,
                'status' => true,
                'message' => 'Booking cancelled successfully',
                'body' => [
                    'bookingId' => $booking->id,
                    'status' => $booking->status,
                    // 'refundStatus' => 
                ]
            ]);
         }
      } else {
              return response()->json([
                  'code' => 400,
                  'status' => false,
                  'message' => 'Only PENDING or CONFIRMED bookings can be cancelled or scheduled date must be after today'
              ], 400);
          }
        }

    // auth user reschedule booking
        public function rescheduleBooking(Request $request, $id)
          {
            $request->validate([
                // 'newScheduledAt' => 'required|date|after:'. now()->addHour(),
                'newScheduledAt' => 'required|date|after:now',
            ]);
            $booking = Booking::find($id);
            if (!$booking) {
                return response()->json([
                    'code' => 404,
                    'status' => false,
                    'message' => 'Booking not found'
                ], 404);
            }
    
            if ($booking->user_id !== auth()->id()) {
                return response()->json([
                    'code' => 403,
                    'status' => false,
                    'message' => 'Unauthorized to reschedule this booking'
                ], 403);
            }
            // only allow rescheduling for bookings with status PENDING or CONFIRMED and scheduled date is after today
            if($booking->status === 'PENDING' || $booking->status === 'CONFIRMED') {
                 $oldscheduledAt = $booking->scheduled_at;
                 $booking->scheduled_at = $request->newScheduledAt;
                 $booking->save();
                    if(!$booking) {
                        return response()->json([
                            'code' => 500,
                            'status' => false,
                            'message' => 'Failed to reschedule booking'
                        ], 500);
                        } else {
                        return response()->json([
                                'code' => 200,
                                'status' => true,
                                'message' => 'Booking rescheduled successfully',
                                'body' => [
                                    'bookingId' => $booking->id,
                                    'oldTime' => $oldscheduledAt,
                                    'newTime' => $booking->scheduled_at,
                                    'status' => $booking->status,
                                ]
                          ]);
                     }
            } else {
                return response()->json([
                    'code' => 400,
                    'status' => false,
                    'message' => 'Only PENDING or CONFIRMED bookings can be rescheduled'
                ], 400);
            }
        }
        
     // auth user confirm OTP
        public function confirmOtp(Request $request, $id)   
        {
            $booking = Booking::find($id);
            if (!$booking) {
                return response()->json([
                    'code' => 404,
                    'status' => false,
                    'message' => 'Booking not found'
                ], 404);
            }
    
            if ($booking->user_id !== auth()->id()) {
                return response()->json([
                    'code' => 403,
                    'status' => false,
                    'message' => 'Unauthorized to confirm OTP for this booking'
                ], 403);
            }
            // only allow OTP confirmation for bookings with status CONFIRMED
            if ($booking->otp_code === $request->otpCode && $booking->status === 'CONFIRMED') {
                $booking->status = 'ONGOING';
                $booking->check_in_time = now()->format('Y-m-d H:i');
                $booking->save();
                    if(!$booking) {
                        return response()->json([
                            'code' => 500,
                            'status' => false,
                            'message' => 'Failed to confirm OTP'
                        ], 500);
                    } else {
                    return response()->json([
                        'code' => 200,
                        'status' => true,
                        'message' => 'OTP confirmed successfully',
                        //  'body' => $booking->only(['id','status','check_in_time'])
                         'body' => [
                                    'bookingId' => $booking->id,
                                    'checkInTime' => $booking->check_in_time,
                                    'status' => $booking->status,
                                ]
                          ]);
                 }
            } else {
                return response()->json([
                    'code' => 400,
                    'status' => false,
                    'message' => 'Invalid OTP code or booking status is not CONFIRMED'
                ], 400);
            }
        }
}
