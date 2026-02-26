<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Booking;
use Carbon\Carbon;

class BookingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
       return [
          'serviceId' => [
                'required',
                'exists:services,id'
            ],
                'addressId' => [
                'required',
                Rule::exists('addresses', 'id')
                    ->where('user_id', $this->user()->id),
            ],
            'scheduledAt' => [
                'required',
                'date',
                'after:now'
            ],

            'durationHours' => [
                'required',
                'integer',
                'min:1'
            ],

            'paymentMethod' => [
                'required',
                Rule::in(['UPI', 'CARD', 'WALLET', 'CASH'])
            ],
        ];
    }
}
