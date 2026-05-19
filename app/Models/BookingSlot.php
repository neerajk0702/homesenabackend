<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingSlot extends Model
{
    protected $fillable = [
        'booking_id',
        'expert_id',
        'date',
        'start_time',
        'end_time',
        'duration',
        'otp_code',
        'otp_attrempts',
        'otp_verified',
        'status',
        'price',
        'payment_status',
        'check_in_time',
        'cancel_reason',
        'cancelled_at',
        'is_rescheduled',
        'is_refunded',
        'notified'
    ];

    protected $casts = [
        'date' => 'date',
        'check_in_time' => 'datetime',
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

    public function review()
    {
        return $this->hasOne(Review::class,'booking_slot_id');
    }
    public function expertSos()
    {
        return $this->hasOne(ExpertSOS::class,'booking_slot_id');
    }
    // public function invoice()
    // {
    //     return $this->morphOne(Invoice::class, 'invoiceable')
    //                 ->where('type', 'slot');
    // }
}
