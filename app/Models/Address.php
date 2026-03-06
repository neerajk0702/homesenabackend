<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    protected $fillable = [
        'user_id',
        'flat_no',
        'address',
        'landmark',
        'save_as',
        'pets',
        'address_lat',
        'address_long',
        'accuracy',
        'device_type',     
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
