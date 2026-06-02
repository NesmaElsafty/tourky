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
            'scope' => ['sometimes', 'string', 'in:upcoming,history,today'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'scope.in' => __('api.trips.client_validation_scope_invalid'),
            'per_page.integer' => __('api.trips.validation_per_page_integer'),
            'per_page.min' => __('api.trips.validation_per_page_min'),
            'per_page.max' => __('api.trips.validation_per_page_max'),
        ];
    }
}
