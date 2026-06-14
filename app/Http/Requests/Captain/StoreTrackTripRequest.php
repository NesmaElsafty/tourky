<?php

namespace App\Http\Requests\Captain;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'point_id' => ['required_if:message_type,arrival', 'nullable', 'integer', 'exists:points,id'],
            'client_id' => ['required_if:message_type,acceptance', 'nullable', 'integer', 'exists:users,id'],
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
            'message_type.required' => __('api.track_trips.validation_message_type_required'),
            'message_type.in' => __('api.track_trips.validation_message_type_in'),
            'point_id.required_if' => __('api.track_trips.validation_point_id_required'),
            'point_id.exists' => __('api.track_trips.validation_point_id_exists'),
            'client_id.required_if' => __('api.track_trips.validation_client_id_required'),
            'client_id.exists' => __('api.track_trips.validation_client_id_exists'),
        ];
    }
}
