<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Service;

class ServiceController extends Controller
{
    // get all services
    public function getServices()
    {
        $services = Service::select('id', 'name')->get();
         if($services->isEmpty()) {
        // if(empty($services)) {
            return response()->json([
                'code' => 404,
                'status' => false,
                'message' => 'No services found'
            ], 404);
        } else{
        return response()->json([
            'code' => 200,
            'status' => true,
            'message' => 'Service List retrieved successfully',
            'body' => $services
          ]);

        }
    }

// get service by id
    public function getServiceById($id)
    {
        $service = Service::select('id', 'name')->find($id);
        if (!$service) {
            return response()->json([
                'code' => 404,
                'status' => false,
                'message' => 'Service not found'
            ], 404);
        } else {
            return response()->json([
                'code' => 200,
                'status' => true,
                'message' => 'Service retrieved successfully',
                'body' => $service
            ]);
        }
    }   
}
