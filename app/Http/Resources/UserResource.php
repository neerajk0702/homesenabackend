<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $this->role,
            'profile_completed' => $this->profile_completed,
            'gender' => $this->gender,
            'profile_image' => $this->profile_image
                ? asset('public/' . $this->profile_image)
                : null,
        ];
    }
}
