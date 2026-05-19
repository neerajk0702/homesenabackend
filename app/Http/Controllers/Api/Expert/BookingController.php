<?php

namespace App\Http\Controllers\Api\Expert;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\ExpertBookingResource;
use App\Http\Resources\ExpertBookingSlotResource;
use App\Models\Booking;
use App\Models\BookingSlot;
use App\Models\UserDevice;
use App\Services\FirebaseService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\BookingSlotLog;
use App\Http\Resources\BookingSlotResource;
use App\Models\ExpertBookingRejectReason;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\User;
class BookingController extends Controller
{
    // public function bookingList(Request $request)
    // {
    //     $expert = $request->user();
    //     $bookings = Booking::whereHas('slots', function ($q) use ($expert) {
    //         $q->where('expert_id', $expert->id);
    //     })
    //         ->with([
    //             'user:id,name,phone',
    //             'service:id,name',
    //             'slots' => function ($q) use ($expert) {
    //                 $q->where('expert_id', $expert->id);
    //             }
    //         ])
    //         ->latest()
    //         ->get();
    //     return response()->json([
    //         'status' => true,
    //         'code' => 200,
    //         'message' => $bookings->isEmpty()
    //             ? 'No bookings found'
    //             : 'Booking list retrieved successfully',
    //         'data' => ExpertBookingResource::collection($bookings)
    //     ], 200);
    // }

    public function bookingSlotList(Request $request)
    {
        $expert = $request->user();
        $status = $request->status; // ongoing | upcoming | completed

        $query = $expert->expertSlots()
            ->with([
                'booking.service:id,name',
                'booking.user:id,name,phone',
                'booking.address:id,flat_no,address,area_name,landmark,save_as,address_lat,address_long',
            ]);

        // Apply status filter only if status is NOT null
        if (!is_null($status)) {

            switch ($status) {

                case 'cancelled':
                    $query->where('status', 'cancelled');
                    break;

                case 'upcoming':
                    $query->where('status', 'accepted')
                        ->where(function ($q) {
                            $q->whereDate('date', '>', now()->toDateString()) // future dates
                                ->orWhere(function ($q2) {
                                    $q2->whereDate('date', now()->toDateString()) // today
                                        ->whereTime('start_time', '>=', now()->format('H:i:s')); // future time
                                });
                        });
                    break;

                case 'completed':
                    $query->where('status', 'completed');
                    break;

                default:
                    return response()->json([
                        'code' => 422,
                        'status' => false,
                        'message' => 'Invalid status value',
                        'data' => []
                    ], 422);
            }
        }
        $slots = $query->orderBy('date')
            ->orderBy('start_time')
            ->get();

        if ($slots->isEmpty()) {
            return response()->json([
                'code' => 200,
                'status' => true,
                'message' => 'No bookings found',
                'data' => []
            ]);
        }

        return response()->json([
            'status' => true,
            'code' => 200,
            'message' => $slots->isEmpty()
                ? 'No bookings found'
                : 'Booking list fetched',
            'data' => ExpertBookingSlotResource::collection($slots)
        ]);
    }

    // public function bookingDetails(Request $request, $bookingId)
    // {
    //     $expert = $request->user();
    //     $booking = Booking::where('id', $bookingId)
    //         ->whereHas('slots', function ($q) use ($expert) {
    //             $q->where('expert_id', $expert->id);
    //         })
    //         ->with([
    //             'user:id,name,phone',
    //             'service:id,name',
    //             'slots' => function ($q) use ($expert) {
    //                 $q->where('expert_id', $expert->id);
    //             }
    //         ])
    //         ->first();
    //     if (!$booking) {
    //         return response()->json([
    //             'status' => false,
    //             'code' => 422,
    //             'message' => 'Booking not found',
    //             'data' => (object) []
    //         ], 422);
    //     }

    //     return response()->json([
    //         'status' => true,
    //         'code' => 200,
    //         'message' => 'Booking details retrieved successfully',
    //         'data' => new ExpertBookingResource($booking)
    //     ], 200);


    // }

    public function bookingSlotDetail(Request $request, $id)
    {
        $expert = $request->user();
        $slot = $expert->expertSlots()
            ->with([
                'booking.service:id,name',
                'booking.user:id,name,phone',
                'booking.address:id,flat_no,address,area_name,landmark,save_as,address_lat,address_long',
                'expertSos:id,expert_id,booking_slot_id'
            ])
            ->where('id', $id)
            ->first();

        if (!$slot) {
            return response()->json([
                'code' => 422,
                'status' => false,
                'message' => 'Booking slot not found',
                'data' => (object) []
            ]);
        }
        return response()->json([
            'status' => true,
            'code' => 200,
            'message' => 'Booking slot details fetched successfully',
            'data' => new ExpertBookingSlotResource($slot)
        ]);
    }

    // public function upcomingBooking(Request $request)
    // {
    //     $expert = $request->user();
    //     $slot = $expert->expertSlots()
    //         ->with([
    //             'booking.service:id,name',
    //             'booking.user:id,name,phone',
    //             'booking.address:id,flat_no,address,area_name,landmark,save_as,address_lat,address_long'
    //         ])
    //         ->where('status', 'accepted')
    //         ->whereDate('date', '>=', now()->toDateString())
    //         ->orderBy('date')
    //         ->orderBy('start_time')
    //         ->first();
    //     return response()->json([
    //         'status' => true,
    //         'code' => 200,
    //         'message' => $slot
    //             ? 'Upcoming booking found'
    //             : 'No upcoming booking',
    //         'data' => $slot ? new ExpertBookingSlotResource($slot) : (object) []
    //     ], 200);
    // }

    public function upcomingBooking(Request $request)
    {
        $expert = $request->user();

        // Ongoing (from DB)
        $ongoing = $expert->expertSlots()
            ->with([
                'booking.service:id,name',
                'booking.user:id,name,phone',
                'booking.address:id,flat_no,address,area_name,landmark,save_as,address_lat,address_long',
                'expertSos:id,expert_id,booking_slot_id'
            ])
            ->where('status', 'ongoing')
            ->orderBy('date')
            ->orderBy('start_time')
            ->first();

        if ($ongoing) {
            return response()->json([
                'status' => true,
                'code' => 200,
                'message' => 'Ongoing booking found',
                'data' => new ExpertBookingSlotResource($ongoing)
            ]);
        }

        //  Upcoming
        $upcoming = $expert->expertSlots()
            ->with([
                'booking.service:id,name',
                'booking.user:id,name,phone',
                'booking.address:id,flat_no,address,area_name,landmark,save_as,address_lat,address_long',
                'expertSos:id,expert_id,booking_slot_id'
            ])
            ->where('status', 'accepted')
            ->whereDate('date', '>=', now()->toDateString())
            ->orderBy('date')
            ->orderBy('start_time')
            ->first();

        return response()->json([
            'status' => true,
            'code' => 200,
            'message' => $upcoming ? 'Upcoming booking found' : 'No booking found',
            // 'type' => $upcoming ? 'upcoming' : null,
            'data' => $upcoming ? new ExpertBookingSlotResource($upcoming) : (object) []
        ]);
    }

    //  ACCEPT BOOKING
    // public function acceptSlot(Request $request, FirebaseService $firebase)
    public function acceptSlot(Request $request, FirebaseService $firebase)
    {

        // $firebase = app(FirebaseService::class);
        $validator = Validator::make($request->all(), [
            'booking_slot_id' => 'required|exists:booking_slots,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'code' => 422,
                'message' => $validator->errors()->first(),
                'data' => (object) []
            ], 422);
        }
        try {
            $slot = BookingSlot::find($request->booking_slot_id);
            if (!$slot) {
                return response()->json([
                    'status' => false,
                    'code' => 422,
                    'message' => 'Booking slot not found',
                    'data' => (object) []
                ]);
            }
            DB::transaction(function () use ($slot, $request) {
                //  Check if already accepted
                $alreadyAccepted = BookingSlot::where('booking_id', $slot->booking_id)
                    ->where('status', 'accepted')
                    ->lockForUpdate()
                    ->exists();
                if ($alreadyAccepted) {
                    throw new \Exception('Booking already accepted by another expert');
                }
                //  Security check
                $expert = auth()->user();
                //  Accept slot
                $slot->expert_id = $expert->id;
                $slot->status = 'accepted';
                $slot->save();
                // Add Booking Log
                // BookingSlotLog::create([
                //     'booking_slot_id' => $request->booking_slot_id,
                //     'expert_id' => $expert->id,
                //     'action' => 'accepted',
                // ]);
                BookingSlotLog::updateOrCreate(
                    [
                        'booking_slot_id' => $request->booking_slot_id,
                        'expert_id' => $expert->id,
                    ],
                    [
                        'action' => 'accepted',
                    ]
                );
            });
            // Load relation instead of extra query
            $slot->load('booking');
            //  Notify user
            if (!empty($slot->booking?->user_id)) {
                $notificationResponse = $this->sendToUserDevices(
                    $slot->booking->user_id,
                    "Booking Accepted",
                    "Your booking has been accepted by expert",
                    ['type' => 'booking_accept'],
                    $firebase
                );
            }

            \Log::info("Booking Accepted main:=============== " . json_encode($notificationResponse));

            return response()->json([
                'status' => true,
                'code' => 200,
                'message' => 'Booking accepted successfully',
                'data' => new BookingSlotResource($slot)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'code' => 422,
                'message' => $e->getMessage(),
                'data' => (object) []
            ], 422);
        }
    }


    //  REJECT BOOKING

    public function rejectSlot(Request $request)
    {
        $request->validate([
            'booking_slot_id' => 'required|exists:booking_slots,id',
            'reason' => 'nullable|string'
        ]);
        $expert = auth()->user();
        $slot = BookingSlot::find($request->booking_slot_id);
        if (!$slot) {
            return response()->json([
                'status' => false,
                'code' => 422,
                'message' => 'Booking slot not found',
                'data' => (object) []
            ]);
        }
        //  Get latest action for this expert + slot
        $latestAction = BookingSlotLog::where('booking_slot_id', $slot->id)
            ->where('expert_id', $expert->id)
            ->latest()
            ->value('action');
        if ($latestAction === 'accepted') {
            return response()->json([
                'code' => 422,
                'status' => false,
                'message' => 'You have already accepted this booking',
                'data' => (object) []
            ], 422);
        }
        if ($latestAction === 'rejected') {
            return response()->json([
                'code' => 422,
                'status' => false,
                'message' => 'Already rejected',
                'data' => (object) []
            ], 422);
        }
        // BookingSlotLog::create([
        //     'booking_slot_id' => $slot->id,
        //     'expert_id' => $expert->id,
        //     'action' => 'rejected',
        //     'reason' => $request->reason,
        // ]);
        BookingSlotLog::updateOrCreate(
            [
                'booking_slot_id' => $slot->id,
                'expert_id' => $expert->id,
            ],
            [
                'action' => 'rejected',
                'reason' => $request->reason,
            ]
        );
        // Load relation
        $slot->load('booking');
        return response()->json([
            'code' => 200,
            'status' => true,
            'message' => 'Booking rejected successfully',
            'data' => (object) []
        ]);
    }

    // SEND NOTIFICATION
    private function sendToUserDevices($userId, $title, $body, $data = [], $firebase)
    {
        $tokens = UserDevice::where('user_id', $userId)
            ->whereNotNull('fcm_token')
            ->pluck('fcm_token')
            ->unique()
            ->toArray();
        // Ensure title & body are strings
        $title = is_array($title) ? json_encode($title) : $title;
        $body = is_array($body) ? json_encode($body) : $body;

        $data = is_array($data) ? $data : (array) $data;

        // Convert all values to string (IMPORTANT)
        foreach ($data as $key => $value) {
            if (!is_string($value)) {
                $data[$key] = json_encode($value);
            }
        }

        $responses = [];
        foreach ($tokens as $token) {
            // \Log::info("FINAL DATA:=============== " .$token);
            $response = $firebase->sendNotification($token, $title, $body, $data, 'user');

            $responses[] = [
                'token' => $token,
                'response' => $response
            ];
        }
        //   \Log::info("Booking Accepted resp:=============== " .json_encode($responses));
        return $responses;
    }

    public function verifyOtp(Request $request, $slotId)
    {
        $request->validate([
            'otp_code' => 'required|digits:6'
        ]);
        $slot = BookingSlot::with('booking:id', 'expert.expertDetail:user_id,registration_code,is_online')->find($slotId);
        // dd($slot->expert->expertDetail);
        if ($slot->expert_id !== auth()->id()) {
            return response()->json([
                'status' => false,
                'code' => 422,
                'message' => 'Unauthorized',
                'data' => (object) []
            ], 422);
        }

        //  Already verified
        if ($slot->otp_verified) {
            return response()->json([
                'status' => false,
                'code' => 422,
                'message' => 'OTP already verified',
                'data' => (object) []
            ], 422);
        }

        //  Max attempts
        if ($slot->otp_attempts >= 5) {
            return response()->json([
                'status' => false,
                'code' => 422,
                'message' => 'Maximum OTP attempts exceeded',
                'data' => (object) []
            ], 422);
        }
        //  Wrong OTP
        if ($slot->otp_code !== $request->otp_code) {
            $slot->increment('otp_attempts');
            $attemptsLeft = max(0, 5 - $slot->otp_attempts);
            return response()->json([
                'status' => false,
                'code' => 422,
                'message' => "Invalid OTP. Attempts left: {$attemptsLeft}",
                'data' => (object) []
            ], 422);
        }
        $startTime = now();
        $duration = $slot->duration ?? 30;
        $endTime = $startTime->copy()->addMinutes($duration);
        //  Success
        $slot->update([
            'otp_verified' => true,
            'otp_attempts' => 0,
            'status' => 'ongoing',
            'check_in_time' => now(),
            'otp_code' => null,
            'start_time' => $startTime,
            'end_time' => $endTime,
        ]);

        return response()->json([
            'status' => true,
            'code' => 200,
            'message' => 'OTP verified successfully',
            'data' => [
                'booking_id' => $slot->booking->id,
                'slot_id' => $slot->id,
                'status' => $slot->status,
                'check_in_time' => $slot->check_in_time?->format('Y-m-d H:i:s'),
                'start_time' => $slot->start_time?->format('H:i:s'),
                'end_time' => $slot->end_time?->format('H:i:s'),
                'expert_registration_code' => $slot->expert?->expertDetail?->registration_code,
                'is_expert_online' => $slot->expert?->expertDetail?->is_online
            ]
        ]);
    }

    public function bookingRejectReason()
    {
        $rejectReasonList = ExpertBookingRejectReason::select('id', 'title', 'status')
            ->where('status', 1)
            ->get();
        return response()->json([
            'code' => 200,
            'status' => true,
            'message' => 'Reject reasons fetched successfully',
            'data' => $rejectReasonList
        ]);
    }

    public function completeBookingSlot($slotId)
    {
        try {
            $bookingSlot = DB::transaction(function () use ($slotId) {
                //  Lock row to prevent duplicate completion
                $bookingSlot = BookingSlot::with('booking.user')
                    ->where('id', $slotId)
                    ->where('expert_id', auth()->id())
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
                $booking = $bookingSlot->booking;
                // check pending slots
                $pendingSlots = $booking->bookingSlots()
                    ->where('status', '!=', 'completed')
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
                    $reward = 100;
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

}

