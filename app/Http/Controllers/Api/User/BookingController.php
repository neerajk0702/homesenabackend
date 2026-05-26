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
use App\Models\BookingCancelReason;
use App\Http\Resources\BookingSlotResource;
use App\Http\Resources\UserBookingSlotResource;
use App\Models\Refund;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\ReferEarnSetting;
use App\Models\Coupon;
use App\Models\CouponHistory;

class BookingController extends Controller
{

    public function storeBooking(Request $request)
    {
        $validator = $this->validateBookingRequest($request);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'code' => 422,
                'message' => $validator->errors()->first(),
                'data' => (object) [],
            ], 422);
        }
        if ($request->date == Carbon::today()->toDateString()) {
            $selectedDateTime = Carbon::parse($request->date . ' ' . $request->time);
            if ($selectedDateTime->lt(Carbon::now())) {
                return response()->json([
                    'status' => false,
                    'code' => 422,
                    'message' => 'Time cannot be in the past.',
                    'data' => (object) [],
                ], 422);
            }
        }
        // payment method
        $request->merge([
            'payment_method' => match ($request->payment_method) {
                1 => 'upi',
                2 => 'cod',
            // default => null
            }
        ]);

        if (!$request->payment_method) {
            return response()->json([
                'status' => false,
                'code' => 422,
                'message' => 'Invalid payment method',
                'data' => (object) [],
            ], 422);
        }

        // validate address ownership and location
        $address = Address::where('id', $request->addressId)
            ->where('user_id', auth()->id())
            ->first();
        if (!$address || empty($address->address_lat) || empty($address->address_long)) {
            return response()->json([
                'code' => 422,
                'data' => (object) [],
                'status' => false,
                'message' => 'Invalid address or missing location.'
            ], 422);
        }

        if ($this->bookingExists($request)) {
            return response()->json([
                'code' => 422,
                'data' => (object) [],
                'status' => false,
                'message' => 'Booking already exists for this slot and user'
            ]);
        }

        try {
            // TRANSACTION START

            $booking = DB::transaction(function () use ($request) {
                $originalAmount = $request->total_price;
                $discountAmount = 0;
                $coupon = null;
                if ($request->filled('coupon_code')) {
                    $coupon = Coupon::where('code', $request->coupon_code)
                        ->where('status', 1)
                        ->first();
                    if ($coupon) {
                        // percentage coupon
                        if ($coupon->discount_type == 'percentage') {
                            $discountAmount = ($originalAmount * $coupon->value) / 100;
                        } else {
                            // fixed coupon
                            $discountAmount = $coupon->value;
                        }
                    }
                }
                $totalAmount = max($originalAmount - $discountAmount, 0);
                /*
                |--------------------------------------------------------------------------
                | ADD REQUEST VALUES
                |--------------------------------------------------------------------------
                */
                $request->merge([
                    'original_price' => $originalAmount,
                    'discount_price' => $discountAmount,
                    'total_price' => $totalAmount,
                    'coupon_id' => $coupon ? $coupon->id : null,
                    'coupon_code' => $coupon ? $coupon->code : null,
                ]);
                $booking = $this->createBooking($request);
                // for cod payment method
                if ($request->payment_method === 'cod') {
                    $booking->update([
                        'status' => 'confirmed',
                    ]);
                }
                $dates = $this->generateBookingDates($request);
                $slots = $this->generateBookingSlots($booking, $dates, $request, false);
                BookingSlot::insertOrIgnore($slots);
                //  COUPON HISTORY
                if ($coupon) {
                    CouponHistory::create([
                        'user_id' => auth()->id(),
                        'booking_id' => $booking->id,
                        'coupon_id' => $coupon->id,
                        'discount_amount' => $discountAmount,
                    ]);
                }
                // $result = $this->processBookingNotifications($booking, $slots, $address);
                return $booking;
            });
            //  SUCCESS RESPONSE
            return response()->json([
                'status' => true,
                'code' => 200,
                'message' => 'Booking created',
                'data' => [
                    'booking' => new BookingResource($booking),
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => false,
                'code' => 422,
                'message' => 'Something went wrong',
                'data' => (object) []
            ], 422);
        }
    }

    private function validateBookingRequest(Request $request)
    {
        return Validator::make($request->all(), [
            // 'serviceId' => 'required_if:type,scheduled',
            'serviceId' => 'nullable|exists:services,id',
            'addressId' => 'required|exists:addresses,id',
            'type' => 'required|in:instant,scheduled',
            'booking_subtype' => 'required_if:type,scheduled|in:single,recurring',
            // 'time' => 'required',
            'time' => 'required_if:type,scheduled',
            // 'date' => 'required_if:booking_subtype,single|date',
            //  prevent past date for single booking
            'date' => 'required_if:booking_subtype,single|date|after_or_equal:today',
            // 'start_date' => 'required_if:booking_subtype,recurring|date',
            //  prevent past start date
            'start_date' => 'required_if:booking_subtype,recurring|date|after_or_equal:today',
            'end_date' => 'required_if:booking_subtype,recurring|date|after_or_equal:start_date',
            'recurring_type' => 'required_if:booking_subtype,recurring|in:daily,weekly,monthly',
            'days' => 'required_if:recurring_type,weekly|array',
            'days.*' => 'in:mon,tue,wed,thu,fri,sat,sun',
            'monthly_date' => 'nullable|integer|min:1|max:31',
            'week' => 'nullable|integer|min:1|max:5',
            'day' => 'nullable|string',
            'duration' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'total_price' => 'required|numeric|min:0',
            // 'transaction_id' => 'required|string',
            'payment_method' => 'required|in:1,2', // 1-UPI, 2-COD  
        ]);


    }

    private function bookingExists(Request $request): bool
    {
        $query = Booking::where('user_id', auth()->id())
            ->where('type', $request->type)
            ->where('time', $request->time)
            ->where('address_id', $request->addressId)
            ->when($request->serviceId, function ($q) use ($request) {
                $q->where('service_id', $request->serviceId);
            });

        //  Handle date logic properly
        if ($request->type === 'instant') {
            $query->whereDate('start_date', now());
        } else {
            // Scheduled bookings
            if ($request->booking_subtype === 'single') {
                $query->where('booking_subtype', 'single')
                    ->whereDate('start_date', $request->date);
            }
            if ($request->booking_subtype === 'recurring') {
                $query->where('booking_subtype', 'recurring')
                    ->whereDate('start_date', $request->start_date)
                    ->whereDate('end_date', $request->end_date);
            }
        }

        return $query->exists();
    }

    private function createBooking(Request $request): Booking
    {
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
        // add logic for recurringData 26-4-2026
        $recurringData = null;
        if ($request->booking_subtype === 'recurring') {
            if ($request->recurring_type) {
                if ($request->recurring_type === 'monthly') {
                    $recurringData = [
                        'type' => 'monthly',
                        'date' => (int) $request->monthly_date,
                    ];
                } elseif ($request->recurring_type === 'weekly') {
                    $recurringData = [
                        'type' => 'weekly',
                        'days' => $request->days,
                    ];
                } elseif ($request->recurring_type === 'daily') {
                    $recurringData = [
                        'type' => 'daily',
                    ];
                }
            }
        }
        return Booking::create([
            'booking_code' => 'HS-' . now()->format('md') . strtoupper(Str::random(4)),
            'user_id' => auth()->id(),
            'service_id' => $request->serviceId,
            'type' => $request->type,
            'booking_subtype' => $request->booking_subtype,
            'start_date' => $startDate,
            'end_date' => $endDate,
            // 'time' => $request->time,
            'time' => $request->type === 'instant' ? now()->addMinutes(15)->format('H:i:s') : $request->time,
            'status' => 'pending',
            'address_id' => $request->addressId,

            'payment_method' => $request->payment_method,
            // 'recurring_data' => $recurringData ? json_encode($recurringData) : null,
            'total_price' => $request->total_price,
            'original_price' => $request->original_price,
            'discount_price' => $request->discount_price,
            'coupon_id' => $request->coupon_id,
            'coupon_code' => $request->coupon_code,
        ]);
    }

    private function generateBookingDates(Request $request): array
    {
        $dates = [];

        if ($request->type === 'instant') {
            $dates[] = now();
        } elseif ($request->booking_subtype === 'single') {
            $dates[] = Carbon::parse($request->date);
        } else {
            $dates = $this->generateRecurringDates($request);
        }

        return $dates;
    }

    private function generateRecurringDates(Request $request): array
    {
        $dates = [];
        $current = Carbon::parse($request->start_date);
        $end = Carbon::parse($request->end_date);

        if ($request->recurring_type === 'daily') {
            while ($current <= $end) {
                $dates[] = $current->copy();
                $current->addDay();
            }
        } elseif ($request->recurring_type === 'weekly') {
            while ($current <= $end) {
                if (in_array(strtolower($current->format('D')), $request->days)) {
                    $dates[] = $current->copy();
                }
                $current->addDay();
            }
        } elseif ($request->recurring_type === 'monthly') {
            $dates = $this->generateMonthlyDates($request, $current, $end);
        }

        return $dates;
    }

    private function generateMonthlyDates(Request $request, Carbon $current, Carbon $end): array
    {
        $dates = [];

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

        return $dates;
    }

    private function generateBookingSlots(Booking $booking, array $dates, Request $request, $isReschedule = false): array
    {
        $slotCount = count($dates);
        if ($request->type === 'instant') {
            $startTime = now()->addMinutes(15);
        } else {
            $startTime = Carbon::parse($request->time);
        }
        // $startTime = Carbon::parse($request->time);
        $endTime = $startTime->copy()->addMinutes($request->duration);
        // force date to today for instant bookings
        if ($request->type === 'instant') {
            $dates = [now()];
        }
        /*
        |--------------------------------------------------------------------------
        | SLOT DISCOUNT
        |--------------------------------------------------------------------------
        */

        $perSlotDiscount = $slotCount > 0
            ? $request->discount_price / $slotCount
            : 0;
        $slots = [];
        foreach (collect($dates)->unique()->values() as $date) {
                 $slotPrice = $request->price;
                $slotFinalAmount = max($slotPrice - $perSlotDiscount, 0);
            $slots[] = [
                'booking_id' => $booking->id,
                'expert_id' => null,
                'date' => $date->format('Y-m-d'),
                'start_time' => $startTime,
                'end_time' => $endTime,
                'duration' => $request->duration,
                'status' => $request->payment_method == 'cod' ? 'confirmed' : 'pending',
                'payment_method' => $request->payment_method,
                'payment_status' => 'pending',
                // 'status' => 'pending',
                // 'price' => $request->price,
                'price' => $request->discount_price > 0 ? $slotFinalAmount : $slotPrice,
                'otp_code' => rand(100000, 999999),
                'is_rescheduled' => $isReschedule ? 1 : 0,
                'discount_price' => $request->discount_price > 0 ? $perSlotDiscount : 0,
                'original_price' =>  $request->price,
                'coupon_id' => $request->coupon_id,
                'coupon_code' => $request->coupon_code,
            ];
        }
        return $slots;
    }

    private function processBookingNotifications(Booking $booking, array $slots, Address $address): array
    {
        $firebase = new FirebaseService();
        $totalSlots = count($slots);
        $availableSlots = 0;

        foreach ($slots as $slot) {
            $experts = $this->getExperts($slot['date'], $slot['start_time'], $slot['end_time'], $address->address_lat, $address->address_long);
            $slotStatus = $experts->isEmpty() ? 'not_available' : 'available';

            BookingSlot::where('booking_id', $booking->id)
                ->where('date', $slot['date'])
                ->update(['status' => $slotStatus]);

            if ($experts->isEmpty()) {
                continue;
            }
            $availableSlots++;
            $this->sendNotificationsToExperts($firebase, $experts, $booking, $slot, $address);
        }

        $message = match (true) {
            $availableSlots === 0 => 'Booking created but no experts available right now',
            $availableSlots === $totalSlots => 'Booking created & notifications sent for all slots',
            default => 'Booking created & notifications sent for some slots'
        };

        return ['message' => $message];
    }
    private function sendNotificationsToExperts(FirebaseService $firebase, $experts, Booking $booking, array $slot, Address $address): void
    {
        $tokens = [];
        // Collect all FCM tokens
        foreach ($experts as $expert) {
            if (!$expert->devices || $expert->devices->isEmpty()) {
                continue;
            }
            foreach ($expert->devices as $device) {
                if (!empty($device->fcm_token)) {
                    $tokens[] = $device->fcm_token;
                }
            }
        }
        // Remove duplicates
        $tokens = array_unique($tokens);
        if (empty($tokens)) {
            return;
        }
        // Send ONE request to Firebase
        $firebase->sendNotification(
            $tokens,
            'New Booking Request',
            "Booking on {$slot['date']} at {$slot['start_time']} is available for you. Please respond.",
            [
                'booking_id' => (string) $booking->id,
                'booking_code' => $booking->booking_code,
                'date' => $slot['date'],
                'time' => $slot['start_time'],
                'duration' => $slot['duration'],
                'location' => $address->address ?? '',
                'earning' => $slot['price'] ?? 0,
                'type' => 'BOOKING_REQUEST',
                'actions' => [
                    ['id' => 'ACCEPT', 'title' => 'Accept'],
                    ['id' => 'REJECT', 'title' => 'Reject']
                ]
            ]
        );
    }
    // private function sendNotificationsToExperts(FirebaseService $firebase, $experts, Booking $booking, array $slot, Address $address): void
    // {
    //       $tokens = [];

    //     foreach ($experts as $expert) {
    //         foreach ($expert->devices as $device) {
    //             if (empty($device->fcm_token)) {
    //                 continue;
    //             }

    //             $firebase->sendNotification(
    //                 $device->fcm_token,
    //                 'New Booking Request',
    //                 "Booking on {$slot['date']} at {$slot['start_time']} is available for you. Please respond to accept or reject.",
    //                 [
    //                     'booking_id' => (string) $booking->id,
    //                     'booking_code' => $booking->booking_code,
    //                     'date' => $slot['date'],
    //                     'time' => $slot['start_time'],
    //                     'duration' => $slot['duration'],
    //                     'location' => $address->address,
    //                     'earning' => $slot['price'] ?? 0,
    //                     'actions' => json_encode([
    //                         ['id' => 'ACCEPT', 'title' => 'Accept'],
    //                         ['id' => 'REJECT', 'title' => 'Reject']
    //                     ])
    //                 ],
    //             );
    //         }
    //     }
    // }

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

    // public function getBookingById($id)
    // {
    //     $booking = Booking::with([
    //         'service',
    //         'address',
    //         'slots.expert'
    //     ])->find($id);
    //     if (!$booking) {
    //         return response()->json([
    //             'code' => 422,
    //             'status' => false,
    //             'message' => 'Booking not found',
    //             'data' => (object) []
    //         ], 422);
    //     }
    //     return response()->json([
    //         'code' => 200,
    //         'status' => true,
    //         'message' => 'Booking retrieved successfully',
    //         'data' => new BookingResource($booking)
    //     ]);
    // }
    // get auth user bookings list
    // public function userBookingListing(Request $request)
    // {
    //     $query = Booking::with([
    //         'service',
    //         'address',
    //         'slots.expert'
    //     ])->where('user_id', auth()->id());

    //     //  FILTER BY STATUS 
    //     if ($request->filled('status')) {
    //         switch ($request->status) {
    //             case 'ongoing':
    //                 $query->where('status', 'confirmed')
    //                     ->whereDate('date', '<=', now());
    //                 break;
    //             case 'upcoming':
    //                 $query->where('status', 'confirmed')
    //                     ->whereDate('date', '>', now());
    //                 break;
    //             case 'cancelled':
    //                 $query->where('status', 'cancelled');
    //                 break;
    //             case 'completed':
    //                 $query->where('status', 'completed');
    //                 break;
    //             default:
    //                 // pending / confirmed
    //                 $query->where('status', $request->status);
    //                 break;
    //         }
    //     }

    //     $bookings = $query->latest()->get();
    //     return response()->json([
    //         'code' => 200,
    //         'status' => true,
    //         'message' => $bookings->isEmpty()
    //             ? 'No bookings found for this user'
    //             : 'User bookings retrieved successfully',
    //         'data' => BookingResource::collection($bookings),
    //         // 'pagination' => [
    //         //     'current_page' => $bookings->currentPage(),
    //         //     'last_page' => $bookings->lastPage(),
    //         //     'per_page' => $bookings->perPage(),
    //         //     'total' => $bookings->total(),
    //         // ]
    //     ]);
    // }

    public function userSlotBookingById($slotId)
    {
        $userId = auth()->id();
        $slot = BookingSlot::with([
            'booking.service',
            'booking.address',
            'booking.user',
            'expert'
        ])
            ->where('id', $slotId)
            ->whereHas('booking', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->first();
        if (!$slot) {
            return response()->json([
                'code' => 422,
                'status' => false,
                'message' => 'Slot not found',
                'data' => (object) []
            ], 422);
        }
        return response()->json([
            'code' => 200,
            'status' => true,
            'message' => 'Booking slot fetched successfully',
            'data' => new UserBookingSlotResource($slot)
        ]);
    }

    public function userSlotBookingListing(Request $request)
    {
        $status = $request->status;
        $userId = auth()->id();
        $query = BookingSlot::with([
            'booking.user',
            'booking.address',
            'booking.service',
            'expert'
        ])
            ->whereHas('booking', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            });

        //  Status filter
        if (!is_null($status)) {
            switch ($status) {
                case 'pending':
                    // $query->where('status', 'accepted')
                    $query->whereIn('status', ['pending', 'confirmed', 'notified']);
                    // ->where('start_time', '>=', now());
                    break;
                case 'upcoming':
                    $query->where('status', 'accepted');
                    // ->where('start_time', '>=', now());
                    break;
                case 'ongoing':
                    $query->where('status', 'ongoing');
                    // ->where('start_time', '>=', now());
                    break;
                case 'completed':
                    $query->where('status', 'completed');
                    break;
                case 'cancelled':
                    $query->where('status', 'cancelled');
                    break;
                default:
                    // optional: return all user slots
                    break;
            }
        }
        // $slots = $query->orderBy('date')
        //     ->orderBy('start_time')
        //     ->get();
        $slots = $query->latest('created_at')
            ->get();

        if ($slots->isEmpty()) {
            return response()->json([
                'code' => 200,
                'status' => true,
                'message' => 'No bookings found',
                'data' => (object) []
            ]);
        }

        return response()->json([
            'code' => 200,
            'status' => true,
            'message' => 'Booking list fetched successfully',
            'data' => UserBookingSlotResource::collection($slots),
        ]);
    }

    public function cancelBooking(Request $request, $id)
    {

        $validator = Validator::make($request->all(), [
            'cancel_reason' => 'required|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => 422,
                'status' => false,
                'message' => $validator->errors()->first(),
                'data' => (object) []
            ]);
        }
        $booking = Booking::find($id);
        // if(!$booking || $booking->user_id !== auth()->id()) {
        //     return response()->json([
        //         'code' => 422,
        //         'status' => false,
        //         'message' => 'Booking not found or access denied',
        //         'data' => (object) []
        //     ], 422);
        // }

        if ($booking->status === 'cancelled') {
            $booking->load('slots');
            return response()->json([
                'code' => 422,
                'status' => false,
                'message' => 'Booking already cancelled',
                'data' => [
                    'id' => $booking->id,
                    'status' => $booking->status,
                    'slots' => $booking->slots->pluck('status', 'id')
                ]
            ], 422);
        }

        //  1-Hour Restriction Check
        $now = Carbon::now();
        //  Dynamic cancel rule
        $minutesBefore = $booking->type === 'instant' ? 10 : 60;
        foreach ($booking->slots as $slot) {

            $slotDateTime = Carbon::parse(Carbon::parse($slot->date)->format('Y-m-d') . ' ' . $slot->start_time);
            // If ANY slot is within 1 hour → block
            if ($now->diffInMinutes($slotDateTime, false) < $minutesBefore) {
                return response()->json([
                    'code' => 422,
                    'status' => false,
                    'message' => $booking->type === 'instant'
                        ? 'Instant booking can only be cancelled at least 10 minutes before start time'
                        : 'Booking cannot be cancelled. One or more slots are within 1 hour.',
                    'data' => (object) []
                    // 'data' => [
                    //     'slot_id' => $slot->id,
                    //     'slot_time' => $slot->start_time,
                    //     'slot_date' => $slot->date,
                    // ]
                ], 422);
            }
        }

        try {
            DB::transaction(function () use ($booking, $request) {
                // Cancel booking
                $booking->update([
                    'status' => 'cancelled',
                    'cancel_reason' => $request->cancel_reason,
                    'cancelled_at' => now(),
                ]);
                // Cancel all slots
                BookingSlot::where('booking_id', $booking->id)
                    ->update([
                        'status' => 'cancelled',
                        'cancel_reason' => $request->cancel_reason,
                        'cancelled_at' => now(),
                    ]);
            });
            $slot = $booking->slots()->first();
            $expertId = $slot?->expert_id;
            if ($expertId && $booking->type === 'instant') {
                $expert = User::with('devices')->find($expertId);
                if ($expert) {
                    $firebase = app(FirebaseService::class);
                    foreach ($expert->devices as $device) {

                        if (!empty($device->fcm_token)) {

                            $firebase->sendNotification(
                                $device->fcm_token,
                                'Booking Cancelled',
                                'Instant booking you accepted has been cancelled',
                                [
                                    'booking_id' => (string) $booking->id,
                                    'booking_code' => (string) $booking->booking_code,
                                    'type' => 'BOOKING_CANCELLED'
                                ],
                                'expert'
                            );
                        }
                    }
                }
            }

            $booking->refresh()->load('slots');
            return response()->json([
                'code' => 200,
                'status' => true,
                'message' => 'Booking cancelled successfully',
                'data' => [
                    'id' => $booking->id,
                    'status' => $booking->status,
                    'slots' => $booking->slots->pluck('status', 'id')
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 422,
                'status' => false,
                'message' => 'Something went wrong',
                'data' => (object) []
            ], 422);
        }
    }

    // cancel single slot
    public function cancelBookingSlot(Request $request, $slotId)
    {
        $validator = Validator::make($request->all(), [
            'cancel_reason' => 'required|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => 422,
                'status' => false,
                'message' => $validator->errors()->first(),
                'data' => (object) []
            ]);
        }
        $slot = BookingSlot::with('booking')->find($slotId);
        // if(!$slot || $slot->booking->user_id !== auth()->id()) {
        //     return response()->json([
        //         'code' => 422,
        //         'status' => false,
        //         'message' => 'Booking not found or access denied',
        //         'data' => (object) []
        //     ], 422);
        // }
        // Already cancelled check
        if ($slot->status === 'cancelled') {
            return response()->json([
                'code' => 422,
                'status' => false,
                'message' => 'Slot already cancelled',
                'data' => [
                    'slot_id' => $slot->id,
                    'status' => $slot->status
                ]
            ], 422);
        }
        //  1-Hour Restriction Logic
        // $slotDateTime = Carbon::parse($slot->date . ' ' . $slot->start_time);
        $slotDateTime = Carbon::parse(Carbon::parse($slot->date)->format('Y-m-d') . ' ' . $slot->start_time);
        $now = Carbon::now();
        // Dynamic cancel rule
        $minutesBefore = $slot->booking->type === 'instant' ? 10 : 60;
        if ($now->diffInMinutes($slotDateTime, false) < $minutesBefore) {
            return response()->json([
                'code' => 422,
                'status' => false,
                'message' => $slot->booking->type === 'instant'
                    ? 'Instant booking can only be cancelled at least 10 minutes before start time'
                    : 'Slot can only be cancelled at least 1 hour before start time',
                'data' => (object) []
            ], 422);
        }
        try {
            DB::transaction(function () use ($slot, $request) {
                $slot->update([
                    'status' => 'cancelled',
                    'cancel_reason' => $request->cancel_reason,
                    'cancelled_at' => now(),
                ]);

                // Check remaining active slots
                $remainingSlots = BookingSlot::where('booking_id', $slot->booking_id)
                    ->where('status', '!=', 'cancelled')
                    ->count();

                // Cancel booking if no active slots
                if ($remainingSlots === 0) {
                    $slot->booking->update([
                        'status' => 'cancelled',
                        'cancel_reason' => $request->cancel_reason,
                        'cancelled_at' => now(),
                    ]);
                }
            });

            // $slot = $booking->slots()->first();
            $expertId = $slot?->expert_id;
            if ($expertId && $slot->booking->type === 'instant') {
                $expert = User::with('devices')->find($expertId);
                if ($expert) {
                    $firebase = app(FirebaseService::class);
                    foreach ($expert->devices as $device) {
                        if (!empty($device->fcm_token)) {
                            $firebase->sendNotification(
                                $device->fcm_token,
                                'Booking Cancelled',
                                'Instant booking you accepted has been cancelled',
                                [
                                    'booking_id' => (string) $slot->booking->id,
                                    'booking_code' => (string) $slot->booking->booking_code,
                                    'type' => 'BOOKING_CANCELLED'
                                ],
                                'expert'
                            );
                        }
                    }
                }
            }
            $slot->load('booking');

            return response()->json([
                'code' => 200,
                'status' => true,
                'message' => 'Slot cancelled successfully',
                'data' => [
                    'slot_id' => $slot->id,
                    'status' => $slot->status,
                    'booking_status' => $slot->booking->status
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 422,
                'status' => false,
                'message' => 'Something went wrong',
                'data' => (object) []
            ], 422);
        }
    }

    public function bookingCancelReason()
    {
        $reasons = BookingCancelReason::select('id', 'title')
            ->orderBy('id', 'asc')
            ->get();

        return response()->json([
            'status' => true,
            'code' => 200,
            'message' => 'Cancel reasons fetched successfully',
            'data' => $reasons
        ]);

    }


    //  Handle Payment Success / Failure
    public function handlePayment(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'payment_id' => 'nullable|string',
        ]);
        DB::beginTransaction();
        try {
            $booking = Booking::with('slots')->find($request->booking_id);
            if (!$booking) {
                return response()->json([
                    'status' => false,
                    'code' => 422,
                    'message' => 'Booking not found',
                    'data' => (object) []
                ]);
            }
            //  PAYMENT SUCCESS
            if ($request->filled('payment_id')) {
                if ($booking->status !== 'pending') {
                    return response()->json([
                        'status' => false,
                        'code' => 422,
                        'message' => 'Already processed',
                        'data' => (object) []
                    ]);
                }
                //  update booking
                $booking->update([
                    'status' => 'confirmed',
                    'payment_id' => $request->payment_id,
                    'payment_status' => 'paid',
                    'payment_time' => now()
                ]);
                //  update all slots
                $booking->slots()->update([
                    'status' => 'confirmed',
                    'payment_status' => 'paid',
                ]);
                DB::commit();
                //  reload fresh data
                $booking->load('slots');
                return response()->json([
                    'status' => true,
                    'code' => 200,
                    'message' => 'Booking confirmed successfully',
                    'data' => [
                        'booking' => new BookingResource($booking),
                    ]
                ]);
            }
            //  PAYMENT FAILED
            if ($booking->status === 'pending' && ($request->payment_id === null || $request->payment_id === '')) {
                $booking->update([
                    'payment_status' => 'failed',
                ]);
                //  update all slots
                $booking->slots()->update([
                    'payment_status' => 'failed',
                ]);
                // $booking->slots()->delete();
                // $booking->delete();
            }
            DB::commit();
            return response()->json([
                'status' => false,
                'code' => 422,
                'message' => 'Booking deleted due to failed payment',
                'data' => (object) []
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'code' => 422,
                'message' => 'Something went wrong. Please try again later.',
                'data' => (object) []

            ]);
        }
    }

    public function rescheduleBooking(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'booking_id' => 'required|exists:bookings,id',
            'time' => 'required',
            'start_date' => 'nullable|date|after_or_equal:today',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'recurring_type' => 'nullable|in:daily,weekly,monthly',
            'days' => 'nullable|array',
            'days.*' => 'in:mon,tue,wed,thu,fri,sat,sun',
            'monthly_date' => 'nullable|integer|min:1|max:31',
            'week' => 'nullable|integer|min:1|max:5',
            'day' => 'nullable|string',
            'duration' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'total_price' => 'required|numeric|min:0',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'code' => 422,
                'message' => $validator->errors()->first(),
                'data' => (object) [],
            ], 422);
        }

        DB::beginTransaction();
        try {
            $booking = Booking::find($request->booking_id);
            if (!$booking) {
                return response()->json([
                    'status' => false,
                    'code' => 422,
                    'message' => 'Booking not found',
                    'data' => (object) [],
                ], 422);
            }
            //  BLOCK if already rescheduled
            if ($booking->is_rescheduled == 1) {
                return response()->json([
                    'status' => false,
                    'code' => 422,
                    'message' => 'Booking already rescheduled once',
                    'data' => (object) []
                ], 422);
            }
            // Delete OLD slots ONLY
            BookingSlot::where('booking_id', $booking->id)->delete();
            // Update booking fields
            $booking->time = $request->time ?? $booking->start_date;
            $booking->start_date = $request->start_date ?? $booking->start_date;
            $booking->end_date = $request->end_date ?? $booking->end_date;
            $booking->status = 'pending';
            // $booking->duration = $request->duration ?? $booking->end_date;
            if ($booking->booking_subtype === 'recurring') {
                $recurringData = null;
                if ($request->recurring_type) {
                    if ($request->recurring_type === 'monthly') {
                        $recurringData = [
                            'type' => 'monthly',
                            'date' => (int) $request->monthly_date,
                        ];
                    } elseif ($request->recurring_type === 'weekly') {
                        $recurringData = [
                            'type' => 'weekly',
                            'days' => $request->days,
                        ];
                    } elseif ($request->recurring_type === 'daily') {
                        $recurringData = [
                            'type' => 'daily',
                        ];
                    }
                }
                $booking->recurring_data = $recurringData;
            }
            $booking->is_rescheduled = 1;
            $booking->save();
            //   Generate NEW dates (reuse your function)
            $dates = $this->generateBookingDates($request);
            //  override request values if not sent
            $request->merge([
                'start_date' => $booking->start_date,
                'end_date' => $booking->end_date,
                'recurring_type' => $booking->recurring_type,
                'status' => 'pending'
            ]);
            //   Generate NEW slots
            $slots = $this->generateBookingSlots($booking, $dates, $request, true);
            // make sure new slots also marked
            // foreach ($slots as &$slot) {
            //     $slot['is_rescheduled'] = 1;
            // }

            // Insert slots
            BookingSlot::insert($slots);
            DB::commit();
            return response()->json([
                'status' => true,
                'code' => 200,
                'message' => 'Booking rescheduled successfully',
                'data' => new BookingResource($booking)
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            // log error
            \Log::error('Reschedule Error: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'code' => 422,
                'message' => 'something went wrong',
                'data' => (object) []
            ]);
        }
    }

    public function rescheduleBookingSlot(Request $request)
    {
        $request->validate([
            'booking_slot_id' => 'required|exists:booking_slots,id',
            'date' => 'required|date|after_or_equal:today',
            'time' => 'required',
            'duration' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
        ]);
        DB::beginTransaction();
        try {
            $slot = BookingSlot::find($request->booking_slot_id);
            if (!$slot) {
                return response()->json([
                    'status' => false,
                    'code' => 422,
                    'message' => 'Booking Slot not found',
                    'data' => (object) [],
                ], 422);
            }
            //  prevent past time
            if ($request->date == now()->toDateString()) {
                $selected = Carbon::parse($request->date . ' ' . $request->time);
                if ($selected->lt(now())) {
                    return response()->json([
                        'status' => false,
                        'code' => 422,
                        'message' => 'Time cannot be in the past.',
                        'data' => (object) [],
                    ], 422);
                }
            }
            //  BLOCK if already rescheduled
            if ($slot->is_rescheduled == 1) {
                return response()->json([
                    'status' => false,
                    'code' => 422,
                    'message' => 'Booking slot already rescheduled once',
                    'data' => (object) []
                ], 422);
            }
            //  update slot
            $startTime = Carbon::parse($request->time);
            $endTime = $startTime->copy()->addMinutes($request->duration);
            $slot->update([
                'expert_id' => null, // unassign expert
                'date' => $request->date,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'duration' => $request->duration,
                'status' => 'pending',
                'price' => $request->price,
                'is_rescheduled' => 1
            ]);
            DB::commit();
            return response()->json([
                'status' => true,
                'code' => 200,
                'message' => 'Slot reschedule updated successfully',
                'data' => new BookingSlotResource($slot->refresh())
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Reschedule Error: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'code' => 422,
                'message' => 'something went wrong',
                'data' => (object) []
            ]);
        }
    }

    public function refundStatus($bookingSlotId)
    {
        try {

            $refund = Refund::where('booking_slot_id', $bookingSlotId)
                ->where('status', 'processed')
                ->latest()
                ->first();

            if (!$refund) {
                return response()->json([
                    'code' => 200,
                    'status' => true,
                    'message' => 'No refund found',
                    'data' => (object) []
                ]);
            }
            return response()->json([
                'code' => 200,
                'status' => true,
                'message' => 'Refund details fetched successfully',
                'data' => [
                    'slot_id' => $refund->booking_slot_id,
                    'refund_status' => $refund->status,
                    'refund_id' => $refund->refund_id,
                    'refund_amount' => $refund->amount,
                    'refunded_at' => $refund->refunded_at,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 422,
                'status' => false,
                'message' => $e->getMessage(),
                'data' => (object) []
            ]);
        }
    }

    public function completeBookingSlot($slotId)
    {
        try {
            $bookingSlot = DB::transaction(function () use ($slotId) {
                //  Lock row to prevent duplicate completion
                $bookingSlot = BookingSlot::with('booking.user')
                    ->where('id', $slotId)
                    ->whereHas('booking', function ($q) {
                        $q->where('user_id', auth()->id());
                    })
                    // ->where('status', 'ongoing')
                    ->lockForUpdate()
                    ->first();
                if (!$bookingSlot) {
                    throw new \Exception('Booking not found or unauthorized');
                }
                //  Already completed
                if ($bookingSlot->status === 'completed') {
                    throw new \Exception('Booking already completed');
                }
                $bookingSlot->status = 'completed';
                $bookingSlot->save();
                // Get booking
                $booking = $bookingSlot->booking;
                // check pending slots
                // $pendingSlots = $booking->bookingSlots()
                //     ->where('status', '!=', 'completed')
                //     ->count();
                // Check pending slots

                $pendingSlots = $booking->slots()
                    ->whereNotIn('status', ['completed', 'cancelled'])
                    ->count();
                // if all slots completed
                if ($pendingSlots == 0) {
                    $booking->status = 'completed';
                    $booking->save();
                }
                //  Correct relation
                $user = $bookingSlot->booking->user;
                if (!$user) {
                    throw new \Exception('User not found');
                }
                //  Referral logic (first time only)
                if ($user->referred_by && $user->referral_reward_given == 0) {
                    $reward = ReferEarnSetting::first() ? ReferEarnSetting::first()->referral_amount : 100;
                    $referrer = User::find($user->referred_by);
                    if ($referrer) {
                        //  Lock wallet
                        $wallet = Wallet::where('user_id', $referrer->id)
                            ->lockForUpdate()
                            ->first();
                        if (!$wallet) {
                            $wallet = Wallet::create([
                                'user_id' => $referrer->id,
                                'balance' => 0
                            ]);
                        }
                        //  Safe increment
                        $wallet->increment('balance', $reward);
                        //  Transaction log
                        WalletTransaction::create([
                            'user_id' => $referrer->id,
                            'wallet_id' => $wallet->id,
                            'amount' => $reward,
                            'type' => 'credit',
                            'source' => 'referral',
                            'reference_id' => $bookingSlot->id,
                            'description' => 'Referral reward for first completed booking'
                        ]);

                        //  Mark reward given
                        $user->update([
                            'referral_reward_given' => 1
                        ]);
                    }
                }
                return $bookingSlot;
            });
            // $bookingSlot->load('booking.user');
            return response()->json([
                'code' => 200,
                'status' => true,
                'message' => 'Booking completed successfully',
                'data' => new BookingSlotResource($bookingSlot)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 422,
                'status' => false,
                'message' => $e->getMessage(),
                'data' => (object) []
            ]);
        }
    }
    // end of controller
}

