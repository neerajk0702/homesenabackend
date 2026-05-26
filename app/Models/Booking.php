<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Booking extends Model
{
    protected $fillable = [
        'booking_code',
        'service_id',
        'user_id',
        'type',
        'booking_subtype',
        'recurring_data',
        'start_date',
        'end_date',
        'time',
        'status',
        'total_price',// final payable amount
        'payment_status',
        'payment_id',
        'payment_time',
        'address_id',
        'cancel_reason',
        'cancelled_at',
        'is_rescheduled',
        'payment_method',
        'original_price',   // before coupon
        'discount_price',   // coupon discount  
        'coupon_id' ,
        'coupon_code' ,  
    ];

    protected $casts = [
        'recurring_data' => 'array',
    ];
    // booking code generator
    // protected static function booted()
    // {
    //     static::created(function ($booking) {

    //         $booking->booking_code =
    //             'BK' . now()->year .
    //             str_pad($booking->id, 6, '0', STR_PAD_LEFT);

    //         $booking->save();
    //     });
    // }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function expert()
    {
        return $this->belongsTo(User::class, 'expert_id');
    }
    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function address()
    {
        return $this->belongsTo(Address::class);
    }

    public function slots()
    {
        return $this->hasMany(BookingSlot::class);
    }

    // public function invoice()
    // {
    //     return $this->morphOne(Invoice::class, 'invoiceable')
    //                 ->where('type', 'booking');
    // }

    public function invoice()
    {
        return $this->hasOne(Invoice::class, 'booking_id');
    }

}

