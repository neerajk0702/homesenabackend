<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceDuration extends Model
{
    protected $fillable = [
        'service_id',
        'duration_minutes',
        'price',    
        'is_active',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

}
