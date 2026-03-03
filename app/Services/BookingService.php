<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingSlot;
use App\Models\Address;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;


class BookingService
{
    protected $firebase;

    public function __construct(FirebaseService $firebase)
    {
        $this->firebase = $firebase;
    }

 // Create new booking with multiple slots
    public function createBooking($request)
    {
          //  Validate request
            $request->validate([
                'addressId' => 'required|exists:addresses,id',
                'serviceId' => 'required|exists:services,id',
                // 'durationHours' => 'required|integer|min:1',
            ]);    
             $address = Address::find($request->addressId);

            // Check latitude & longitude
            if (!$address->address_lat || !$address->address_long) {
                return response()->json([
                    'success' => false,
                    'message' => 'Selected address does not have valid location. Please update address.'
                ], 422);
            }
        return DB::transaction(function () use ($request) {     
            $booking = Booking::create([
                'booking_code'=> 'BK' . now()->format('md') . strtoupper(Str::random(4)),
                'user_id' => auth()->id(),
                'service_id' => $request->serviceId,
                'address_id' => $request->addressId,
                'booking_date'=> now()->toDateString() //create date of booking
            ]);


            foreach ($request->slots as $slotData) {
                 $startTime = Carbon::parse($slotData['start_time']);
                 $endTime = $startTime->copy()->addMinutes($slotData['duration']);

                $slot = BookingSlot::create([
                    'booking_id' => $booking->id,
                    'booking_date' => $slotData['date'], // scheduled date
                    'start_time' => $startTime ,
                    'end_time' =>  $endTime,
                    'duration' => $slotData['duration'],
                ]);

                $experts = $this->getNearbyExperts($request->addressId);
                foreach ($experts as $expert) {
                        // $this->firebase->sendNotification(
                  if ($expert->fcm_token) {
                       $this->firebase->send(
                            $expert->fcm_token,
                            "New Booking",
                            "New slot available on date {$slot->booking_date} at time {$slot->start_time} to {$slot->end_time}",
                            ['slot_id' => $slot->id]
                            );
                      } else {
                            // Log or handle experts without FCM token
                            \Log::warning("Expert ID {$expert->id} has no FCM token, cannot send notification.");
                       }
                  }
            }

            return $booking;
        });
    }

    // nearby expert finder
    public function getNearbyExperts($addressId, $radiusKm = 10)
     {
                // Get address
            $address = Address::findOrFail($addressId);
            $latitude = $address->address_lat;
            $longitude = $address->address_long;
            //  Haversine Formula to calculate distance and find experts within radius
            $experts = User::where('users.role', 'expert')
                    ->where('users.is_available', 1)
                    ->join('addresses', 'addresses.user_id', '=', 'users.id')
                    ->select('users.*')
                    ->selectRaw(
                    "(6371 * acos(
                        cos(radians(?)) *
                        cos(radians(addresses.address_lat)) *
                        cos(radians(addresses.address_long) - radians(?)) +
                        sin(radians(?)) *
                        sin(radians(addresses.address_lat))
                    )) AS distance",
                    [$latitude, $longitude, $latitude]
                )
                ->having('distance', '<=', $radiusKm)
                ->orderBy('distance', 'asc')
                ->get();

            return $experts;
    }

    // Cancel booking slot
    public function cancelSlots($request, $slotId)
     {
        $slot = BookingSlot::with('booking', 'expert')->find($slotId);
            if (!$slot) {
                return response()->json([
                    'status' => false,
                    'message' => 'Booking Slot not found'
                ], 200);
             }
          //  Check ownership
            if ($slot->booking->user_id !== auth()->id()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized to cancel this booking slot'
                ], 403);
            }

            //  Check already completed or ongoing slot
                if ($slot->status === 'completed' || $slot->status === 'ongoing' || $slot->status === 'cancelled') {
                    return response()->json([
                        'status' => false,
                        'message' => 'Completed, Ongoing, and Cancelled slots cannot be cancelled'
                    ], 422);
                }

                // Prevent cancel within 2 hours
                $slotDateTime = Carbon::parse($slot->booking_date . ' ' . $slot->start_time);
                $cancelLimit = $slotDateTime->copy()->subHours(2);

                if (now()->greaterThanOrEqualTo($cancelLimit)) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Cancellation window closed. You cannot cancel within 2 hours of slot time.'
                    ], 422);
                }

               DB::transaction(function () use ($slot) {
                    // Cancel slot
                    $slot->update([
                        'status' => 'cancelled'
                    ]);

                // If all slots cancelled → cancel booking
                $allCancelled = $slot->booking->bookingSlots()
                        ->where('status', '!=', 'cancelled')
                        ->count() === 0;
          
                if ($allCancelled) {
                    $slot->booking->update([
                        'status' => 'cancelled'
                    ]);
                }

                //  Notify expert if assigned (Direct Firebase Call)
                if ($slot->expert && $slot->expert->fcm_token) {
                        $firebase->send(
                        $slot->expert->fcm_token,
                        'Booking Cancelled',
                        'Your assigned slot on ' . $slot->booking_date . ' at ' . $slot->start_time . ' has been cancelled by the user.',
                    );
                }
             });
              return response()->json([
                 'code' => 200,
                 'status' => true,
                 'message' => 'Slot cancelled successfully',
                 'data' => [
                    'bookingId' => $slot->booking->id,
                    'slotId' => $slot->id,
                    'status' => $slot->status, //slot status after cancellation
                    // 'refundStatus' => 
                  ]
              ],200);
        }


        //  Reschedule booking slot
     public function rescheduleSlots($request, $slotId)
     {
          $request->validate([
                'newScheduledAt' => 'required|date|after:' . now()->addHour(2)->toDateTimeString(), // Reschedule must be at least 2 hours in the future
                // 'start_time' => 'required',
                'duration' => 'required|integer'
              ]);

                $dt = Carbon::parse($request->newScheduledAt);
                $newSchedukedDate = $dt->toDateString();
                $slotStartTime   = $dt->toTimeString();

            $slot = BookingSlot::with('booking', 'expert')->find($slotId);
            if (!$slot) {
                return response()->json([
                    'status' => false,
                    'message' => 'Booking Slot not found'
                ], 200);
               }

          //  Check ownership
            if ($slot->booking->user_id !== auth()->id()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized to reschedule this booking slot'
                 ], 403);
                }
            //  Check already completed or ongoing slot
                if ($slot->status === 'completed' || $slot->status === 'ongoing' || $slot->status === 'cancelled') {
                    return response()->json([
                        'status' => false,
                        'message' => 'Completed,Ongoing and Cancelled slots cannot be rescheduled'
                    ], 422);
                }

            //  Prevent reschedule within 2 hours of OLD slot
                $oldStart = Carbon::parse($slot->booking_date . ' ' . $slot->start_time);
                $rescheduleLimit = $oldStart->copy()->subHours(2);
                if (now()->greaterThanOrEqualTo($rescheduleLimit)) {
                    return response()->json([
                        'message' => 'Cannot reschedule within 2 hours of slot time'
                    ], 422);
                }
                //  New Slot Time
                //  $newStart = Carbon::parse($request->newScheduledAt . ' ' . $request->start_time);
                 $newStart = Carbon::parse($newSchedukedDate . ' ' . $slotStartTime);;
                 $newEnd = $newStart->copy()->addMinutes($request->duration);  
                  // Expert Conflict Check
             if ($slot->expert_id) {
                   $conflict = BookingSlot::where('expert_id', $slot->expert_id)
                        ->where('booking_date', $newSchedukedDate)
                        ->where('id', '!=', $slot->id)
                        ->where('status', '!=', 'cancelled')
                        ->where(function ($query) use ($newStart, $newEnd) {
                            $query->where('start_time', '<', $newEnd->format('H:i:s'))
                                  ->where('end_time', '>', $newStart->format('H:i:s'));
                             })
                             ->exists();

                    if ($conflict) {
                        return response()->json([
                            'message' => 'Expert is not available at this time'
                        ], 422);
                    }
                 }

                DB::transaction(function () use ($slot, $newStart, $newEnd, $request) {
                        $slot->update([
                                'booking_date' => $newStart->toDateString(),
                                'start_time' => $newStart->format('H:i:s'),
                                'end_time' => $newEnd->format('H:i:s'),
                                'duration' => $request->duration,
                                'status' => 'accepted'
                            ]);
                        });

                  //  Notify Expert
                      if ($slot->expert && $slot->expert->fcm_token) {
                         $firebase->send(
                             $slot->expert->fcm_token,
                            'Booking Rescheduled',
                            'Your assigned slot has been rescheduled by the user. New date: ' . $slot->booking_date . ', New time: ' . $slot->start_time . '. Please check the app for details.',
                         );
                     }

                    return response()->json([
                                'status' => true,
                                'message' => 'Slot rescheduled successfully',
                                'data' => [
                                    'bookingId' => $slot->booking->id,
                                    'slotId' => $slot->id,
                                    'oldTime' => $oldStart->toDateTimeString(),
                                    'newTime' => $slot->booking_date . ' ' . $slot->start_time,               
                                    'status' => $slot->status
                                 ]
                            ]);   
             
        }

        // Confirm OTP for a slot
     public function verifyOtp($request, $slotId)   
     {
            $request->validate([
                'otpCode' => 'required|digits:6'
            ]);

            $slot = BookingSlot::with('booking', 'expert')->find($slotId);
            if (!$slot) {
                return response()->json([
                    'status' => false,
                    'message' => 'Booking Slot not found'
                ], 200);
             }

             // Check ownership only expert can confirm OTP
            if ($slot->expert_id !== auth()->id()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized to confirm OTP for this booking slot'
                ], 403);
              } 
              // Block if attempts already 5
                if ($slot->otp_attempts >= 5) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Maximum OTP attempts exceeded.'
                    ], 422);
                }
            // Check OTP
            if ($slot->otp_code !== $request->otpCode) {       
                $slot->increment('otp_attempts');  // Increase attempt count
                $attemptsLeft = 5 - $slot->otp_attempts;
                return response()->json([
                    'status' => false,
                     'message' => 'Invalid OTP. Attempts left: ' . $attemptsLeft

                ], 422);
            }

            // Mark slot as ongoing
            $slot->update([
                'otp_verified' => true,
                'otp_attempts' => 0,
                'status' => 'ongoing',
                'check_in_time' => now()->toDateTimeString()
            ]);

             return response()->json([
                 'status' => true,
                 'message' => 'OTP confirmed, slot marked as ongoing',
                 'data' => [
                    'bookingId' => $slot->booking->id,
                    'slotId' => $slot->id,
                    'status' => $slot->status,
                    'checkInTime' => $slot->check_in_time
                  ]
              ],200);
        }
                      
}