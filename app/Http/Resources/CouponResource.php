<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CouponResource extends JsonResource
{
    public function toArray(Request $request): array
    {
    
        if (!empty($this->description)) {
            $description = $this->description;
        } else {
            if ($this->discount_type == 'percentage') {
                $description = $this->discount_value . '% OFF';
                if ($this->maximum_discount_amount) {
                    $description .= ' up to ₹' .
                        $this->maximum_discount_amount;
                }
            } else {
                $description = 'Flat ₹' .
                    $this->discount_value . ' OFF';
            }
        }

        return [
            'id' => $this->id,
            'code' => $this->code,
            'description' => $description,
            'discount_type' => $this->discount_type,
            'discount_value' => $this->discount_value,
            'maximum_discount_amount' => $this->maximum_discount_amount,
            'coupon_for' => $this->coupon_for,
            'per_user_limit' => $this->per_user_limit,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'status' => $this->status,
        ];
    }
}

