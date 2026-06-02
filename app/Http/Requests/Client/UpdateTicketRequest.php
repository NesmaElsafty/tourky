<?php

namespace App\Http\Requests\Client;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTicketRequest extends FormRequest
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
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string', 'min:1', 'max:10000'],
            'captain_id' => ['sometimes', 'nullable', 'integer', Rule::exists('users', 'id')->where('type', 'captain')],
            'trip_id' => ['sometimes', 'nullable', 'integer', 'exists:trips,id'],
        ];
    }
}
