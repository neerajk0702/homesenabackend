<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // return parent::toArray($request);
          return [
            'booking_id' => $this->id,
            'booking_created_at' => $this->booking_date,
            'booking_code' => $this->booking_code,
            'status' => $this->status,

            //  Service Details
            'service' => [
                'id' => $this->service->id ?? null,
                'name' => $this->service->name ?? null,
            ],

            //  Address Details
            'address' => [
                'id' => $this->address->id ?? null,
                'address' => $this->address->address ?? null,
                'lat' => $this->address->address_lat ?? null,
                'lng' => $this->address->address_long ?? null,   
            ],

            //  Multiple Slots
            'slots' => $this->bookingSlots->map(function ($slot) {
                return [
                    'slot_id' => $slot->id,
                    'booking_date' => $slot->booking_date,
                    'start_time' => $slot->start_time,
                    'end_time' => $slot->end_time,
                    'duration' => $slot->duration,
                    'status' => $slot->status,

                    //  Expert per slot
                    'expert' => $slot->expert ? [
                        'id' => $slot->expert->id,
                        'name' => $slot->expert->name,
                        'phone' => $slot->expert->phone,
                    ] : null,
                ];
            }),
        ];
    }
}
