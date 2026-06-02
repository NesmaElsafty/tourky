<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class FireNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_ids' => ['sometimes', 'array'],
            'user_ids.*' => ['integer', 'distinct', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_ids.array' => __('api.notifications.validation_user_ids_array'),
            'user_ids.*.integer' => __('api.notifications.validation_user_ids_integer'),
            'user_ids.*.exists' => __('api.notifications.validation_user_ids_exists'),
        ];
    }
}
