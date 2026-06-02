<?php

namespace App\Http\Requests\Client;

use Illuminate\Foundation\Http\FormRequest;

class StoreReportRequest extends FormRequest
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
            'type' => ['required', 'in:trip,captain'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type.required' => __('api.reports.validation_type_required'),
            'type.in' => __('api.reports.validation_type_in'),
            'message.required' => __('api.reports.validation_message_required'),
            'message.min' => __('api.reports.validation_message_min'),
            'message.max' => __('api.reports.validation_message_max'),
        ];
    }
}
