<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use App\Support\CaptainDocumentCollections;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = (int) $this->route('id');
        $user = User::query()->withTrashed()->find($id);
        $type = (string) ($user?->type ?? 'client');

        $phoneRule = Rule::unique('users', 'phone')->where(
            fn ($query) => $query->where('type', $type),
        )->ignore($id);

        $emailRule = Rule::unique('users', 'email')->where(
            fn ($query) => $query->where('type', $type),
        )->ignore($id);

        return array_merge([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'phone' => ['sometimes', 'required', 'string', 'max:50', $phoneRule],
            'email' => ['nullable', 'string', 'email', 'max:255', $emailRule],
            'password' => ['sometimes', 'nullable', 'string', 'min:6', 'confirmed'],
            'language' => ['sometimes', 'required', Rule::in(['en', 'ar'])],
            'role_id' => ['sometimes', 'nullable', 'integer', 'exists:roles,id'],
            'image' => ['nullable', 'file', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'balance' => ['sometimes', 'required', 'numeric'],
        ], $type === 'captain' ? array_merge(
            CaptainDocumentCollections::validationRules(),
            ['license_expiry_date' => ['sometimes', 'nullable', 'date']],
        ) : []);
    }

    public function messages(): array
    {
        return [
            'phone.unique' => __('api.users.validation_phone_unique'),
            'email.unique' => __('api.users.validation_email_unique'),
            'role_id.exists' => __('api.users.validation_role_invalid'),
        ];
    }
}
