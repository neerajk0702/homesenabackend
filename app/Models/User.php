<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens;
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'device_type',
        'device_id',
        'email',
        'email_verified_at',
        'password',
        'remember_token',
        'phone',
        'otp',
        'otp_expires_at',
        'role',
        'is_available',
        'fcm_token',
        'current_latitude' ,
        'current_longitude' ,
        'is_online',
        'device_type',
        'last_location_update' 
      
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'otp'
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function addresses()  
     {
       return $this->hasMany(Address::class);
     }

     public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    // public function services()
    // {
    //     return $this->belongsToMany(Service::class);
    // }

    // public function services()
    // {
    //     return $this->belongsToMany(Service::class,'service_experts','expert_id');
    // }

    public function services() 
    {
        return $this->belongsToMany(Service::class, 'service_experts', 'expert_id', 'service_id');
    }

    public function expertSlots()
    {
        return $this->hasMany(BookingSlot::class,'expert_id');
    }

    // scope for available experts
    public function scopeExperts($query)
    {
        return $query->where('role', 'expert')
                    ->where('is_active', 1);
    }
}
