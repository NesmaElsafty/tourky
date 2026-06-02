<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'number_of_seats' => ['nullable', 'string', 'max:255'],
            'type' => ['sometimes', 'required', 'in:sedan,microbus'],
            'plate_numbers' => ['nullable', 'string', 'max:255'],
            'plate_letters' => ['nullable', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:active,inactive,maintenance,in_use'],
            'captain_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where('type', 'captain'),
            ],
        ];
    }
}
