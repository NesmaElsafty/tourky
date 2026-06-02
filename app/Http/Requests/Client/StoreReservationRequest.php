<?php

namespace App\Http\Requests\Client;

use Illuminate\Foundation\Http\FormRequest;

class StoreReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'time_id' => ['required', 'integer', 'exists:times,id'],
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'drop_off_time_id' => ['required', 'integer', 'exists:times,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'time_id.required' => __('api.reservations.validation_time_id'),
            'time_id.exists' => __('api.reservations.validation_time_id'),
            'date.required' => __('api.reservations.validation_date_past'),
            'date.date_format' => __('api.reservations.validation_date_past'),
            'date.after_or_equal' => __('api.reservations.validation_date_past'),
            'drop_off_time_id.required' => __('api.reservations.validation_drop_off_time_id'),
            'drop_off_time_id.exists' => __('api.reservations.validation_drop_off_time_id'),
        ];
    }
}
