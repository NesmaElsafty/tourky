<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'number_of_seats' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:sedan,microbus'],
            'plate_numbers' => ['required', 'string', 'max:255'],
            'plate_letters' => ['required', 'string', 'max:255'],
            'color' => ['required', 'string', 'max:255'],
            'status' => ['required', 'in:active,inactive,maintenance,in_use'],
            'captain_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where('type', 'captain'),
            ],
        ];
    }
}
