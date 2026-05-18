<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Service;
use App\Models\Address;
use App\Models\HomePromotion;
use App\Models\ReferEarnSetting;
use App\Http\Resources\ServiceResource;
class UserHomeController extends Controller
{
    public function userHome( Request $request){
        if($request->addressId){
            $address = Address::find($request->addressId);
            // Check latitude & longitude
            if (!$address->address_lat || !$address->address_long) {
                return response()->json([
                    'code' => 422,
                    'success' => false,
                    'message' => 'Selected address does not have valid location. Please update address.',
                    'data' => (object) []
                ], 422);
            }
             $lat =  $address->address_lat;
             $lng=  $address->address_long;
        } 
        else {
             $lat =  $request->latitude;
             $lng=  $request->longitude;
        }
        
        $experts = $this->getExperts($lat, $lng);
        $services = Service::with('activeVariants')->where('status', 1)->get();
        $allServices = ServiceResource::collection($services);
        $superSavePack = HomePromotion::where('status', 1)->where('promotion_datetime','>=',now())->first();
        $superSavePack->image = $superSavePack->image ? asset('public/' . $superSavePack->image) : null;
        // $referral_reward = 100;
        $referral_reward = ReferEarnSetting::first() ? ReferEarnSetting::first()->referral_amount : 100;
        return response()->json([
                'status' => true,
                'code' => 200,
                'message' => 'Experts and service list',
                'data' => [
                    'experts' => $experts,
                    'services' => $allServices,
                    'superSavePack' => $superSavePack,
                    'referralReward' => $referral_reward   
            ]
       ]);

    }

private function getExperts( $lat, $lng)
    {
           $radiusKm = 1;
           return User::where('users.role', 'expert')
                    ->where('users.status', 1)
                    ->join('addresses', 'addresses.user_id', '=', 'users.id')
                    ->join('expert_details', 'expert_details.user_id', '=', 'users.id') 
                    ->where('expert_details.is_online', true) 
                    ->with('devices')
                    ->select('users.*')
                    ->selectRaw(
                    "(6371 * acos(
                        cos(radians(?)) *
                        cos(radians(addresses.address_lat)) *
                        cos(radians(addresses.address_long) - radians(?)) +
                        sin(radians(?)) *
                        sin(radians(addresses.address_lat))
                    )) AS distance",
                    [$lat, $lng, $lat]
                )
                ->having('distance', '<=', $radiusKm)
                ->orderBy('distance', 'asc')
                ->get();
    }
    
}
