<?php

namespace App\Http\Requests\Captain;

use Illuminate\Foundation\Http\FormRequest;

class CaptainOnlineToggleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'image' => ['required', 'image', 'mimes:jpeg,png,jpg,gif,svg'],
        ];
    }
}
