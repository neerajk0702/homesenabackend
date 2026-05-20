<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory, Notifiable, SoftDeletes;
    protected $fillable = [
        'name',
        'email',
        'profile_image',
        'password',
        'phone',
        'otp',
        'role',
        'remember_token',
        'email_verified_at',
        'otp_expires_at',
        'otp_last_sent_at',
        'status',
        'profile_completed',
        'referral_code',
        'referred_by',
        'referral_reward_given',
        'delete_reason'
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
            'otp_expires_at' => 'datetime',
            'otp_last_sent_at' => 'datetime',
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

    public function services()
    {
        return $this->belongsToMany(Service::class, 'service_experts', 'expert_id', 'service_id'); // expert_id ==>user's table id for expert
    }

    public function expertSlots()
    {
        return $this->hasMany(BookingSlot::class, 'expert_id');
    }
    public function expert()
    {
        return $this->belongsTo(User::class, 'expert_id');
    }
    public function reviews()
    {
        return $this->hasMany(Review::class, 'expert_id');
    }

    public function ratingStat()
    {
        return $this->hasOne(ExpertRatingStat::class, 'expert_id');
    }

    public function expertDetail()
    {
        return $this->hasOne(ExpertDetail::class, 'user_id');
    }

    public function devices()
    {
        return $this->hasMany(UserDevice::class);
    }
    public function onlineLogs()
    {
        return $this->hasMany(ExpertOnlineLog::class, 'user_id');
    }

    // scope for available experts
    public function referrer()
    {
        return $this->belongsTo(User::class, 'referred_by');
    }

    public function referrals()
    {
        return $this->hasMany(User::class, 'referred_by');
    }

    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }

    //  One user → many transactions
    public function walletTransactions()
    {
        return $this->hasMany(WalletTransaction::class);
    }
}
