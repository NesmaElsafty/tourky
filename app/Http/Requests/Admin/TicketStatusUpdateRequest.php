<?php

namespace App\Http\Requests\Admin;

use App\Models\Ticket;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TicketStatusUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(Ticket::STATUSES)],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => __('api.tickets.validation_status_required'),
            'status.in' => __('api.tickets.validation_status_in'),
        ];
    }
}
