<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class TrackTripIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'trip_id' => ['sometimes', 'integer', 'min:1'],
            'captain_id' => ['sometimes', 'integer', 'min:1'],
            'car_id' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'per_page.integer' => __('api.track_trips.validation_per_page_integer'),
            'per_page.min' => __('api.track_trips.validation_per_page_min'),
            'per_page.max' => __('api.track_trips.validation_per_page_max'),
        ];
    }
}
