<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class TripIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'per_page.integer' => __('api.trips.validation_per_page_integer'),
            'per_page.min' => __('api.trips.validation_per_page_min'),
            'per_page.max' => __('api.trips.validation_per_page_max'),
        ];
    }
}
