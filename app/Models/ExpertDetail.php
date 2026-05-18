<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExpertDetail extends Model
{
    protected $fillable = [
        'id',
        'user_id',
        'registration_code',

        // Training / Service
        'training_center_id',
        'service_location_id',

        'is_online',
        'approval_status',
        'approved_at',
        'approved_by',

        // ✅ KYC
        'aadhar_front',
        'aadhar_back',
        'pan_number',
        'aadhar_number',

        // ✅ BANK
        'account_holder_name',
        'account_number',
        'ifsc_code',
        'bank_name',
    ];

    /*
    |--------------------------------------------------------------------------
    | User Relation
    |--------------------------------------------------------------------------
    */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Training Center Relation
    |--------------------------------------------------------------------------
    */
    public function trainingCenter()
    {
        return $this->belongsTo(TrainingCenter::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Service Location Relation
    |--------------------------------------------------------------------------
    */
    public function serviceLocation()
    {
        return $this->belongsTo(ServiceLocation::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Emergency Contacts
    |--------------------------------------------------------------------------
    */
    public function emergencyContacts()
    {
        return $this->hasMany(
            ExpertEmergencyContact::class,
            'expert_detail_id'
        );
    }
}