<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CodPayment extends Model
{
    protected $fillable = [
        'booking_slot_id',
        'qr_id',
        'payment_id',
        'amount',
        'status',
        'response',
        'qr_expire_at',
        'paid_at'
    ];

    protected $casts = [
        'response' => 'array',
    ];

}
