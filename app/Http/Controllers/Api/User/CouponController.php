<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Coupon;
use App\Models\CouponUser;
use App\Models\CouponHistory;
use App\Models\Booking;
use App\Http\Resources\CouponResource;

class CouponController extends Controller
{
    public function applyCoupon(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'coupon_code' => 'required|string',
            'amount' => 'required|numeric|min:1',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'code' => 422,
                'message' => $validator->errors()->first(),
                'data' => (object) []
            ], 422);
        }
        $user = auth()->user();
        /*
        |--------------------------------------------------------------------------
        | Find Coupon
        |--------------------------------------------------------------------------
        */
        $coupon = Coupon::where('code', $request->coupon_code)
            ->where('status', 1)
            ->first();
        if (!$coupon) {
            return response()->json([
                'status' => false,
                'code' => 422,
                'message' => 'Invalid coupon code',
                'data' => (object) []
            ]);
        }
        /*
        |--------------------------------------------------------------------------
        | Check Start Date
        |--------------------------------------------------------------------------
        */
        if ($coupon->start_date && now()->lt($coupon->start_date)) {
            return response()->json([
                'status' => false,
                'message' => 'Coupon not started yet',
                'code' => 422,
                'data' => (object) []
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | Check Expiry
        |--------------------------------------------------------------------------
        */
        if ($coupon->end_date && now()->gt($coupon->end_date)) {
            return response()->json([
                'status' => false,
                'message' => 'Coupon expired',
                'code' => 422,
                'data' => (object) []
            ], 422);
        }
        /*
        |--------------------------------------------------------------------------
        | Minimum Amount
        |--------------------------------------------------------------------------
        */

        // if ($request->amount < $coupon->minimum_order_amount) {

        //     return response()->json([
        //         'status' => false,
        //         'message' => 'Minimum order amount is ₹' . $coupon->minimum_order_amount,
        //         'code' => 422,
        //         'data' => (object) []
        //     ], 422);
        // }

        /* 
        |--------------------------------------------------------------------------
        | coupon code used per user check 
        |-------------------------------------------------------------------------- 
        */
        $usedCount = CouponHistory::where('coupon_id', $coupon->id)
            ->where('user_id', $user->id)
            ->count();
        if ($usedCount >= $coupon->per_user_limit) {
            return response()->json([
                'status' => false,
                'code' => 422,
                'message' => 'Coupon usage limit exceeded for this user',
                'data' => (object) []
            ], 422);
        }
        /*
        |--------------------------------------------------------------------------
        | New User Coupon
        |--------------------------------------------------------------------------
        */
        if ($coupon->coupon_for == 'new_user') {
            $totalOrders = Booking::where('user_id', $user->id)->count();
            if ($totalOrders > 0) {
                return response()->json([
                    'status' => false,
                    'message' => 'Coupon valid only for new users',
                    'code' => 422,
                    'data' => (object) []
                ], 422);
            }
        }
        /*
        |--------------------------------------------------------------------------
        | Specific User Coupon
        |--------------------------------------------------------------------------
        */
        if ($coupon->coupon_for == 'specific_user') {
            $exists = CouponUser::where('coupon_id', $coupon->id)
                ->where('user_id', $user->id)
                ->exists();
            if (!$exists) {
                return response()->json([
                    'status' => false,
                    'message' => 'Coupon not valid for you',
                    'code' => 422,
                    'data' => (object) []
                ], 422);
            }
        }
        /*
        |--------------------------------------------------------------------------
        | Calculate Discount
        |--------------------------------------------------------------------------
        */
        if ($coupon->discount_type == 'percentage') {
            $discount = ($request->amount * $coupon->discount_value) / 100;
            // max discount cap
            if ($coupon->maximum_discount_amount && $discount > $coupon->maximum_discount_amount) {
                $discount = $coupon->maximum_discount_amount;
            }
        } else {
            $discount = $coupon->discount_value;
        }
        // prevent negative
        if ($discount > $request->amount) {
            $discount = $request->amount;
        }
        $finalAmount = $request->amount - $discount;
        return response()->json([
            'status' => true,
            'code' => 200,
            'message' => 'Coupon applied successfully',
            'data' => [
                'coupon_id' => $coupon->id,
                'coupon_code' => $coupon->code,
                'discount_type' => $coupon->discount_type,
                'discount_value' => $coupon->discount_value,
                'discount_amount' => round($discount, 2),
                'final_amount' => round($finalAmount, 2),
            ]
        ]);
    }

    public function couponList()
    {
        $user = auth()->user();
        $coupons = Coupon::where('status', 1)
            // start date
            ->where(function ($query) {
                $query->whereNull('start_date')
                    ->orWhere('start_date', '<=', now());
            })
            // end date
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            })
            ->latest()
            ->get()
            // filter user wise
            ->filter(function ($coupon) use ($user) {
                /*
                |--------------------------------------------------------------------------
                | Per User Limit
                |--------------------------------------------------------------------------
                */
                if ($coupon->per_user_limit) {
                    $usedCount = CouponHistory::where('coupon_id', $coupon->id)
                        ->where('user_id', $user->id)
                        ->count();
                    if ($usedCount >= $coupon->per_user_limit) {
                        return false;
                    }
                }
                /*
                |--------------------------------------------------------------------------
                | New User Coupon
                |--------------------------------------------------------------------------
                */
                if ($coupon->coupon_for == 'new_user') {
                    $totalOrders = Booking::where('user_id', $user->id)
                        ->count();
                    if ($totalOrders > 0) {
                        return false;
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | Specific User Coupon
                |--------------------------------------------------------------------------
                */

                if ($coupon->coupon_for == 'specific_user') {
                    $allowed = CouponUser::where('coupon_id', $coupon->id)
                        ->where('user_id', $user->id)
                        ->exists();
                    if (!$allowed) {
                        return false;
                    }
                }
                return true;
            });

        return response()->json([
            'status' => true,
            'code' => 200,
            'message' => 'Coupon list fetched successfully',
            'data' => CouponResource::collection($coupons)->values()
        ]);
    }
}
