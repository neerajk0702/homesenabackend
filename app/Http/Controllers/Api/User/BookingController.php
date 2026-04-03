<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Services\BookingService;
use App\Services\ExpertSlotService;
use Illuminate\Http\Request;
use App\Models\Booking;
use App\Http\Resources\BookingResource;
use App\Services\FirebaseService;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\Address;
use App\Models\BookingSlot;

class BookingController extends Controller
{

    public function storeBooking(Request $request)
    {
        $validator = $this->bookingValidation($request);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'code' => 422,
                'message' => $validator->errors()->first(),
                'data' => [],
            ], 422);
        }
        $address = Address::find($request->addressId);
        // Check latitude & longitude
        if (!$address || empty($address->address_lat) || empty($address->address_long)) {
            return response()->json([
                'code' => 422,
                'data' => [],
                'success' => false,
                'message' => 'Invalid address or missing location.'
            ], 422);
        }
        $query = Booking::where('user_id', auth()->id())
            ->where('type', $request->type)
            ->where('time', $request->time)
            ->where('address_id', $request->addressId)
            // apply only if not null
            ->when($request->serviceId, function ($q) use ($request) {
                $q->where('service_id', $request->serviceId);
            });
        if ($request->booking_subtype === 'single') {
            $query->where('booking_subtype', 'single')
                ->whereDate('start_date', $request->date);
        } elseif ($request->booking_subtype === 'recurring') {

            $query->where('booking_subtype', 'recurring')
                ->whereDate('start_date', $request->start_date)
                ->whereDate('end_date', $request->end_date);
        } elseif ($request->type === 'instant') {
            $query->whereDate('start_date', $request->start_date ?? now());
        }
        $existingBooking = $query->exists();
        if ($existingBooking) {
            return response()->json([
                'code' => 422,
                'data' => [],
                'status' => false,
                'message' => 'Booking already exists for this slot and user'
            ]);
        }
        $startDate = null;
        $endDate = null;
        if ($request->type === 'instant') {
            $startDate = now();
            $endDate = now();
        } elseif ($request->booking_subtype === 'single') {
            $startDate = $request->date;
            $endDate = $request->date;
        } elseif ($request->booking_subtype === 'recurring') {
            $startDate = $request->start_date;
            $endDate = $request->end_date;
        }
        $totalPrice = 0;
        $booking = Booking::create([
            'booking_code' => 'HS-' . now()->format('md') . strtoupper(Str::random(4)),
            'user_id' => auth()->id(),
            'service_id' => $request->serviceId,
            'type' => $request->type,
            'booking_subtype' => $request->booking_subtype,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'time' => $request->time,
            'status' => 'pending',
            'address_id' => $request->addressId,
            'total_price' => $request->total_price,
        ]);
        $dates = [];
        // Instant
        if ($request->type == 'instant') {
            $dates[] = now();
        }
        //  Single
        elseif ($request->booking_subtype == 'single') {
            $dates[] = Carbon::parse($request->date);
        }
        //  Recurring
        else {
            $current = Carbon::parse($request->start_date);
            $end = Carbon::parse($request->end_date);

            if ($request->recurring_type == 'daily') {
                while ($current <= $end) {
                    $dates[] = $current->copy();
                    $current->addDay();
                }
            } elseif ($request->recurring_type == 'weekly') {
                while ($current <= $end) {
                    if (in_array(strtolower($current->format('D')), $request->days)) {
                        $dates[] = $current->copy();
                    }
                    $current->addDay();
                }
            } elseif ($request->recurring_type == 'monthly') {

                if ($request->monthly_date) {
                    $current = Carbon::parse($request->start_date)->startOfMonth();

                    while ($current <= $end) {
                        $day = min($request->monthly_date, $current->daysInMonth);
                        $date = $current->copy()->day($day);

                        if ($date >= Carbon::parse($request->start_date) && $date <= $end) {
                            $dates[] = $date;
                        }

                        $current->addMonth();
                    }
                } elseif ($request->week && $request->day) {
                    $current = Carbon::parse($request->start_date)->startOfMonth();

                    while ($current <= $end) {
                        $date = $current->copy()->modify("{$request->week} {$request->day}");

                        if ($date >= Carbon::parse($request->start_date) && $date <= $end) {
                            $dates[] = $date;
                        }

                        $current->addMonth();
                    }
                }
            }
        }
        $startTime = Carbon::parse($request->time);
        $endTime = $startTime->copy()->addMinutes($request->duration);
        $dates = collect($dates)->unique()->values();
        $slots = [];
        foreach ($dates as $date) {
            $slots[] = [
                'booking_id' => $booking->id,
                'date' => $date->format('Y-m-d'),
                // 'start_time' => $request->type == 'instant' ? now()->format('H:i:s') : $startTime,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'duration' => $request->duration,
                'status' => 'pending',
                'price' => $request->price,
            ];
        }
        BookingSlot::insertOrIgnore($slots);
        // $totalPrice = collect($slots)->sum('price');

        // $booking->update([
        //     'total_price' => $totalPrice
        // ]);
        $latitude = $address->address_lat;
        $longitude = $address->address_long;
        //  SEND NOTIFICATION FOR ALL
        $firebase = new FirebaseService();
        $totalSlots = count($slots);
        $availableSlots = 0;
        foreach ($slots as $slot) {
            $experts = $this->getExperts($slot['date'], $slot['start_time'], $slot['end_time'], $latitude, $longitude);
            // Determine status
            $slotStatus = $experts->isEmpty() ? 'not_available' : 'available';
            // update slot status
            BookingSlot::where('booking_id', $booking->id)
                ->where('date', $slot['date'])
                ->update([
                    'status' => $slotStatus
                ]);
            // Update slot array for response
            $slot['status'] = $slotStatus;
            // if no expert → skip notification
            if ($experts->isEmpty()) {
                continue;
            }
            $availableSlots++;
            foreach ($experts as $expert) {
                foreach ($expert->devices as $device) {
                    if (empty($device->fcm_token)) {
                        continue;
                    }
                    // if ($device->fcm_token) {
                    $firebase->send(
                        $device->fcm_token,
                        'New Booking Request',
                        "Booking on {$slot['date']} at {$slot['start_time']} is available for you. Please respond to accept or reject.",
                        [
                            'booking_id' => (string) $booking->id,
                            'booking_code' => $booking->booking_code,
                            // 'address_id' => $address->id,
                            'date' => $slot['date'],
                            'time' => $slot['start_time'],
                            'duration' => $slot['duration'],
                            'location' => $address->address,
                            'earning' => $slot['price'] ?? 0,
                            'actions' => json_encode([
                                ['id' => 'ACCEPT', 'title' => 'Accept'],
                                ['id' => 'REJECT', 'title' => 'Reject']
                            ])
                        ],
                    );
                    // }
                }
            }
        }
        //  FINAL MESSAGE
        if ($availableSlots === 0) {
            $message = 'Booking created but no experts available right now';
        } elseif ($availableSlots === $totalSlots) {
            $message = 'Booking created & notifications sent for all slots';
        } else {
            $message = 'Booking created & notifications sent for some slots';
        }
        return response()->json([
            'status' => true,
            'code' => 200,
            'message' => $message,
            'data' => [
                'booking' => new BookingResource($booking),
            ]
        ]);

    }

    //  Get nearby + free experts
    private function getExperts($date, $startTime, $endTime, $lat, $lng)
    {
        $radiusKm = 1;
        return User::where('users.role', 'expert')
            ->where('users.status', 1)
            ->join('addresses', 'addresses.user_id', '=', 'users.id')
            ->join('expert_details', 'expert_details.user_id', '=', 'users.id')
            ->where('expert_details.is_online', true)
            ->with('devices')
            ->select('users.*')
            ->selectRaw(
                "(6371 * acos(
                        cos(radians(?)) *
                        cos(radians(addresses.address_lat)) *
                        cos(radians(addresses.address_long) - radians(?)) +
                        sin(radians(?)) *
                        sin(radians(addresses.address_lat))
                    )) AS distance",
                [$lat, $lng, $lat]
            )
            ->having('distance', '<=', $radiusKm)
            ->orderBy('distance', 'asc')
            ->whereDoesntHave('expertSlots', function ($q) use ($date, $startTime, $endTime, ) {
                $q->where('date', $date)
                    ->where('status', 'accepted')
                    ->where('start_time', '<', $endTime)
                    ->where('end_time', '>', $startTime);
            })
            ->get();
    }

    // booking by id detail

    public function getBookingById($id)
    {
        $booking = Booking::with([
            'service',
            'address',
            'slots.expert'
        ])->find($id);
        if (!$booking) {
            return response()->json([
                'code' => 422,
                'status' => false,
                'message' => 'Booking not found',
                'data' => (object) []
            ], 422);
        }
        return response()->json([
            'code' => 200,
            'status' => true,
            'message' => 'Booking retrieved successfully',
            'data' => new BookingResource($booking)
        ]);
    }
    // get auth user bookings
    public function getUserBookings(Request $request)
    {
        $query = Booking::with([
            'service',
            'address',
            'slots.expert'
        ])->where('user_id', auth()->id());
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        $bookings = $query->latest()->get();
        return response()->json([
            'code' => 200,
            'status' => true,
            'message' => $bookings->isEmpty()
                ? 'No bookings found for this user'
                : 'User bookings retrieved successfully',
            'data' => BookingResource::collection($bookings),
            // 'pagination' => [
            //     'current_page' => $bookings->currentPage(),
            //     'last_page' => $bookings->lastPage(),
            //     'per_page' => $bookings->perPage(),
            //     'total' => $bookings->total(),
            // ]
        ]);
    }

private function bookingValidation($request)
{
    return Validator::make($request->all(), [

        'addressId' => 'required|exists:addresses,id',
        'type' => 'required|in:instant,scheduled',
        'booking_subtype' => 'required_if:type,scheduled|in:single,recurring',
        'time' => 'required',
        'date' => 'required_if:booking_subtype,single|date',
        'start_date' => 'required_if:booking_subtype,recurring|date',
        'end_date' => 'required_if:booking_subtype,recurring|date|after_or_equal:start_date',
        'recurring_type' => 'required_if:booking_subtype,recurring|in:daily,weekly,monthly',

        'days' => 'required_if:recurring_type,weekly|array',
        'monthly_date' => 'nullable|integer|min:1|max:31',

        'duration' => 'required|integer|min:1',
        'price' => 'required|numeric|min:0',
        'total_price' => 'required|numeric|min:0',

    ]);
}

}

