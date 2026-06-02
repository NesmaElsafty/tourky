<?php

namespace App\Http\Requests\Admin;

use App\Models\Notification;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FiredNotificationsByUserTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_type' => ['required', Rule::in(Notification::USER_TYPES)],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_type.required' => __('api.notifications.validation_user_type_required'),
            'user_type.in' => __('api.notifications.validation_user_type_invalid'),
        ];
    }
}
