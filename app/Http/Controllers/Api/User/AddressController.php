<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Address;
use App\Models\Booking;

class AddressController extends Controller
{
    //  save auth user address
    public function saveAddress(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'code' => 422,
                'status' => false,
                'message' => 'Unauthorized',
                'data' => (object) []
            ]);
        }

        $validator = Validator::make($request->all(), [
            'address' => 'required|string|max:255',
            'flat_no' => 'nullable|string|max:100',
            'landmark' => 'nullable|string|max:255',
            'save_as' => 'nullable|in:home,office,other',
             'pets' => 'nullable|boolean',
            'address_lat' => 'nullable|numeric',
            'address_long' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => 422,
                'status' => false,
                'message' => $validator->errors()->first(),
                'data' => (object) []
            ]);
        }

        $addressText = strtolower(trim($request->address));
        $flatNo = $request->flat_no ? strtolower(trim($request->flat_no)) : null;

        $duplicate = Address::where('user_id', $user->id)
            ->where('address', $addressText)
            ->where('flat_no', $flatNo)
            ->exists();

        if ($duplicate) {
            return response()->json([
                'code' => 422,
                'status' => false,
                'message' => 'Address already exists',
                'data' => (object) []
            ]);
        }

        $address = Address::create([
            'user_id' => $user->id,
            'address' => $addressText,
            'flat_no' => $flatNo,
            'landmark' => $request->landmark,
            'save_as' => $request->save_as,
            'pets' => $request->pets,
            'address_lat' => $request->address_lat,
            'address_long' => $request->address_long,
        ]);

        return response()->json([
            'code' => 200,
            'status' => true,
            'message' => 'Address saved successfully',
            'data' => $address
        ]);
    }

    // update address auth user address
    public function updateAddress(Request $request, $id)
    {
        $userId = auth()->id();

        $address = Address::where('id', $id)
            ->where('user_id', $userId)
            ->first();

        if (!$address) {
            return response()->json([
                'code' => 422,
                'status' => false,
                'message' => 'Address not found',
                'data' => (object) []
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'address' => 'required|string|max:255',
            'flat_no' => 'nullable|string|max:100',
            'landmark' => 'nullable|string|max:255',
            'save_as' => 'nullable|string',
            'pets' => 'nullable|boolean',
            'address_lat' => 'nullable|numeric',
            'address_long' => 'nullable|numeric'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'code' => 422,
                'message' => $validator->errors()->first(),
                'data' => (object) []
            ], 422);
        }

        $addressText = strtolower(trim($request->address));
        $flatNo = $request->flat_no ? strtolower(trim($request->flat_no)) : null;

        // Duplicate check
        $query = Address::where('user_id', $userId)
            ->where('address', $addressText)
            ->where('id', '!=', $id);

        if ($flatNo) {
            $query->where('flat_no', $flatNo);
        } else {
            $query->whereNull('flat_no');
        }

        if ($query->exists()) {
            return response()->json([
                'status' => false,
                'code' => 422,
                'message' => 'Address already exists',
                'data' => (object) []
            ], 422);
        }

        $address->update([
            'address' => $addressText,
            'flat_no' => $flatNo,
            'landmark' => $request->landmark,
            'save_as' => $request->save_as,
            'pets' => $request->pets,
            'address_lat' => $request->address_lat,
            'address_long' => $request->address_long,
        ]);

        return response()->json([
            'code' => 200,
            'status' => true,
            'message' => 'Address updated successfully',
            'data' => $address->fresh()
        ], 200);
    }

    // auth all address list
    public function addressList(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'code' => 422,
                'status' => false,
                'message' => 'Unauthorized',
                'data' => (object) []
            ], 422);
        }
        $addresses = $user->addresses()->latest()->get();
        return response()->json([
            'code' => 200,
            'status' => true,
            'message' => 'Address list retrieved successfully',
            'data' => $addresses
        ]);
    }


    public function deleteAddress(Request $request, $id)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'code' => 422,
                'status' => false,
                'message' => 'Unauthorized',
                'data' => (object) []
            ], 422);
        }
        $address = Address::where('id', $id)
            ->where('user_id', $user->id)
            ->first();
        if (!$address) {
            return response()->json([
                'code' => 422,
                'status' => false,
                'message' => 'Address not found',
                'data' => (object) []
            ], 422);
        }
        $bookingExists = Booking::where('address_id', $id)
            ->whereDate('end_date', '>=', today())
            ->exists();
        if ($bookingExists) {
            return response()->json([
                'code' => 422,
                'status' => false,
                'message' => 'This address cannot be deleted because it is associated with upcoming bookings.',
                'data' => (object) []
            ], 422);
        }
        $address->delete();
        return response()->json([
            'code' => 200,
            'status' => true,
            'message' => 'Address deleted successfully',
            'data' => (object) []
        ]);
    }
}
