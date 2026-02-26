<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = [
        'name',
        'image',
        'description',
        'base_price',
        'duration_minutes',
        'status',
        'new_flag' 
    ];

    // Define relationships and other model methods as needed   
}
