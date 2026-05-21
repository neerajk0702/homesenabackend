<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CouponHistory extends Model
{
    protected $fillable = [
        'coupon_id',
        'user_id',
        'booking_id',
        'discount_amount',
    ];

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}
