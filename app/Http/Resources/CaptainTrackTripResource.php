<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\ResolvesApiLocale;
use App\Models\TrackTrip;
use App\Support\TrackTripMessage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin TrackTrip
 */
class CaptainTrackTripResource extends JsonResource
{
    use ResolvesApiLocale;

    public function toArray(Request $request): array
    {
        $locale = $this->resolveLocale($request);
        $location = self::parseLocation($this->message);

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
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array{lat: float, long: float}|null
     */
    public static function parseLocation(?string $message): ?array
    {
        if ($message === null || $message === '') {
            return null;
        }

        $decoded = json_decode($message, true);

        if (! is_array($decoded) || ! isset($decoded['lat'], $decoded['long'])) {
            return null;
        }

        return [
            'lat' => (float) $decoded['lat'],
            'long' => (float) $decoded['long'],
        ];
    }
}
