<?php

namespace App\Http\Requests\Admin;

use App\Models\Notification;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class NotificationIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_type' => ['nullable', Rule::in(Notification::USER_TYPES)],
        ];
    }
}
