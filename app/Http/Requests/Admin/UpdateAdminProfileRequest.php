<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAdminProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'phone' => ['sometimes', 'required', 'string', 'max:20', 'unique:users,phone,'.$userId.',id,type,admin'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255', 'unique:users,email,'.$userId.',id,type,admin'],
            'password' => ['sometimes', 'required', 'string', 'min:6', 'confirmed'],
            'language' => ['sometimes', 'in:en,ar'],
            'fcm_token' => ['sometimes', 'nullable', 'string', 'max:512'],
            'image' => ['sometimes', 'image', 'max:5120'],
        ];
    }
}
