<?php

namespace App\Http\Requests\Client;

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
            'amount' => ['required', 'numeric', 'gt:0'],
            'transaction_method' => ['required', 'in:bank_transfer,wallet'],
            'image' => ['required', 'file', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'amount.required' => __('api.transactions.validation_amount_required'),
            'amount.numeric' => __('api.transactions.validation_amount_numeric'),
            'amount.gt' => __('api.transactions.validation_amount_gt'),
            'transaction_method.required' => __('api.transactions.validation_method_required'),
            'transaction_method.in' => __('api.transactions.validation_method_client_invalid'),
            'image.required' => __('api.transactions.validation_image_required'),
            'image.mimes' => __('api.transactions.validation_image_mimes'),
            'image.max' => __('api.transactions.validation_image_max'),
        ];
    }
}
