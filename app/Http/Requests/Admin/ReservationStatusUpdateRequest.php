<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ReservationStatusUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:confirmed,cancelled'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => __('api.reservations.validation_status_required'),
            'status.in' => __('api.reservations.validation_status_in'),
        ];
    }
}
