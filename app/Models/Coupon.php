<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $fillable = [
        'code',
        'description',
        'discount_type',
        'discount_value',
        'maximum_discount_amount',
        'description',
        'coupon_for',
        'per_user_limit',
        'start_date',
        'end_date',
        'status',
    ];

    public function couponUsers()
    {
        return $this->hasMany(CouponUser::class);
    }
}
