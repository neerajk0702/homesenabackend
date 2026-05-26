<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class UserBookingSlotResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // $date = $this->date ? Carbon::parse($this->date) : null;
        // $startTime = $this->start_time ? Carbon::parse($this->start_time) : null;
        // $endTime = $this->end_time ? Carbon::parse($this->end_time) : null;

        return [
            //  SLOT INFO
            'slot_id' => $this->id,
            'date' => $this->date,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'duration' => $this->duration,
            'status' => $this->status,
            'price' => $this->price,
            'check_in_time' => $this->check_in_time,
            'otp_code'=>$this->otp_code,
            'payment_status'=>$this->payment_status,
            'payment_method'=>$this->payment_method,


            //  BOOKING INFO
            'booking' => [
                'booking_id' => $this->booking?->id ?? null,
                'booking_code' => $this->booking?->booking_code ?? null,
                'type' => $this->booking?->type ?? null,
                'booking_subtype' => $this->booking?->booking_subtype ?? null,

                'start_date' => $this->booking?->start_date ?? null,
                'end_date' => $this->booking?->end_date ?? null,
                'time' => $this->booking?->time ?? null ,
                'total_price' => $this->booking?->total_price ?? null,
                'payment_id'=>$this->booking?->payment_id ?? null,
                'payment_time'=>$this->booking?->payment_time ?? null,
                'payment_status'=>$this->booking?->payment_status ?? null,
                'payment_method'=>$this->booking?->payment_method ?? null,
                'created_at'=>$this->booking?->created_at,

                //  SERVICE
                'service' => [
                    'id' => $this->booking?->service?->id ?? null,
                    'name' => $this->booking?->service?->name ?? null,
                ],

                //  USER
                'user' => [
                    'id' => $this->booking?->user?->id,
                    'name' => $this->booking?->user?->name,
                    'phone' => $this->booking?->user?->phone,
                ],

                //  EXPERT
                'expert' => [
                    'id' => $this->expert?->id ?? null,
                    'name' => $this->expert?->name ?? null,
                    'phone' => $this->expert?->phone ?? null,
                    'email' => $this->expert?->email ?? null,
                    'profile_image' => $this->expert?->profile_image
                                ? asset('public/' . $this->expert->profile_image)
                                : null,
                                               
                    // optional extras
                    //'rating' => $this->expert?->ratingStat ?? null,
                ], 
            ],
        ];
    }
}
