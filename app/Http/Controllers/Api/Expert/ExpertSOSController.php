<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExpertSOS;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class SOSController extends Controller
{
    public function sendSOS(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'latitude' => 'required',
                'longitude' => 'required',
                'booking_slot_id' => 'required|exists:booking_slots,id',
                'message' => 'nullable|string'
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'code' => 422,
                    'status' => false,
                    'message' => $validator->errors()->first(),
                    'data' => (object)[]
                ], 422);
            }
            // Auth Expert + Relations
            $expert = auth()->user()->load(
                'expertDetail.emergencyContacts'
            );
            //  Prevent Spam
            $exists = ExpertSOS::where('expert_id', $expert->id)
                ->where('created_at', '>=', now()->subMinute())
                ->exists();
            if ($exists) {
                return response()->json([
                    'status' => false,
                    'message' => 'Wait before sending another SOS',
                    'code' => 422,
                    'data' => (object)[]
                ], 422);
            }
            //  Save SOS
            $sos = ExpertSOS::create([
                'expert_id' => $expert->id,
                'booking_slot_id' => $request->booking_slot_id,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'message' => $request->message,
            ]);
            //  Google Map URL
            $mapUrl = "https://maps.google.com/?q={$request->latitude},{$request->longitude}";
            //  WhatsApp Message
            $message = "🚨 SOS ALERT\n"
                . "Expert Name: {$expert->name}\n"
                . "Phone: {$expert->phone}\n"
                . "Message: " . ($request->message ?? 'Emergency Help Needed') . "\n"
                . "Location: {$mapUrl}";

            //  Send WhatsApp To Emergency Contacts
           
            if ( $expert->expertDetail && $expert->expertDetail->emergencyContacts->count()) {
                foreach ($expert->expertDetail->emergencyContacts as $contact) {
                    Http::withToken(env('WHATSAPP_TOKEN'))
                        ->post(
                            'https://graph.facebook.com/v23.0/' . env('WHATSAPP_PHONE_NUMBER_ID') . '/messages',
                            [
                                'messaging_product' => 'whatsapp',
                                'to' => $contact->phone,
                                'type' => 'text',
                                'text' => [
                                    'body' => $message
                                ]
                            ]
                        );
                }
            }
            return response()->json([
                'status' => true,
                'code' => 200,
                'message' => 'SOS sent successfully',
                'data' => $sos
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'code' => 422,
                'message' => $e->getMessage(),
                'data' => (object)[]
            ], 422);
        }
    }
}