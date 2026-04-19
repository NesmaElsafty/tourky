<?php

namespace App\Http\Resources;

use App\Models\Reservation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Client view of an assigned trip: route, point, pickup time, captain, car only.
 *
 * @mixin Reservation
 */
class ClientTripResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $locale = $this->resolveLocale($request);
        app()->setLocale($locale);
        return [
            'reservation_id' => $this->id,
            'date' => $this->date,
            'route' => $this->when(
                $this->relationLoaded('route') && $this->route !== null,
                fn () => [
                    'id' => $this->route->id,
                    'name' => $this->localizedModel($this->route, 'name_en', 'name_ar', $locale),
                    'name_en' => $this->route->name_en,
                    'name_ar' => $this->route->name_ar,
                ]
            ),
            'point' => $this->when(
                $this->relationLoaded('point') && $this->point !== null,
                fn () => [
                    'id' => $this->point->id,
                    'name' => $this->localizedModel($this->point, 'name_en', 'name_ar', $locale),
                    'name_en' => $this->point->name_en,
                    'name_ar' => $this->point->name_ar,
                    'lat' => $this->point->lat,
                    'long' => $this->point->long,
                ]
            ),
            'pickup_time' => $this->when(
                $this->relationLoaded('time') && $this->time !== null,
                fn () => $this->time->pickup_time
            ),
            'captain' => $this->when(
                $this->relationLoaded('tripCar')
                    && $this->tripCar !== null
                    && $this->tripCar->relationLoaded('captain')
                    && $this->tripCar->captain !== null,
                fn () => [
                    'id' => $this->tripCar->captain->id,
                    'name' => $this->tripCar->captain->name,
                    'phone' => $this->tripCar->captain->phone,
                ]
            ),
            'car' => $this->when(
                $this->relationLoaded('tripCar')
                    && $this->tripCar !== null
                    && $this->tripCar->relationLoaded('car')
                    && $this->tripCar->car !== null,
                fn () => [
                    'id' => $this->tripCar->car?->id,
                    'name' => $this->tripCar->car?->name,
                    'number_of_seats' => $this->tripCar->car?->number_of_seats,
                    'plate_numbers' => $this->tripCar->car?->plate_numbers,
                    'plate_letters' => $this->tripCar->car?->plate_letters,
                    'color' => $this->tripCar->car?->color,
                    'type' => $this->tripCar->car?->type,
                ]
            ),
        ];
    }

    /**
     * @param  Model  $model
     */
    private function localizedModel($model, string $enKey, string $arKey, string $locale): ?string
    {
        $en = $model->{$enKey};
        $ar = $model->{$arKey};

        if ($locale === 'ar') {
            return $ar ?? $en;
        }

        return $en ?? $ar;
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
