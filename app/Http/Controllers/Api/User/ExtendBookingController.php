<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BookingSlot;
use App\Models\BookingExtension;
use Carbon\Carbon;
use App\Models\Booking;
// use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ExtendBookingController extends Controller
{
    public function extendBooking(Request $request)
    {
        $booking = BookingSlot::where('id', $request->booking_slot_id)
            ->whereHas('booking', function ($query) {
                $query->where('user_id', auth()->id());
            })
            ->first();
        if (!$booking) {
            return response()->json([
                'code' => 422,
                'status' => false,
                'message' => 'Booking not found',
                'data' => (object) []
            ]);
        }
        $extension = BookingExtension::create([
            'booking_slot_id' => $booking->id,
            'minutes' => $request->minutes,
            'price' => $request->price,
            'payment_status' => 'pending',
            'status' => 'pending',
        ]);
        // 
        return response()->json([
            'code' => 200,
            'status' => true,
            'message' => 'Booking extended successfully',
            'data' => $extension,

        ]);
    }
    public function paymentSuccessExtendBooking($extensionId)
    {
        try {
            $extension = BookingExtension::find($extensionId);
            if (!$extension) {
                return response()->json([
                    'code' => 422,
                    'status' => false,
                    'message' => 'Booking extension not found',
                    'data' => (object) []
                ]);
            }
            // prevent duplicate processing
            if ($extension->payment_status === 'paid') {
                return response()->json([
                    'code' => 200,
                    'status' => true,
                    'message' => 'Payment already processed',
                    'data' => (object) []
                ]);
            }
            DB::transaction(function () use ($extension) {
                $bookingSlot = BookingSlot::lockForUpdate()
                    ->find($extension->booking_slot_id);
                if (!$bookingSlot) {
                    throw new \Exception('Booking slot not found');
                }
                //  UPDATE SLOT TIME
                $bookingSlot->end_time = Carbon::parse($bookingSlot->end_time)
                    ->addMinutes($extension->minutes);
                //  UPDATE SLOT PRICE
                $bookingSlot->price = ($bookingSlot->price ?? 0) + $extension->price;
                //  UPDATE EXTENDED MINUTES
                // $bookingSlot->extended_minutes =
                //     ($bookingSlot->extended_minutes ?? 0) + $extension->minutes;
                $bookingSlot->save();
                // UPDATE MAIN BOOKING PRICE
                $booking = Booking::lockForUpdate()
                    ->find($bookingSlot->booking_id);
                if ($booking) {
                    $booking->total_price = ($booking->total_price ?? 0) + $extension->price;
                    $booking->save();
                }
                //  UPDATE EXTENSION STATUS
                $extension->update([
                    'payment_status' => 'paid',
                    'status' => 'completed',
                    'paid_at' => now()
                ]);
            });
            return response()->json([
                'code' => 200,
                'status' => true,
                'message' => 'Payment successful',
                'data' => (object) []
            ]);
        } catch (\Exception $e) {
            \Log::error('Extension Payment Error', [
                'extension_id' => $extensionId,
                'message' => $e->getMessage()
            ]);
            return response()->json([
                'code' => 422,
                'status' => false,
                'message' => 'Something went wrong',
                'data' => (object) []
            ], 422);
        }
    }
}
