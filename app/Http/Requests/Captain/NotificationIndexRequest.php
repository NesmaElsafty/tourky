<?php

namespace App\Http\Requests\Captain;

use Illuminate\Foundation\Http\FormRequest;

class NotificationIndexRequest extends FormRequest
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
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'per_page.integer' => __('api.notifications.validation_per_page_integer'),
            'per_page.min' => __('api.notifications.validation_per_page_min'),
            'per_page.max' => __('api.notifications.validation_per_page_max'),
        ];
    }
}
