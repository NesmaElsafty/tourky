<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePointRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name_en' => ['sometimes', 'required', 'string', 'max:255'],
            'name_ar' => ['sometimes', 'required', 'string', 'max:255'],
            'lat' => ['nullable', 'string', 'max:255'],
            'long' => ['nullable', 'string', 'max:255'],
            'route_id' => ['sometimes', 'required', 'exists:routes,id'],
        ];
    }
}
