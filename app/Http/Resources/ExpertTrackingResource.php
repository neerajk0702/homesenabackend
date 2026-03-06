<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpertTrackingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // return parent::toArray($request);
        // dd($this->expert) ;
                return [
                    'slot_id' => $this->id,
                    'booking_id' => $this->booking_id,
                    'expert' => [
                        'id' => $this->expert?->id,
                        'name' => $this->expert?->name,
                        'phone_masked' => $this->expert ? substr($this->expert->phone, 0, 2).'XXXX'.substr($this->expert->phone, -4) : null,
                        // 'rating' => $this->expert->rating,
                    ],
                    'location' => [
                        'latitude' => $this->expert?->current_latitude,
                        'longitude' => $this->expert?->current_longitude,
                        'last_updated' => $this->expert?->last_location_update,
                    ],
                    "distance_km" => $this->distance_km,
                    'estimated_arrival_time' => $this->calculateETA($this->distance_km) ?? 'Calculating...',
                    'tracking_status' => $this->status,
        ];
    }

        public function calculateETA($distanceKm)
        {
            $averageSpeedKmh = 30; // Average speed in km/h
            if ($distanceKm <= 0) {
                return 'Arriving soon';
            }
            $etaHours = $distanceKm / $averageSpeedKmh;
            $etaMinutes = round($etaHours * 60);
            return "$etaMinutes minutes";
        }       
}
