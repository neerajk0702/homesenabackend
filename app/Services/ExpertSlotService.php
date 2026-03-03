<?php

namespace App\Services;

use App\Models\BookingSlot;
use Illuminate\Support\Facades\DB;

class ExpertSlotService
{
    protected $firebase;

    public function __construct(FirebaseService $firebase)
    {
        $this->firebase = $firebase;
    }

    public function acceptSlot($slotId)
    {
        return DB::transaction(function () use ($slotId) {

            $slot = BookingSlot::lockForUpdate()->findOrFail($slotId);

            if ($slot->status === 'accepted') {
                return response()->json([
                    'status' => false,
                    'error'=>'Slot Already accepted'
                    ]);
            }

            // Conflict Check
            $conflict = BookingSlot::where('expert_id', auth()->id())
                ->where('booking_date', $slot->booking_date)
                ->where('status', 'accepted')
                ->where(function ($q) use ($slot) {
                    $q->whereBetween('start_time', [$slot->start_time, $slot->end_time])
                      ->orWhereBetween('end_time', [$slot->start_time, $slot->end_time]);
                })
                ->exists();

            if ($conflict) {
                return response()->json([
                    'status' => false,
                    'error'=>'Time conflict'
                ]);
            }

            // Accept slot
            $slot->update([
                'expert_id' => auth()->id(),
                'status' => 'accepted',
                'otp_code' => rand(100000, 999999) // Generate OTP,
            ]);

            // Notify customer
            $this->firebase->send(
                $slot->booking->user->fcm_token,
                "Booking Confirmed",
                "Your OTP is {$slot->otp_code}"
            );

            return $slot;
        });
    }
}