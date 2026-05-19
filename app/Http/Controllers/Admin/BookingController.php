<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\BookingSlot;
use App\Models\User;
use App\Models\BookingSlotLog;
use App\Models\BookingSlotNotification;
use App\Models\Refund;

class BookingController extends Controller
{

    public function index(Request $request)
    {
        $bookings = Booking::with('service', 'address', 'slots.expert')
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->search;
                $q->where(function ($query) use ($search) {
                    $query->where('booking_code', 'like', "%{$search}%")
                        ->orWhere('booking_subtype', 'like', "%{$search}%")
                        ->orWhere('start_date', 'like', "%{$search}%")
                        ->orWhere('end_date', 'like', "%{$search}%")
                        ->orWhere('type', 'like', "%{$search}%");
                });
            })
            //  Type
            ->when($request->filled('type'), function ($q) use ($request) {
                $q->where('type', $request->type);
            })

            //  Sub Type
            ->when($request->filled('sub_type'), function ($q) use ($request) {
                $q->where('booking_subtype', $request->sub_type);
            })

            //  Status
            ->when($request->filled('status'), function ($q) use ($request) {
                $q->where('status', $request->status);
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();
        return view('admin.bookings.index', compact('bookings'));
    }


    public function create()
    {
        return view('admin.bookings.create');
    }
    public function edit()
    {
        return view('admin.bookings.edit');
    }

    public function show(Booking $booking)
    {
        $booking->load(['service', 'address']);
        $slots = $booking->slots()
            ->with('expert')
            ->paginate(10);
        return view('admin.bookings.show', compact('booking', 'slots'));
    }
    public function assignExpertPage($id)
    {
        $booking = BookingSlot::findOrFail($id);

        // experts from users table
        $experts = User::where('role', 'expert')
            ->where('status', 1)
            ->get();

        return view('admin.bookings.assign-expert', compact('booking', 'experts'));
    }
    public function assignExpertSubmit(Request $request, $id)
    {
        $request->validate([
            'expert_id' => 'required|exists:users,id',
        ]);

        $booking = BookingSlot::findOrFail($id);

        $booking->expert_id = $request->expert_id;
        $booking->status = 'accepted';

        $booking->save();

        return redirect()->route('admin.bookings.index')
            ->with('success', 'Expert assigned successfully');
    }
    public function slotLogs($id)
    {
        $logs = BookingSlotLog::with('expert')
            ->where('booking_slot_id', $id)
            ->latest()
            ->get();

        return view('admin.bookings.slot_logs', compact('logs'));
    }
    public function slotNotifications($id)
    {
        $notifications = BookingSlotNotification::where('booking_slot_id', $id)
            ->latest()
            ->get();

        return view('admin.bookings.slot_notifications', compact('notifications'));
    }

    public function refundBooking($id)
    {
        try {
            $booking = Booking::findOrFail($id);
            $refundAmount = $booking->total_price;
            $paymentId = $booking->payment_id;
            $refund = $this->refundPayment(
                $paymentId,
                $refundAmount
            );
            if (!$refund['status']) {
                return back()->with('error', $refund['message']);
            }
            $booking->status = 'refunded';
            $booking->save();
            return back()->with('success', 'Booking refunded successfully');
        } catch (\Exception $e) {
            \Log::error('Refund Exception: ' . $e->getMessage());
            return back()->with('error', 'Refund Failed: ' . $e->getMessage());
        }
    }

    public function refundBookingSlot($id)
    {
        try {
            $slotBooking = BookingSlot::with('booking')
                ->findOrFail($id);
            // CHECK SLOT CANCELLED
            if ($slotBooking->status != 'cancelled' && $slotBooking->booking->payment_status != 'paid') {
                return back()->with('error', 'Please cancel slot first to process refund or check if booking is paid');
            }
            // CHECK ALREADY REFUNDED
            $alreadyRefunded = Refund::where('booking_slot_id', $slotBooking->id)
                ->where('status', 'processed')
                ->exists();
            if ($alreadyRefunded) {
                return back()->with('error', 'Slot already refunded');
            }
            $refundAmount = $slotBooking->price;
            $paymentId = $slotBooking->booking->payment_id;
            // CREATE REFUND ENTRY
            $refundEntry = Refund::create([
                'booking_id' => $slotBooking->booking_id,
                'booking_slot_id' => $slotBooking->id,
                'payment_id' => $paymentId,
                'amount' => $refundAmount,
                'status' => 'pending',
                'refunded_by' => auth()->id(),
            ]);

            // PROCESS REFUND
            $refundResponse = $this->refundPayment($paymentId, $refundAmount);
            // REFUND FAILED
            if (!$refundResponse['status']) {
                $refundEntry->update([
                    'status' => 'failed',
                    'refund_response' => json_encode($refundResponse['data'] ?? [])
                ]);
                return back()->with('error', $refundResponse['message']
                );
            }
             $slotBooking->update([
                'payment_status' => 'refunded',
                'is_refunded' => true,
            ]);
            // REFUND SUCCESS
            $refundEntry->update([
                'refund_id' => $refundResponse['refund_id'],
                'status' => 'processed',
                'refund_response' => json_encode($refundResponse['data']),
                'refunded_at' => now(),
            ]);

            // CHECK ALL SLOTS REFUNDED
            $booking = $slotBooking->booking;
            $remainingSlots = $booking->slots()
                ->whereNotIn('id', function ($query) {
                    $query->select('booking_slot_id')
                        ->from('refunds')
                        ->where('status', 'processed');
                })
                ->count();
            // ALL REFUNDED
            if ($remainingSlots == 0) {
                $booking->update([
                    'payment_status' => 'refunded'
                ]);
            }
            return back()->with(
                'success',
                'Refund processed successfully. Amount will reflect within 5-7 working days.'
            );

        } catch (\Exception $e) {
            \Log::error('Refund Exception: ' . $e->getMessage());
            return back()->with( 'error','Refund Failed: ' . $e->getMessage()
            );
        }
    }


    private function refundPayment($paymentId, $refundAmount)
    {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.razorpay.com/v1/payments/{$paymentId}/refund",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode([
                "amount" => $refundAmount * 100,
                "speed" => "normal"
            ]),
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "Authorization: Basic " . base64_encode(
                    env('RAZORPAY_KEY') . ':' . env('RAZORPAY_SECRET')
                )
            ],
        ]);
        $response = curl_exec($curl);
        $error = curl_error($curl);
        curl_close($curl);
        // CURL ERROR
        if ($error) {
            \Log::error('Refund CURL Error: ' . $error);
            return [
                'status' => false,
                'message' => $error
            ];
        }
        $apiResponse = json_decode($response, true);
        \Log::info('Refund API Response----------------', $apiResponse);
        // API FAILED
        if (isset($apiResponse['error'])) {
            return [
                'status' => false,
                'message' => $apiResponse['error']['description'] ?? 'Refund failed',
                'data' => $apiResponse
            ];
        }
        // SUCCESS
        return [
            'status' => true,
            'refund_id' => $apiResponse['id'] ?? null,
            'data' => $apiResponse
        ];
    }

}
