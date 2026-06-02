<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ReservationPriceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'time_id' => ['required', 'integer', 'exists:times,id'],
            'drop_off_time_id' => ['required', 'integer', 'exists:times,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'time_id.required' => __('api.reservations.validation_time_id'),
            'time_id.exists' => __('api.reservations.validation_time_id'),
            'drop_off_time_id.required' => __('api.reservations.validation_drop_off_time_id'),
            'drop_off_time_id.exists' => __('api.reservations.validation_drop_off_time_id'),
        ];
    }
}
