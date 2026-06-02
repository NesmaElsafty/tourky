<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['admin', 'captain', 'client'])],
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'language' => ['sometimes', 'nullable', Rule::in(['en', 'ar'])],
            'role_id' => ['sometimes', 'nullable', 'integer', 'exists:roles,id'],
            'created_from' => ['sometimes', 'nullable', 'date'],
            'created_to' => ['sometimes', 'nullable', 'date', 'after_or_equal:created_from'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => __('api.users.validation_type_required'),
            'type.in' => __('api.users.validation_type_invalid'),
            'per_page.integer' => __('api.users.validation_per_page_integer'),
            'per_page.min' => __('api.users.validation_per_page_min'),
            'per_page.max' => __('api.users.validation_per_page_max'),
            'created_to.after_or_equal' => __('api.users.validation_created_range'),
        ];
    }
}
