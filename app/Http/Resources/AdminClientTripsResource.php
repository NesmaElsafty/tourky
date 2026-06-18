<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Concerns\ResolvesApiLocale;
class AdminClientTripsResource extends JsonResource
{
    use ResolvesApiLocale;
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $locale = $this->resolveLocale($request);
        app()->setLocale($locale);
        
        return [

            'reservation_id' => $this->id, // this is the reservation id
            'status' => $this->status,
            'status_label' => __('api.reservations.status_labels.'.$this->status),
            'route' => $this->when(
                $this->relationLoaded('route') && $this->route !== null,
                fn () => [
                    'id' => $this->route->id,
                    'name' => $this->localizedModel($this->route, 'name_en', 'name_ar', $locale),
                ],
            ),
            'trip_id' => $this->trip_id,
            'car' => $this->when(
                $this->relationLoaded('car') && $this->car !== null,
                fn () => [
                    'id' => $this->car->id,
                    'name' => $this->car->name,
                ],
            ),
            'captain' => $this->when(
                $this->relationLoaded('captain') && $this->captain !== null,
                fn () => [
                    'id' => $this->captain->id,
                    'name' => $this->captain->name,
                ],
            ),
            'time' => $this->whenLoaded('time', fn () => $this->time->pickup_time),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
