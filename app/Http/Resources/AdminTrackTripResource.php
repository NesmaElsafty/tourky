<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\ResolvesApiLocale;
use App\Http\Resources\CaptainTrackTripResource;
use App\Models\TrackTrip;
use App\Support\TrackTripMessage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin TrackTrip
 */
class AdminTrackTripResource extends JsonResource
{
    use ResolvesApiLocale;

    public function toArray(Request $request): array
    {
        $locale = $this->resolveLocale($request);
        $location = CaptainTrackTripResource::parseLocation($this->message);

        return [
            'id' => $this->id,
            'trip_id' => $this->trip_id,
            'captain_id' => $this->captain_id,
            'car_id' => $this->car_id,
            'point_id' => $this->point_id,
            'client_id' => $this->client_id,
            'message' => $location === null
                ? TrackTripMessage::display($this->resource, $locale)
                : null,
            'trip' => $this->whenLoaded('trip', fn () => $this->trip === null ? null : [
                'id' => $this->trip->id,
                'date' => $this->trip->date,
                'status' => $this->trip->status,
                'pickup_time' => $this->trip->relationLoaded('time') && $this->trip->time !== null
                    ? $this->trip->time->pickup_time
                    : null,
            ]),
            'captain' => $this->whenLoaded('captain', fn () => $this->captain === null ? null : [
                'id' => $this->captain->id,
                'name' => $this->captain->name,
                'phone' => $this->captain->phone,
            ]),
            'car' => $this->whenLoaded('car', fn () => $this->car === null ? null : [
                'id' => $this->car->id,
                'plate_numbers' => $this->car->plate_numbers,
                'plate_letters' => $this->car->plate_letters,
            ]),
            'point' => $this->whenLoaded('point', fn () => $this->point === null ? null : [
                'id' => $this->point->id,
                'name' => $locale === 'ar'
                    ? ($this->point->name_ar ?: $this->point->name_en)
                    : ($this->point->name_en ?: $this->point->name_ar),
            ]),
            'client' => $this->whenLoaded('client', fn () => $this->client === null ? null : [
                'id' => $this->client->id,
                'name' => $this->client->name,
                'phone' => $this->client->phone,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
