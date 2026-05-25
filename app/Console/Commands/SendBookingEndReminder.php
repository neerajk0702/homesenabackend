<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\BookingSlot;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Services\FirebaseService;
class SendBookingEndReminder extends Command
{
    
    protected $signature = 'app:send-booking-end-reminder';
    protected $description = 'Send push notification before booking end time';

    public function handle()
    {
        $firebase = new FirebaseService();
        $now = Carbon::now();
        // 10 minutes before booking end time
        $startTime = $now->copy()->addMinutes(10)->startOfMinute();
        $endTime   = $now->copy()->addMinutes(10)->endOfMinute();
        $slots = BookingSlot::with([
                'booking.user.devices',
                'expert.devices'
            ])
            ->whereDate('date', $now->toDateString())
            ->where('status', 'ongoing')
            ->where('end_notification_sent', 0)
            ->whereBetween('end_time', [
                $startTime->format('H:i:s'),
                $endTime->format('H:i:s')
            ])
            ->get();
        foreach ($slots as $slot) {
            try {
                /*
                |--------------------------------------------------------------------------
                | USER NOTIFICATION
                |--------------------------------------------------------------------------
                */
                $user = $slot->booking->user ?? null;
                if ($user && $user->devices->count()) {
                    foreach ($user->devices as $device) {
                        // SKIP EMPTY TOKEN
                        if (empty($device->fcm_token)) {
                            continue;
                        }
                         $firebase->sendNotification(
                            $device->fcm_token,
                            'Booking Ending Soon',
                            'Your booking will end in 10 minutes.'
                        );
                    }
                    Log::info('User end notification sent', [
                        'slot_id' => $slot->id,
                        'user_id' => $user->id
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | EXPERT NOTIFICATION
                |--------------------------------------------------------------------------
                */
                $expert = $slot->expert ?? null;
                if ($expert && $expert->devices->count()) {
                    foreach ($expert->devices as $device) {
                        // SKIP EMPTY TOKEN
                        if (empty($device->fcm_token)) {
                            continue;
                        }
                         $firebase->sendNotification(
                            $device->fcm_token,
                            'Booking Ending Soon',
                            'This booking will end in 10 minutes.'
                        );
                    }
                    Log::info('Expert end notification sent', [
                        'slot_id' => $slot->id,
                        'expert_id' => $expert->id
                    ]);
                }
                /*
                |--------------------------------------------------------------------------
                | UPDATE STATUS
                |--------------------------------------------------------------------------
                */
               $slot->update([
                    'end_notification_sent' => 1,
                    'end_notification_sent_at' => now()
                ]);
            } catch (\Exception $e) {
                Log::error('End notification failed', [
                    'slot_id' => $slot->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
        $this->info('Booking end reminder completed.');
    }
}
