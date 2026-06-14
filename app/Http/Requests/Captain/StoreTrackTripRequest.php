<?php

namespace App\Http\Requests\Captain;

use Illuminate\Foundation\Http\FormRequest;

class StoreTrackTripRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'trip_id' => ['required', 'integer', 'exists:trips,id'],
            'car_id' => ['required', 'integer', 'exists:cars,id'],
            'captain_id' => ['required', 'integer', 'exists:users,id'],
            'point_id' => ['required', 'integer', 'exists:points,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'trip_id.required' => __('api.track_trips.validation_trip_id_required'),
            'trip_id.exists' => __('api.track_trips.validation_trip_id_exists'),
            'car_id.required' => __('api.track_trips.validation_car_id_required'),
            'car_id.exists' => __('api.track_trips.validation_car_id_exists'),
            'captain_id.required' => __('api.track_trips.validation_captain_id_required'),
            'captain_id.exists' => __('api.track_trips.validation_captain_id_exists'),
            'point_id.required' => __('api.track_trips.validation_point_id_required'),
            'point_id.exists' => __('api.track_trips.validation_point_id_exists'),
        ];
    }
}
