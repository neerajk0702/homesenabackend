<?php

namespace App\Http\Controllers\Api\Expert;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CodPayment;
use App\Models\BookingSlot;
use Illuminate\Support\Facades\Validator;
class RazorpayController extends Controller
{
    // Generate QR
    public function generateQr(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'booking_slot_id' => 'required|exists:booking_slots,id',
                'amount' => 'required|numeric|min:1'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'code' => 422,
                    'status' => false,
                    'message' => $validator->errors()->first(),
                    'data' => (object) []
                ]);
            }

            // CHECK SLOT EXISTS
            $slot = BookingSlot::find($request->booking_slot_id);
            if (!$slot) {
                return response()->json([
                    'code' => 422,
                    'status' => false,
                    'message' => 'Booking slot not found',
                    'data' => (object) []
                ]);
            }

            // CONVERT TO PAISE
            $amount = (int) ($request->amount * 100);
            $data = [
                "type" => "upi_qr",
                "name" => "Booking Slot Payment",
                "usage" => "single_use",
                "fixed_amount" => true,
                "payment_amount" => $amount,
                "description" => "Booking Slot Payment #" . $request->booking_slot_id,
                "close_by" => now()->addMinutes(10)->timestamp,
                "notes" => [
                    "booking_slot_id" => (string) $request->booking_slot_id,
                    "amount" => (string) $request->amount,
                ],
            ];

            $curl = curl_init();

            curl_setopt_array($curl, [
                CURLOPT_URL => "https://api.razorpay.com/v1/payments/qr_codes",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_HTTPHEADER => [
                    "Content-Type: application/json",
                    "Authorization: Basic " . base64_encode(
                        env('RAZORPAY_KEY') . ":" . env('RAZORPAY_SECRET')
                    )
                ],
            ]);

            $response = curl_exec($curl);
            $error = curl_error($curl);
            // dd( $error,    $response);
            curl_close($curl);
            // CURL ERROR
            if ($error) {
                return response()->json([
                    'code' => 422,
                    'status' => false,
                    'message' => $error,
                    'data' => (object) []
                ]);
            }

            $result = json_decode($response, true);
            // RAZORPAY ERROR
            if (isset($result['error'])) {
                return response()->json([
                    'code' => 422,
                    'status' => false,
                    'message' => $result['error']['description'] ?? 'Something went wrong',
                    'data' => (object) []
                ]);
            }
            // SAVE PAYMENT
            $payment = CodPayment::create([
                'booking_slot_id' => $request->booking_slot_id,
                'qr_id' => $result['id'],
                'amount' => $request->amount,
                'status' => 'pending',
                // 'response' => $result,
                'response' => json_encode($result),
                'qr_expire_at' => now()->addMinutes(10)
            ]);

            return response()->json([
                'code' => 200,
                'status' => true,
                'message' => 'QR Generated Successfully',
                'data' => [
                    'payment_table_id' => $payment->id,
                    'booking_slot_id' => $request->booking_slot_id,
                    'qr_id' => $result['id'],
                    'qr_image' => $result['image_url'] ?? '',
                    'amount' => $request->amount,
                    'status' => 'pending'
                ]
            ]);

        } catch (\Exception $e) {

            \Log::error('QR Generate Error', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);

            return response()->json([
                'code' => 422,
                'status' => false,
                'message' => 'Something went wrong',
                'data' => (object) []
            ]);
        }
    }
    // Check Payment
    public function checkPayment($qrId)
    {
        try {

            $payment = CodPayment::where('qr_id', $qrId)->first();
            if (!$payment) {
                return response()->json([
                    'code' => 422,
                    'status' => false,
                    'message' => 'Payment record not found',
                    'data' => (object) []
                ]);
            }
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => "https://api.razorpay.com/v1/payments/qr_codes/" . $qrId . "/payments",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_HTTPHEADER => [
                    "Content-Type: application/json",
                    "Authorization: Basic " . base64_encode(
                        env('RAZORPAY_KEY') . ":" . env('RAZORPAY_SECRET')
                    )
                ],
            ]);
            $response = curl_exec($curl);
            $error = curl_error($curl);
            curl_close($curl);
            if ($error) {
                return response()->json([
                    'code' => 422,
                    'status' => false,
                    'message' => $error,
                    'data' => (object) []
                ]);
            }
            $result = json_decode($response, true);
            // Razorpay API error
            if (isset($result['error'])) {
                \Log::error('Razorpay Payment Check Error', [
                    'response' => $result
                ]);
                return response()->json([
                    'code' => 422,
                    'status' => false,
                    'message' => $result['error']['description'] ?? 'Something went wrong',
                    'data' => (object) []
                ]);
            }
            // No payment found
            if (empty($result['items'])) {
                return response()->json([
                    'code' => 422,
                    'status' => false,
                    'message' => 'No payment found',
                    'data' => (object) []
                ]);
            }
            $paymentData = $result['items'][0];
            $paymentStatus = $paymentData['status'] ?? 'pending';
            // Razorpay payment created time
            $paidAt = null;
            if ($paymentStatus == 'captured' && isset($paymentData['created_at'])) {
                $paidAt = date(
                    'Y-m-d H:i:s',
                    $paymentData['created_at']
                );
            }
            // Update cod_payments table
            $payment->update([
                'payment_id' => $paymentData['id'] ?? null,
                'status' => $paymentStatus,
                'response' => json_encode($paymentData),
                'paid_at' => $paidAt,
            ]);
            /*
            |--------------------------------------------------------------------------
            | PAYMENT SUCCESS
            |--------------------------------------------------------------------------
            */
            if ($paymentStatus == 'captured') {
                $bookingSlot = BookingSlot::find($payment->booking_slot_id);
                // Prevent duplicate paid update
                if ($bookingSlot && $bookingSlot->payment_status != 'paid') {

                    $bookingSlot->update([
                        'payment_status' => 'paid',
                        'payment_method' => 'cod/online',
                        'payment_id' => $paymentData['id'] ?? null,
                    ]);
                }
                return response()->json([
                    'code' => 200,
                    'status' => true,
                    'message' => 'Payment Successful',
                    'data' => [
                        'booking_slot_id' => $payment->booking_slot_id,
                        'payment_id' => $paymentData['id'] ?? null,
                        'amount' => isset($paymentData['amount'])
                            ? $paymentData['amount'] / 100
                            : 0,
                        'method' => $paymentData['method'] ?? null,
                        'status' => $paymentStatus,
                        'paid_at' => $paidAt,
                    ]
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | PAYMENT FAILED
            |--------------------------------------------------------------------------
            */

            if ($paymentStatus == 'failed') {
                return response()->json([
                    'code' => 422,
                    'status' => false,
                    'message' => 'Payment Failed',
                    'data' => [
                        'status' => $paymentStatus
                    ]
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | PAYMENT PENDING
            |--------------------------------------------------------------------------
            */

            return response()->json([
                'code' => 422,
                'status' => false,
                'message' => 'Payment Pending',
                'data' => [
                    'status' => $paymentStatus
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Check Payment Error', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
            return response()->json([
                'code' => 422,
                'status' => false,
                'message' => 'Something went wrong',
                'data' => (object) []
            ]);
        }
    }

    /**
     * Razorpay Webhook
     */
    public function webhook(Request $request)
    {
        try {
            \Log::info('Razorpay Webhook', $request->all());
            // VERIFY SIGNATURE
            $webhookSecret = env('RAZORPAY_WEBHOOK_SECRET');
            $generatedSignature = hash_hmac(
                'sha256',
                $request->getContent(),
                $webhookSecret
            );
            $razorpaySignature = $request->header('X-Razorpay-Signature');
            if ($generatedSignature !== $razorpaySignature) {
                return response()->json([
                    'code' => 422,
                    'status' => false,
                    'message' => 'Invalid signature',
                    'data' => (object) []
                ]);
            }
            $payload = $request->all();
            // ONLY PAYMENT CAPTURED
            if (($payload['event'] ?? '') != 'payment.captured') {
                return response()->json([
                    'code' => 200,
                    'status' => true,
                    'message' => 'Event ignored',
                    'data' => (object) []
                ]);
            }

            $paymentEntity = $payload['payload']['payment']['entity'] ?? [];
            if (empty($paymentEntity)) {
                return response()->json([
                    'code' => 422,
                    'status' => false,
                    'message' => 'Invalid payload',
                    'data' => (object) []
                ]);
            }

            // PAYMENT ID
            $paymentId = $paymentEntity['id'] ?? null;
            // BOOKING SLOT ID FROM NOTES
            $bookingSlotId = $paymentEntity['notes']['booking_slot_id'] ?? null;
            if (!$bookingSlotId) {
                return response()->json([
                    'code' => 422,
                    'status' => false,
                    'message' => 'Booking slot id missing',
                    'data' => (object) []
                ]);
            }

            // FIND PAYMENT
            $payment = CodPayment::where('booking_slot_id', $bookingSlotId)
                ->latest()
                ->first();

            if (!$payment) {
                return response()->json([
                    'code' => 404,
                    'status' => false,
                    'message' => 'Payment record not found',
                    'data' => (object) []
                ]);
            }

            // AVOID DUPLICATE UPDATE
            if ($payment->status == 'captured') {
                return response()->json([
                    'code' => 200,
                    'status' => true,
                    'message' => 'Already processed',
                    'data' => (object) []
                ]);
            }

            // UPDATE PAYMENT TABLE
            $payment->update([
                'payment_id' => $paymentId,
                'status' => 'captured',
                'paid_at' => now(),
                'response' => json_encode($paymentEntity)
            ]);

            // UPDATE BOOKING SLOT
            BookingSlot::where('id', $bookingSlotId)
                ->update([
                    'payment_status' => 'paid',
                    'payment_method' => 'cod/online',
                    'payment_id' => $paymentId
                ]);

            return response()->json([
                'code' => 200,
                'status' => true,
                'message' => 'Webhook processed successfully',
                'data' => (object) []
            ]);

        } catch (\Exception $e) {

            \Log::error('Webhook Error', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);

            return response()->json([
                'code' => 422,
                'status' => false,
                'message' => 'Something went wrong',
                'data' => (object) []
            ]);
        }
    }
}

