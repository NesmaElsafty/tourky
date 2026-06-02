<?php

namespace App\Http\Requests\Captain;

use Illuminate\Foundation\Http\FormRequest;

class TripIndexRequest extends FormRequest
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
            'scope' => ['sometimes', 'string', 'in:upcoming,week,history,today'],
            'week_offset' => ['sometimes', 'integer', 'min:0', 'max:52'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'scope.in' => __('api.captain_trips.validation_scope_invalid'),
            'week_offset.integer' => __('api.captain_trips.validation_week_offset_integer'),
            'week_offset.min' => __('api.captain_trips.validation_week_offset_min'),
            'week_offset.max' => __('api.captain_trips.validation_week_offset_max'),
            'per_page.integer' => __('api.trips.validation_per_page_integer'),
            'per_page.min' => __('api.trips.validation_per_page_min'),
            'per_page.max' => __('api.trips.validation_per_page_max'),
        ];
    }
}
