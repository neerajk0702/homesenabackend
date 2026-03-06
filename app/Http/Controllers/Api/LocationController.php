<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\ExpertService;
use App\Models\BookingSlot;
use App\Models\Address;
use App\Models\User;    
use App\Http\Resources\ExpertTrackingResource;


class LocationController extends Controller
{   
    protected $expertService;
    public function __construct(
        ExpertService $expertService,
      )
      {
        $this->expertService = $expertService;
      }
    public function nearbyServices(Request $request)
    {
          $request->validate([
            'latitude'  => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);
        $experts = $this->expertService->getNearbyExperts($request);
        if ($experts->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No nearby experts found',
                'data' => []
            ], 200);
        } else {
            return response()->json([
                'status' => true,
                'message' => 'Nearby Experts Retrieved Successfully',
                'data' => $experts
            ], 200);
        }
    }

    // Update user location
    public function updateLocation(Request $request)
    {
        $request->validate([
            'addressId' => 'required|exists:addresses,id',
            'latitude'  => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'accuracy'   => 'nullable|numeric|min:0|max:100',
            'device_type'=> 'required|in:android,ios',
         ]);

        // $address = Address::where('id', $request->addressId)
        //         ->where('user_id', auth()->id()) // security check
        //         ->first();
        $user = auth()->user();
        $address = Address::where('id', $request->addressId)
            ->when($user->role === 'user', function ($q) use ($user) {
                $q->where('user_id', $user->id);
              })
            ->first();
        if (!$address) {
            return response()->json([
                'status' => false,
                'message' => 'Address not found for user',
            ], 404);
        }
         //  reject poor GPS
        if ($request->accuracy && $request->accuracy > 50) {
            return response()->json([
                'status' => false,
                'message' => 'Low GPS accuracy'
            ],422);
        }

          $address->update([
              'address_lat'  => $request->latitude,
              'address_long' => $request->longitude,
              'accuracy'  => $request->accuracy,
              'device_type' => $request->device_type,
           ]);

        return response()->json([
            'status' => true,
            'message' => 'Location updated successfully',
            'data' => [
                'latitude' => $address->address_lat,
                'longitude' => $address->address_long,
                'updated_at' => $address->updated_at
            ]
        ], 200);
    }

    // expert update location
    public function expertUpdateLocation(Request $request)
    {       
         $request->validate([
            'latitude'  => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'accuracy'   => 'nullable|numeric|min:0|max:100',
            'device_type'=> 'required|in:android,ios',
         ]);
        $user = $request->user();
        if (!$user || $user->role !== 'expert') {
            return response()->json([
                'status' => false,      
                'message' => 'Unauthorized'
            ], 401);
        }
         //  reject poor GPS
        if ($request->accuracy && $request->accuracy > 50) {
            return response()->json([
                'status' => false,          
                'message' => 'Low GPS accuracy'
            ],422);
        }

        $user->update([
            'current_latitude'  => $request->latitude,
            'current_longitude' => $request->longitude,
            // 'accuracy'  => $request->accuracy,
            'is_online' => 1,
            'device_type' => $request->device_type,
            'last_location_update' => now()
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Location updated successfully',
            'data' => [
                'latitude' => $user->current_latitude,
                'longitude' => $user->current_longitude,
                'updated_at' => $user->last_location_update
            ]
        ], 200);
    }


    // expert tracking
    public function expertTracking($slotId)
    {
        $bookingSlot = BookingSlot::with('booking', 'expert')->find($slotId);
        if (!$bookingSlot || $bookingSlot->status !== 'accepted') {
            return response()->json([
                'success' => false,
                'message' => 'No accepted expert for this slot'
            ]);
        }
        $expert = User::where('id', $bookingSlot->expert_id)
                ->where('role', 'expert')
                ->where('is_online', 1)
                ->first();

        if (!$expert) {
            return response()->json([
                'success' => false,
                'message' => 'Expert offline'
              ]);
          }

            $userLat = $bookingSlot->booking->address->address_lat;
            $userLng = $bookingSlot->booking->address->address_long;
            $expertLat = $expert->current_latitude;
            $expertLng = $expert->current_longitude;

            $distance = $this->calculateDistance($userLat,$userLng,$expertLat,$expertLng);
            $bookingSlot->distance_km = round($distance,2);
            return response()->json([
                'satus' => true,
                'message' => 'Expert location retrieved successfully',
                'data' => new ExpertTrackingResource($bookingSlot)
            ],200);   
     }  
     
     private function calculateDistance($lat1, $lon1, $lat2, $lon2)
        {
            $earthRadius = 6371;
            $dLat = deg2rad($lat2 - $lat1);
            $dLon = deg2rad($lon2 - $lon1);

            $a = sin($dLat/2) * sin($dLat/2) +
                cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
                sin($dLon/2) * sin($dLon/2);

            $c = 2 * atan2(sqrt($a), sqrt(1-$a));
            return $earthRadius * $c;
        }
}
