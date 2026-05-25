<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Service;
use App\Http\Resources\ServiceResource;
use App\Models\TimeSlot;
use App\Models\InstantBookingSetting;
use App\Models\ServiceLocation;
use Illuminate\Support\Facades\Validator;

class ServiceController extends Controller
{
    // get all services
    public function getServices()
    {
        $services = Service::with('activeVariants')
            ->where('status', 1)
            ->get();

        return response()->json([
            'code' => 200,
            'status' => true,
            'message' => 'Service list retrieved successfully',
            'data' => ServiceResource::collection($services)
        ], 200);
    }

    // get service by id
    public function getServiceById($id)
    {
        $service = Service::with('activeVariants')
            ->where('status', 1)
            ->find($id);

        if (!$service) {
            return response()->json([
                'status' => false,
                'code' => 422,
                'message' => 'Service not found',
                'data' => (object) []
            ]);
        }

        return response()->json([
            'code' => 200,
            'status' => true,
            'message' => 'Service retrieved successfully',
            'data' => new ServiceResource($service)
        ]);
    }

    // public function timeSlot()
    // {
    //     $allTimeSlots = TimeSlot::select('id', 'start_time')->get();

    //     return response()->json([
    //         'code' => 200,
    //         'status' => true,
    //         'message' => 'Time Slots retrieved successfully',
    //         'data' => $allTimeSlots
    //     ]);
    // }

    public function timeSlot()
    {
        $allTimeSlots = TimeSlot::select('id', 'start_time')
            ->where('status', 1) // ONLY ACTIVE SLOTS
            ->orderBy('start_time', 'asc')
            ->get();

        return response()->json([
            'code' => 200,
            'status' => true,
            'message' => 'Time Slots retrieved successfully',
            'data' => $allTimeSlots
        ]);
    }
    public function instantBookingSetting()
    {
        $allduration = InstantBookingSetting::select(
            'id',
            'duration_minutes',
            'price',
            'discount_price'
        )->get();

        return response()->json([
            'code' => 200,
            'status' => true,
            'message' => 'Instant booking duration retrieved successfully',
            'data' => $allduration
        ]);
    }

    public function serviceAvailable(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'code' => 422,
                'message' => $validator->errors()->first(),
                'data' => (object) []
            ], 422);
        }
        $radiusKm = .2; // 200 meters
        $exists = ServiceLocation::select('*')
            ->selectRaw("
            (6371 * acos(
                cos(radians(?)) *
                cos(radians(latitude)) *
                cos(radians(longitude) - radians(?)) +
                sin(radians(?)) *
                sin(radians(latitude))
            )) AS distance", [
                $request->latitude,
                $request->longitude,
                $request->latitude
            ])
            ->where('status', 1)
            ->having('distance', '<=', $radiusKm)
            ->exists();

        return response()->json([
            'status' => true,
            'code' => 200,
            // 'data' => $exists,
            'data' => true,
            'message' => $exists ? 'Service available in this area' : 'Service not available in this area'
        ]);
    }
}
