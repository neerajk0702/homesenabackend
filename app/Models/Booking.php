<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Booking extends Model
{
      protected $fillable = [   
        'booking_code',
        'user_id',
        'expert_id',
        'service_id',
        'address_id',
        'scheduled_at',
        // 'duration_hours',
        'status',
        'total_amount',
        'cancel_reason',
        // 'payment_method',
        'check_in_time',
        'notes',
        'otp_code',
    ];

 // booking code generator
   protected static function boot()
    {
        parent::boot();
        static::creating(function ($booking) {

            do {
                $code = 'BK' . now()->format('md') . strtoupper(Str::random(4));
            } while (self::where('booking_code', $code)->exists());

            $booking->booking_code = $code;
        });
    }

    public function user(){
            return $this->belongsTo(User::class);
        }

    public function service()
        {
            return $this->belongsTo(Service::class);
        }

    public function address()
    {
        return $this->belongsTo(Address::class);
    }
}

    