<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\BookingSlot;
use App\Models\User;
use App\Services\FirebaseService;
use Illuminate\Support\Facades\Log;
use App\Models\BookingSlotLog;

class NotifyNearbyExpertsBeforeSlot extends Command
{
    protected $signature = 'app:notify-nearby-experts-before-slot';
    protected $description = 'Notify experts with 20 sec delay between slots';

    public function handle()
    {
        $firebase = app(FirebaseService::class);

        $now = now();
        $today = $now->toDateString();
        $time = $now->format('H:i:s');

        // $slots = BookingSlot::with(['booking.address','booking.user'])
        //     ->where('status', 'confirmed')
        //     ->whereNull('expert_id')
        //     ->where(function ($q) use ($today, $time) {
        //         $q->where('date', '>', $today)
        //           ->orWhere(function ($q2) use ($today, $time) {
        //               $q2->where('date', $today)
        //                  ->where('start_time', '>=', $time);
        //           });
        //     })
        //     ->orderBy('date')
        //     ->orderBy('start_time')
        //     ->get();

        $slots = BookingSlot::with(['booking.address', 'booking.user'])
            ->where('status', 'confirmed')
            ->whereNull('expert_id')
            ->where(function ($q) use ($today, $time) {

                // Future slots
                $q->where(function ($q1) use ($today, $time) {
                    $q1->where('date', '>', $today)
                        ->orWhere(function ($q2) use ($today, $time) {
                            $q2->where('date', $today)
                                ->where('start_time', '>=', $time);
                        });
                })

                    //  Retry logic
                    ->where(function ($q3) {
                    $q3->where('notified', 0)
                        ->orWhere(function ($q4) {
                            $q4->where('notified', 1)
                                ->where('updated_at', '<=', now()->subMinutes(2));
                        });
                });
            })
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();
        //  Log::info("Slot  notifications  ==== " . $slots);
        foreach ($slots as $index => $slot) {

            if (!$slot->booking || !$slot->booking->address) {
                continue;
            }
            // $startDateTime = Carbon::parse($slot->date . ' ' . $slot->start_time);
            $date = Carbon::parse($slot->date)->format('Y-m-d');
            $startDateTime = Carbon::createFromFormat(
                'Y-m-d H:i:s',
                $date . ' ' . $slot->start_time
            );
            $bookingType = $slot->booking->type ?? 'scheduled';
            // Time windows
            if ($bookingType === 'instant') {
                $startWindow = $startDateTime->copy()->subMinutes(14);
                $endWindow = $startDateTime->copy()->subMinutes(5);
            } else {
                $startWindow = $startDateTime->copy()->subMinutes(30);
                $endWindow = $startDateTime->copy()->subMinutes(10);
            }
            if (!$now->between($startWindow, $endWindow)) {
                continue;
            }
            //  Lock
            $locked = BookingSlot::where('id', $slot->id)
                ->where('notified', '!=', 2)
                ->update(['notified' => 2]);

            if (!$locked)
                continue;

            try {

                $date = $startDateTime->format('Y-m-d');
                $startTime = $startDateTime->format('H:i:s');
                $endTime = Carbon::createFromFormat('H:i:s', $slot->end_time)->format('H:i:s');

                $lat = $slot->booking->address->address_lat ?? null;
                $lng = $slot->booking->address->address_long ?? null;

                if (!$lat || !$lng) {
                    $this->resetSlot($slot->id);
                    continue;
                }

                $experts = $this->getExperts($date, $startTime, $endTime, $lat, $lng);
                // Log::info("Slot  experts  ==== " . $experts);
                // $experts = User::where('role', 'expert')
                //     ->where('status', 1)

                //     // Expert must be approved + online
                //     ->whereHas('expertDetail', function ($q) {
                //         $q->where('is_online', true)
                //             ->where('approval_status', 'approved');
                //     })

                //     // Must have device for notification
                //     ->whereHas('devices')

                //     // Avoid overlapping slots
                //     ->whereDoesntHave('expertSlots', function ($q) use ($date, $startTime, $endTime) {
                //         $q->where('date', $date)
                //             ->where('status', 'accepted')
                //             ->where('start_time', '<', $endTime)
                //             ->where('end_time', '>', $startTime);
                //     })

                //     ->with('devices')
                //     ->get();

                if ($experts->isEmpty()) {
                    $this->resetSlot($slot->id);
                    continue;
                }

                //  tokens
                $tokens = [];
                $expertIds = [];
                foreach ($experts as $expert) {
                    foreach ($expert->devices as $device) {
                        if (!empty($device->fcm_token)) {
                            $tokens[] = $device->fcm_token;
                            // unique expert
                            $expertIds[$expert->id] = $expert->id;
                        }
                    }
                }

                $tokens = array_values(array_unique(array_filter($tokens)));
                $expertIds = array_values($expertIds);

                if (empty($tokens)) {
                    $this->resetSlot($slot->id);
                    continue;
                }

                //  earnings
                $earning = $slot->price ? $slot->price * 0.5 : 0;

                //  address
                $address = implode(', ', array_filter([
                    $slot->booking->address->flat_no ?? '',
                    $slot->booking->address->address ?? '',
                    $slot->booking->address->area_name ?? '',
                    $slot->booking->address->landmark ?? ''
                ]));

                //  profile
                $profileImage = $slot->booking?->user?->profile_image ?? '';
                $profile_image = $profileImage ? url('public/' . $profileImage) : '';

                $data = [
                    'user_name' => $slot->booking->user->name ?? '',
                    'profile_image' => $profile_image,
                    'booking_id' => $slot->booking->id ?? '',
                    'slot_id' => $slot->id ?? '',
                    'date' => $date,
                    'time' => $startTime,
                    'earning' => $earning,
                    'address' => $address,
                    'type' => 'BOOKING_REQUEST',
                    'booking_type' => $bookingType,
                ];

                // convert all to string safely
                $data = array_map(function ($value) {
                    return (string) $value;
                }, $data);

                //  Send to all experts (no delay here)
                foreach ($tokens as $token) {
                    $firebase->sendNotification(
                        $token,
                        'New Booking Available',
                        $bookingType === 'instant'
                        ? 'Instant booking available near you!'
                        : 'Scheduled booking starting soon!',
                        $data,
                        'expert'
                    );
                }

                foreach ($expertIds as $expertId) {
                    BookingSlotLog::updateOrCreate(
                        [
                            'booking_slot_id' => $slot->id,
                            'expert_id' => $expertId,
                        ],
                        [
                            'action' => 'notified',
                            'sent_at' => now(),
                        ]
                    );
                }

                Log::info("Slot {$slot->id} notifications sent");

                BookingSlot::where('id', $slot->id)->update([
                    'notified' => 1,
                    'updated_at' => now()
                    // 'status' => 'notified'
                ]);

            } catch (\Exception $e) {

                $this->resetSlot($slot->id);

                Log::error("Slot error", [
                    'slot_id' => $slot->id,
                    'error' => $e->getMessage()
                ]);
            }

            // 20 sec delay BEFORE next slot
            if ($index < count($slots) - 1) {
                sleep(20);
            }
        }

        $this->info("Cron executed successfully");
    }

    private function resetSlot($slotId)
    {
        BookingSlot::where('id', $slotId)->update(['notified' => 0]);
    }

    private function getExperts($date, $startTime, $endTime, $lat, $lng)
    {
        $radiusKm = 1.5; // 1.2 km radius
        return User::where('users.role', 'expert')
            ->where('users.status', 1)
            ->join('expert_details', 'expert_details.user_id', '=', 'users.id')
            ->join('service_locations', 'service_locations.id', '=', 'expert_details.service_location_id')
            ->where('expert_details.is_online', true)
            ->where('expert_details.approval_status', 'approved')
            ->whereHas('devices')
            ->with('devices')
            ->select('users.*')
            ->selectRaw(
                "(6371 * acos(
                cos(radians(?)) *
                cos(radians(service_locations.latitude)) *
                cos(radians(service_locations.longitude) - radians(?)) +
                sin(radians(?)) *
                sin(radians(service_locations.latitude))
            )) AS distance",
                [$lat, $lng, $lat]
            )
            ->having('distance', '<=', $radiusKm)
            ->orderBy('distance', 'asc')
            ->whereDoesntHave('expertSlots', function ($q) use ($date, $startTime, $endTime) {
                $q->where('date', $date)
                    ->where('status', 'accepted')
                    ->where('start_time', '<', $endTime)
                    ->where('end_time', '>', $startTime);
            })
            ->get();
    }
}