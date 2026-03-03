<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BookingService;
use App\Services\ExpertSlotService;
use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\BookingSlot;
use App\Http\Resources\BookingResource;
use App\Services\FirebaseService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;


class BookingController extends Controller
{
    protected $bookingService;
    protected $expertService;
    protected $firebase;

    public function __construct(
        BookingService $bookingService,
        ExpertSlotService $expertService,
        FirebaseService $firebase
      ){
        $this->bookingService = $bookingService;
        $this->expertService = $expertService;
        $this->firebase = $firebase; 
     }

     // Create new booking with multiple slots
    public function store(Request $request)
    {
        return $this->bookingService->createBooking($request);
    }

    // Expert accepts a slot
    public function accept($slotId)
    {
        return $this->expertService->acceptSlot($slotId);

        // return response()->json([
        //     'status' => true,
        //     'message' => 'Slot Accepted',
        //     'data' => $slot
        // ],200);
    }

    // get booking by id //need to checked
    public function getBookingById($id)
    {
        $booking = Booking::with([
                        'service',
                        'address',
                        'bookingSlots.expert'
                        ])->find($id);
        if (!$booking) {
            return response()->json([
                // 'code' => 404,
                'status' => false,
                'message' => 'Booking not found'
            ], 200);
        } else {
            return response()->json([
                'code' => 200,
                'status' => true,
                'message' => 'Booking retrieved successfully',
                'data'   => new BookingResource($booking)
            ],200);
        }      
    }

      // get auth user bookings
    public function getUserBookings(Request $request)
     { 
         $query  = Booking::with([
                        'service',
                        'address',
                        'bookingSlots.expert'
                        ])->where('user_id', auth()->id());
         if ($request->status) {
                $query->where('status', $request->status);
             }
        $bookings = $query->latest()->paginate(10);

        if($bookings->isEmpty()) {
            return response()->json([
                // 'code' => 404,
                'status' => false,
                'message' => 'No bookings found for this user'
            ], 200);
           } else {
            return response()->json([
                'status' => true,
                'message' => 'User Bookings retrieved successfully',
                'data' => BookingResource::collection($bookings)
            ],200);
           }
      }

         // auth user cancel booking slot
      public function cancelBookingSlots(Request $request, $slotId)
       {
           return $this->bookingService->cancelSlots($request, $slotId);
        
       }

         // auth user reschedule booking
      public function rescheduleBookingSlots(Request $request, $slotId)
        {
            return $this->bookingService->rescheduleSlots($request, $slotId);

        }

        public function confirmOtp(Request $request, $slotId)   
        {
            return $this->bookingService->verifyOtp($request, $slotId);
        }

}
