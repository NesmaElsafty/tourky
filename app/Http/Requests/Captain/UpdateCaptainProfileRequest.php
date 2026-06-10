<?php

namespace App\Http\Requests\Captain;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCaptainProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'phone' => ['sometimes', 'required', 'string', 'max:20', 'unique:users,phone,'.$this->user()->id.',id,type,captain'],
            'password' => ['sometimes', 'required', 'string', 'min:6', 'confirmed'],
            'fcm_token' => ['sometimes', 'nullable', 'string', 'max:512'],
        ];
    }
}
