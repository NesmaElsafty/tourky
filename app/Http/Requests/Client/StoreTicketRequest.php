<?php

namespace App\Http\Requests\Client;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTicketRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'min:1', 'max:10000'],
            'captain_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where('type', 'captain')],
            'trip_id' => ['nullable', 'integer', 'exists:trips,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => __('api.tickets.validation_title_required'),
            'description.required' => __('api.tickets.validation_description_required'),
        ];
    }
}
