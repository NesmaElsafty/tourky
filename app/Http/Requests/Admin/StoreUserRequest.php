<?php

namespace App\Http\Requests\Admin;

use App\Support\CaptainDocumentCollections;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $type = (string) $this->input('type');
        $phoneRule = Rule::unique('users', 'phone')->where(
            fn ($query) => $query->where('type', $type),
        );
        $emailRule = Rule::unique('users', 'email')->where(
            fn ($query) => $query->where('type', $type),
        );

        return array_merge([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50', $phoneRule],
            'email' => ['nullable', 'string', 'email', 'max:255', $emailRule],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'type' => ['required', Rule::in(['admin', 'captain', 'client'])],
            'language' => ['required', Rule::in(['en', 'ar'])],
            'role_id' => [
                Rule::requiredIf($type === 'admin'),
                'nullable',
                'integer',
                'exists:roles,id',
            ],
            'image' => ['nullable', 'file', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
        ], $type === 'captain' ? array_merge(
            CaptainDocumentCollections::validationRules(),
            ['license_expiry_date' => ['nullable', 'date']],
        ) : []);
    }

    public function messages(): array
    {
        return [
            'name.required' => __('api.users.validation_name_required'),
            'phone.required' => __('api.users.validation_phone_required'),
            'phone.unique' => __('api.users.validation_phone_unique'),
            'email.unique' => __('api.users.validation_email_unique'),
            'password.required' => __('api.users.validation_password_required'),
            'type.required' => __('api.users.validation_type_required'),
            'type.in' => __('api.users.validation_type_invalid'),
            'language.required' => __('api.users.validation_language_required'),
            'role_id.required' => __('api.users.validation_role_required'),
            'role_id.exists' => __('api.users.validation_role_invalid'),
        ];
    }
}
