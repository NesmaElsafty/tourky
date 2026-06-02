<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class TermIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'in:terms_conditions,privacy_policy,FAQ'],
            'user_type' => ['required', 'in:client,captain'],
        ];
    }
}
