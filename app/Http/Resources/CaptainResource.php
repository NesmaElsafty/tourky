<?php

namespace App\Http\Resources;

use App\Services\CaptainRatingService;
use App\Http\Resources\FeedbackResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CaptainResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $locale = $this->resolveLocale($request);
        app()->setLocale($locale);

        $rating = app(CaptainRatingService::class)->aggregateForCaptainId((int) $this->id);

        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'type' => $this->type,
            'language' => $locale,
            'rating_average' => $rating['average'],
            'ratings_count' => $rating['count'],
            // feedbacks is the feedbacks of the captain take last 7 feedbacks
            'feedbacks' => $this->receivedFeedbacks->take(7),
            'lat' => $this->when($this->type === 'captain', $this->lat),
            'long' => $this->when($this->type === 'captain', $this->long),
            'status' => $this->when($this->type === 'captain', $this->status),
            'has_trip' => $this->when($this->type === 'captain', $this->has_trip),
            'trip_id' => $this->when($this->type === 'captain', $this->trip_id),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

        ];

        if ($this->resource->offsetExists('captain_feedback_entries')) {
            $data['feedback_entries'] = $this->resource->getAttribute('captain_feedback_entries');
        }

        if ($this->resource->offsetExists('captain_report_entries')) {
            $data['report_entries'] = $this->resource->getAttribute('captain_report_entries');
        }

        $data['feedbacks'] = $this->when(
            $this->type === 'captain' && $this->relationLoaded('receivedFeedbacks'),
            fn () => FeedbackResource::collection($this->receivedFeedbacks),
        );

        return $data;
    }

    private function resolveLocale(Request $request): string
    {
        $userLanguage = strtolower((string) ($this->language ?? ''));
        if ($userLanguage === 'en' || $userLanguage === 'ar') {
            return $userLanguage;
        }

        $headerLanguage = strtolower((string) $request->header('lang', ''));

        return $headerLanguage === 'ar' ? 'ar' : 'en';
    }
}
