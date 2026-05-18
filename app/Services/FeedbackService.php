<?php

namespace App\Services;

use App\Models\Feedback;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class FeedbackService
{
    /**
     * @param  array{captain_id: int, feedback: string, rating: int}  $data
     */
    public function createForClient(User $client, array $data): Feedback
    {
        $captainId = (int) $data['captain_id'];

        if ($captainId === (int) $client->id) {
            throw ValidationException::withMessages([
                'captain_id' => [__('api.feedbacks.cannot_rate_self')],
            ]);
        }

        $captain = User::query()
            ->where('id', $captainId)
            ->where('type', 'captain')
            ->first();

        if ($captain === null) {
            throw ValidationException::withMessages([
                'captain_id' => [__('api.feedbacks.captain_not_found')],
            ]);
        }

        if (! $this->clientHasCompletedTripWithCaptain($client, $captainId)) {
            throw ValidationException::withMessages([
                'captain_id' => [__('api.feedbacks.no_completed_trip')],
            ]);
        }

        if ($this->feedbackAlreadyExists($client, $captainId)) {
            throw ValidationException::withMessages([
                'captain_id' => [__('api.feedbacks.feedback_already_exists')],
            ]);
        }

        return Feedback::query()->create([
            'client_id' => $client->id,
            'captain_id' => $captainId,
            'feedback' => $data['feedback'],
            'rating' => $data['rating'],
        ]);
    }

    public function clientHasCompletedTripWithCaptain(User $client, int $captainId): bool
    {
        return Reservation::query()
            ->where('user_id', $client->id)
            ->whereNotNull('dropped_off_at')
            ->whereHas('tripCar', function (Builder $q) use ($captainId): void {
                $q->where('captain_id', $captainId);
            })
            ->exists();
    }

    public function feedbackAlreadyExists(User $client, int $captainId, int $reservationId): bool
    {
        return Feedback::query()
            ->where('client_id', $client->id)
            ->where('captain_id', $captainId)
            ->where(column: 'created_at', operator: '>=', value: now()->subDays(30))
            ->exists();
    }
}
