<?php

namespace App\Http\Resources;

use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Trip
 */
class CaptainTripListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $locale = $this->resolveLocale($request);
        app()->setLocale($locale);

        $route = $this->relationLoaded('time') && $this->time !== null
            && $this->time->relationLoaded('point') && $this->time->point !== null
            && $this->time->point->relationLoaded('route') && $this->time->point->route !== null
            ? $this->time->point->route
            : null;

        $tripCar = $this->relationLoaded('tripCars') ? $this->tripCars->first() : null;
        $car = $tripCar !== null && $tripCar->relationLoaded('car') ? $tripCar->car : null;

        return [
            'id' => $this->id,
            'date' => $this->date,
            'status' => $this->status,
            'route' => $route !== null ? [
                'id' => $route->id,
                'name' => $locale === 'ar' ? ($route->name_ar ?? $route->name_en) : ($route->name_en ?? $route->name_ar),
            ] : null,
            'pickup_time' => $this->when(
                $this->relationLoaded('time') && $this->time !== null,
                fn () => $this->time->pickup_time
            ),
            'car' => $car !== null ? [
                'id' => $car->id,
                'name' => $car->name,
                'type' => $car->type,
                'number_of_seats' => $car->number_of_seats,
                'plate_numbers' => $car->plate_numbers,
                'plate_letters' => $car->plate_letters,
                'color' => $car->color,
            ] : null,
            'my_clients_count' => (int) ($this->my_clients_count ?? 0),
        ];
    }

    private function resolveLocale(Request $request): string
    {
        $user = $request->user();
        if ($user !== null) {
            $language = strtolower((string) $user->getAttribute('language'));
            if ($language === 'en' || $language === 'ar') {
                return $language;
            }
        }

        $headerLanguage = strtolower((string) $request->header('lang', ''));

        return $headerLanguage === 'ar' ? 'ar' : 'en';
    }
}
