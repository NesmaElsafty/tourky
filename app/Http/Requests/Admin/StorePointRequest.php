<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StorePointRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name_en' => ['required', 'string', 'max:255'],
            'name_ar' => ['required', 'string', 'max:255'],
            'lat' => ['nullable', 'string', 'max:255'],
            'long' => ['nullable', 'string', 'max:255'],
            'route_id' => ['required', 'exists:routes,id'],
            'times' => ['required', 'array'],
            'times.*.pickup_time' => ['required', 'string', 'max:255'],
            'times.*.is_active' => ['required', 'boolean'],
        ];
    }
}
