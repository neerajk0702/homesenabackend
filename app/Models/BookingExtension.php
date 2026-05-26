<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingExtension extends Model
{
     protected $fillable = [
        'booking_slot_id',
        'minutes',
        'price',
        'payment_status',
        'status',
        'payment_id',
        'paid_at'
    ];

     protected $casts = [
        'price' => 'float',
        'paid_at' => 'datetime',
    ];

     public function bookingSlot()
    {
        return $this->belongsTo(BookingSlot::class, 'booking_slot_id');
    }
  

}
