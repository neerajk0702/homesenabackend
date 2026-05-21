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
<<<<<<< HEAD
        'maximum_discount_amount',
=======
        'description',
>>>>>>> 86e5092ceb57509984a423535fb0ac146fbd7e72
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
