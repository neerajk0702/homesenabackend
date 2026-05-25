<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Service;
use App\Models\Address;
use App\Models\HomePromotion;
use App\Models\ReferEarnSetting;
use App\Models\BookingSlot;
use App\Http\Resources\ServiceResource;
class UserHomeController extends Controller
{
    public function userHome(Request $request)
    {
        if ($request->addressId) {
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
            $lat = $address->address_lat;
            $lng = $address->address_long;
        } else {
            $lat = $request->latitude;
            $lng = $request->longitude;
        }

        $experts = $this->getExperts($lat, $lng);
        $services = Service::with('activeVariants')->where('status', 1)->get();
        $allServices = ServiceResource::collection($services);
        $superSavePack = HomePromotion::where('status', 1)->where('promotion_datetime', '>=', now())->first();
        if ($superSavePack) {
            $superSavePack->image = $superSavePack->image ? asset('public/' . $superSavePack->image) : null;
        }
        // $superSavePack = HomePromotion::where('status', 1)
        //     // ->where('promotion_datetime', '>=', now())
        //     ->get()
        //     ->map(function ($item) {

        //         $item->image = $item->image
        //             ? asset('public/' . $item->image)
        //             : null;

        //         return $item;
        //     });
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
                'referralReward' => $referral_reward,
                // 'upcomingBooking' => $upcomingBooking
            ]
        ]);

    }

    private function getExperts($lat, $lng)
    {
        $radiusKm = 1; // 1.2 km radius
        return User::where('users.role', 'expert')
            ->where('users.status', 1)
            ->join('expert_details', 'expert_details.user_id', '=', 'users.id')
            ->join('service_locations', 'service_locations.id', '=', 'expert_details.service_location_id')
            ->where('expert_details.is_online', true)
            ->where('expert_details.approval_status', 'approved')
            ->whereHas('devices')
            ->with('devices')
            ->select('users.*')
            ->selectRaw(
                "(6371 * acos(
                cos(radians(?)) *
                cos(radians(service_locations.latitude)) *
                cos(radians(service_locations.longitude) - radians(?)) +
                sin(radians(?)) *
                sin(radians(service_locations.latitude))
            )) AS distance",
                [$lat, $lng, $lat]
            )
            ->having('distance', '<=', $radiusKm)
            ->orderBy('distance', 'asc')
            ->get();
    }


    // public function upcomingBooking()
    // {
    //     $upcomingBooking = BookingSlot::with([
    //         'booking.user',
    //         'booking.address',
    //         'booking.service',
    //         'expert'
    //     ])
    //         ->whereHas('booking', function ($q) {
    //             $q->where('user_id', auth()->id());
    //         })
    //         ->whereIn('status', ['confirmed', 'notified', 'accepted', 'ongoing'])
    //         ->where(function ($query) {
    //             $query->whereDate('date', '>', now()->toDateString())
    //                 ->orWhere(function ($q) {
    //                     $q->whereDate('date', now()->toDateString())
    //                         ->whereTime('start_time', '>=', now()->format('H:i:s'));
    //                 });
    //         })
    //         ->orderBy('date', 'asc')
    //         ->orderBy('start_time', 'asc')
    //         ->first();

    //     // If booking not found
    //     if (!$upcomingBooking) {
    //         return response()->json([
    //             'status' => false,
    //             'code' => 422,
    //             'message' => 'No upcoming booking found',
    //             'data' => (object) []
    //         ], 422);
    //     }

    //     return response()->json([
    //         'status' => true,
    //         'code' => 200,
    //         'message' => 'Upcoming booking fetched successfully',
    //         'data' => $upcomingBooking
    //     ]);
    // }

    public function upcomingBooking()
    {
        $now = now();
        $upcomingBooking = BookingSlot::with([
            'booking.user',
            'booking.address',
            'booking.service',
            'expert'
        ])
            ->whereHas('booking', function ($q) {
                $q->where('user_id', auth()->id());
            })
            ->whereIn('status', ['confirmed', 'notified', 'accepted', 'ongoing'])
            ->where(function ($query) use ($now) {
                // Future dates
                $query->whereDate('date', '>', $now->toDateString())
                    // Today slots
                    ->orWhere(function ($q) use ($now) {
                    $q->whereDate('date', $now->toDateString())
                        ->where(function ($timeQuery) use ($now) {
                            // Upcoming start time
                            $timeQuery->whereTime('start_time', '>=', $now->format('H:i:s'))
                                // Already ongoing slot
                                ->orWhere(function ($ongoing) use ($now) {
                                $ongoing->whereTime('start_time', '<=', $now->format('H:i:s'))
                                    ->whereTime('end_time', '>=', $now->format('H:i:s'));
                            });
                        });
                });
            })
            ->orderBy('date', 'asc')
            ->orderBy('start_time', 'asc')
            ->first();
        if (!$upcomingBooking) {
            return response()->json([
                'status' => false,
                'code' => 422,
                'message' => 'No upcoming booking found',
                'data' => (object) []
            ], 422);
        }
        return response()->json([
            'status' => true,
            'code' => 200,
            'message' => 'Upcoming booking fetched successfully',
            'data' => $upcomingBooking
        ]);
    }

    public function homePageRating()
    {
        $booking = BookingSlot::with([
            'booking.service',
            'expert'
            ])
            ->whereHas('booking', function ($q) {
                $q->where('user_id', auth()->id());
            })
            ->where('rating_popup_skipped', 0)
            ->latest()
            ->first();

        return response()->json([
            'code' => 200,
            'status' => true,
            'data' => $booking,
            'message' => 'Home Rating Data fetched successfully'
        ]);
    }

    public function skipRatingPopup($slotId)
    {
        $bookingSlot = BookingSlot::where('id', $slotId)
            ->whereHas('booking', function ($q) {
                $q->where('user_id', auth()->id());
            })
            ->first();
        if (!$bookingSlot) {
            return response()->json([
                'code' => 422,
                'status' => false,
                'message' => 'Booking slot not found',
                'data' => (object) []
            ]);
        }
        $bookingSlot->rating_popup_skipped = 1;
        $bookingSlot->save();
        return response()->json([
            'code' => 200,
            'status' => true,
            'message' => 'Rating popup skipped successfully',
            'data' => (object) []   
        ]);
    }   
}
