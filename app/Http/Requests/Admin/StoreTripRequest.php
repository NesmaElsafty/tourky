<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTripRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date'],
            'route_time_id' => ['required', 'integer', 'exists:route_times,id'],
            'cars' => ['required', 'array', 'min:1'],
            'cars.*.captain_id' => ['required', 'integer', 'distinct', 'exists:users,id'],
            'cars.*.car_id' => ['required', 'integer', 'distinct', 'exists:cars,id'],
            'cars.*.status' => ['sometimes', 'string', Rule::in(['planned', 'in_progress', 'completed', 'cancelled'])],
        ];
    }

    public function messages(): array
    {
        return [
            'date.required' => __('api.trips.validation_date_required'),
            'date.date' => __('api.trips.validation_date_date'),
            'route_time_id.required' => __('api.trips.validation_route_time_id_required'),
            'route_time_id.exists' => __('api.trips.validation_route_time_id_exists'),
            'cars.required' => __('api.trips.validation_cars_required'),
            'cars.array' => __('api.trips.validation_cars_array'),
            'cars.min' => __('api.trips.validation_cars_min'),
            'cars.*.captain_id.distinct' => __('api.trips.validation_captain_distinct'),
            'cars.*.car_id.distinct' => __('api.trips.validation_car_distinct'),
        ];
    }
}
