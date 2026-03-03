<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceExpert extends Model
{
    protected $fillable = [
        'service_id',
        'expert_id',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function expert()
    {
        return $this->belongsTo(User::class,'expert_id');
    }
}
