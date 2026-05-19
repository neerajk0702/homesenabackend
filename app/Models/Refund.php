<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Refund extends Model
{
       protected $fillable = [
        'booking_id',
        'booking_slot_id',
        'payment_id',
        'refund_id',
        'amount',
        'refund_response',
        'status',
        'refunded_at',
        'refunded_by'
    ];

    public function booking()
    {
        return $this->belongsTo( Booking::class);
    }
    public function bookingSlot()
    {
        return $this->belongsTo(BookingSlot::class);
    }
    public function admin()
    {
        return $this->belongsTo( User::class, 'refunded_by');
    }
}
