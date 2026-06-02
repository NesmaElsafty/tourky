<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreTransactionRequest extends FormRequest
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
            'client_id' => ['required', 'integer', 'exists:users,id'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'transaction_status' => ['required', 'in:pending,accepted,rejected'],
            'transaction_method' => ['required', 'in:cash,bank_transfer,wallet,online_payment'],
            'transaction_type' => ['sometimes', 'in:manual,gateway'],
            'image' => ['sometimes', 'nullable', 'file', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'client_id.required' => __('api.transactions.validation_client_required'),
            'client_id.exists' => __('api.transactions.validation_client_invalid'),
            'amount.required' => __('api.transactions.validation_amount_required'),
            'amount.numeric' => __('api.transactions.validation_amount_numeric'),
            'amount.gt' => __('api.transactions.validation_amount_gt'),
            'transaction_status.required' => __('api.transactions.validation_status_required'),
            'transaction_status.in' => __('api.transactions.validation_status_invalid'),
            'transaction_method.required' => __('api.transactions.validation_method_required'),
            'transaction_method.in' => __('api.transactions.validation_method_invalid'),
            'transaction_type.in' => __('api.transactions.validation_type_invalid'),
            'image.mimes' => __('api.transactions.validation_image_mimes'),
            'image.max' => __('api.transactions.validation_image_max'),
        ];
    }
}
