<?php

namespace App\Http\Requests\Captain;

use Illuminate\Foundation\Http\FormRequest;

class TrackingLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'long' => ['required', 'numeric', 'between:-180,180'],
        ];
    }
}
