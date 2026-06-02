<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRouteRequest extends FormRequest
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
            'start_point_en' => ['nullable', 'string', 'max:255'],
            'start_point_ar' => ['nullable', 'string', 'max:255'],
            'start_lat' => ['nullable', 'string', 'max:255'],
            'start_long' => ['nullable', 'string', 'max:255'],
            'end_point_en' => ['nullable', 'string', 'max:255'],
            'end_point_ar' => ['nullable', 'string', 'max:255'],
            'end_lat' => ['nullable', 'string', 'max:255'],
            'end_long' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', Rule::in(['b2b', 'b2c'])],
            'company_id' => ['nullable', 'integer', 'exists:users,id', 'required_if:type,b2b'],
            'is_active' => ['nullable', 'boolean'],
            'point_price' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
