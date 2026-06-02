<?php

namespace App\Http\Requests\Client;

use Illuminate\Foundation\Http\FormRequest;

class RateCaptainRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'feedback' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'rating.required' => __('api.trips.captain_rating_validation_required'),
            'rating.integer' => __('api.trips.captain_rating_validation_integer'),
            'rating.min' => __('api.trips.captain_rating_validation_min'),
            'rating.max' => __('api.trips.captain_rating_validation_max'),
            'feedback.max' => __('api.trips.captain_feedback_validation_max'),
        ];
    }
}
