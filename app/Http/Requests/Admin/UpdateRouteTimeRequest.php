<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRouteTimeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'route_id' => ['sometimes', 'required', 'integer', 'exists:routes,id'],
            'time_ids' => ['sometimes', 'required', 'array', 'min:1'],
            'time_ids.*' => ['required_with:time_ids', 'integer', 'exists:times,id'],
        ];
    }
}
