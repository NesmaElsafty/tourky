<?php

namespace App\Http\Requests\Admin;

use App\Models\Term;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTermRequest extends FormRequest
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
            'description_en' => ['nullable', 'string', 'max:255'],
            'description_ar' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
            'type' => ['required', Rule::in(Term::TYPES)],
            'user_type' => ['required', Rule::in(Term::USER_TYPES)],
        ];
    }
}
