<?php

namespace App\Http\Requests\Client;

use Illuminate\Foundation\Http\FormRequest;

class StoreFeedbackRequest extends FormRequest
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
            'captain_id' => ['required', 'integer', 'exists:users,id'],
            'feedback' => ['required', 'string', 'min:3', 'max:5000'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'captain_id.required' => __('api.feedbacks.validation_captain_required'),
            'feedback.required' => __('api.feedbacks.validation_feedback_required'),
            'rating.required' => __('api.feedbacks.validation_rating_required'),
        ];
    }
}
