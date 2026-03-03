<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingSlot extends Model
{
    protected $fillable = [
        'booking_id',
        'expert_id',
        'booking_date',
        'start_time',
        'end_time',
        'duration',
        'otp_code',
        'otp_attrempts',
        'otp_verified',
        'status',
        'check_in_time'
    ];

    // relationship with others tables  
    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function expert()
    {
        return $this->belongsTo(User::class,'expert_id');
    }
}
