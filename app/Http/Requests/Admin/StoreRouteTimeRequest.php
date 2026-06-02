<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreRouteTimeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'route_id' => ['required', 'integer', 'exists:routes,id'],
            'time_ids' => ['required', 'array', 'min:1'],
            'time_ids.*' => ['required', 'integer', 'exists:times,id'],
        ];
    }
}
