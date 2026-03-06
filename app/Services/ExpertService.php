<?php
namespace App\Services;

use App\Models\Address;
use App\Models\User;

class ExpertService
{
    /**
     * Create a new class instance.
     */
     
    public function __construct()
    {
        //
    }

    // nearby expert finder
    public function getNearbyExperts($addressRequest)
     {      
            $latitude = $addressRequest->latitude;
            $longitude = $addressRequest->longitude;
            $radius = $addressRequest->input('radius_km', 1); // Default radius is 1 km if not provided
            //  Haversine Formula to calculate distance and find experts within radius
            $experts = User::where('users.role', 'expert')
                    ->where('users.is_available', 1)
                    ->join('addresses', 'addresses.user_id', '=', 'users.id')
                    ->select('users.*')
                    ->selectRaw(
                    "(6371 * acos(
                        cos(radians(?)) *
                        cos(radians(addresses.address_lat)) *
                        cos(radians(addresses.address_long) - radians(?)) +
                        sin(radians(?)) *
                        sin(radians(addresses.address_lat))
                    )) AS distance",
                    [$latitude, $longitude, $latitude]
                )
                ->having('distance', '<=', $radius)
                ->orderBy('distance', 'asc')
                ->with('services')
                ->get();
            return $experts;
    }
}
