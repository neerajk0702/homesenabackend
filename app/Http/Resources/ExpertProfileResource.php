<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpertProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
         return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'profile_image' => $this->profile_image,
            'profile_completed' => $this->profile_completed,
            'gender' => $this->gender,

            'expertDetail' => [
                'training_center_id' => $this->expertDetail->training_center_id ?? null,
                'training_center' => $this->expertDetail->trainingCenter->name ?? null,
            ],
        ];
    }
}
