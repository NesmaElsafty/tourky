<?php

namespace App\Http\Resources;

use App\Models\Point;
use App\Models\Reservation;
use App\Models\Route;
use App\Models\RouteTime;
use App\Models\Time;
use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

/**
 * @mixin Trip
 */
class CaptainTripDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $locale = $this->resolveLocale($request);
        app()->setLocale($locale);

        $tripCar = $this->relationLoaded('tripCars') ? $this->tripCars->first() : null;
        $car = $tripCar !== null && $tripCar->relationLoaded('car') ? $tripCar->car : null;
        $captain = $tripCar !== null && $tripCar->relationLoaded('captain') && $tripCar->captain !== null
            ? $tripCar->captain
            : null;

        $captainTripCarIds = $this->relationLoaded('tripCars')
            ? $this->tripCars->pluck('id')->map(static fn ($id): int => (int) $id)
            : collect();

        $myReservations = $this->relationLoaded('reservations')
            ? $this->reservations->filter(
                static fn (Reservation $reservation): bool => $captainTripCarIds->contains((int) $reservation->trip_car_id)
            )
            : collect();

        $route = $this->relationLoaded('routeTime')
            && $this->routeTime !== null
            && $this->routeTime->relationLoaded('route')
            && $this->routeTime->route !== null
            ? $this->routeTime->route
            : null;

        return [
            'id' => $this->id,
            'route_time_id' => $this->route_time_id,
            'date' => $this->date,
            'status' => $this->status,
            'pickup_time' => $this->relationLoaded('time') && $this->time !== null
                ? $this->time->pickup_time
                : null,
            'start_point' => $this->formatRouteStartPoint($route, $locale),
            'car' => $car !== null ? [
                'id' => $car->id,
                'name' => $car->name,
                'type' => $car->type,
                'number_of_seats' => $car->number_of_seats,
                'plate_numbers' => $car->plate_numbers,
                'plate_letters' => $car->plate_letters,
                'color' => $car->color,
            ] : null,
            'captain' => $captain !== null ? [
                'id' => $captain->id,
                'name' => $captain->name,
                'phone' => $captain->phone,
                'lat' => $captain->lat,
                'long' => $captain->long,
                'status' => $captain->status,
                'has_trip' => $captain->has_trip,
                'trip_id' => $captain->trip_id,
            ] : null,
            'route_time' => $this->formatRouteTimeBlock(
                $this->relationLoaded('routeTime') ? $this->routeTime : null,
                $myReservations,
                $locale,
            ),
            'rejection_reports' => $this->when(
                $this->relationLoaded('reports'),
                fn () => CaptainRejectionReportResource::collection($this->reports),
            ),
        ];
    }

    private function formatRouteTimeBlock(?RouteTime $routeTime, Collection $myReservations, string $locale): ?array
    {
        if ($routeTime === null) {
            return null;
        }

        $route = $routeTime->relationLoaded('route') ? $routeTime->route : null;

        $timeIds = collect($routeTime->time_ids ?? [])
            ->map(static fn ($id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->values();

        $timesById = $timeIds->isEmpty()
            ? collect()
            : Time::query()
                ->with('point:id,route_id,name_en,name_ar,lat,long')
                ->whereIn('id', $timeIds->all())
                ->get()
                ->keyBy('id');

        $points = [];
        foreach ($timeIds as $timeId) {
            /** @var Time|null $time */
            $time = $timesById->get($timeId);
            if ($time === null || $time->point === null) {
                continue;
            }

            $point = $time->point;
            $clients = $myReservations
                ->filter(static function (Reservation $reservation) use ($timeId): bool {
                    return (int) $reservation->time_id === $timeId
                        || (int) $reservation->drop_off_time_id === $timeId;
                })
                ->map(fn (Reservation $reservation): array => $this->formatClientReservation($reservation, $timeId))
                ->values()
                ->all();

            $points[] = [
                'id' => $point->id,
                'name' => $this->localizedPointName($point, $locale),
                'name_en' => $point->name_en,
                'name_ar' => $point->name_ar,
                'lat' => $point->lat,
                'long' => $point->long,
                'time' => [
                    'id' => $time->id,
                    'pickup_time' => $time->pickup_time,
                ],
                'clients' => $clients,
            ];
        }

        return [
            'id' => $routeTime->id,
            'route' => $route !== null ? [
                'id' => $route->id,
                'name' => $this->localizedRouteName($route, $locale),
                'name_en' => $route->name_en,
                'name_ar' => $route->name_ar,
            ] : null,
            'points' => $points,
        ];
    }

    /**
     * @return array{
     *     name: string,
     *     name_en: ?string,
     *     name_ar: ?string,
     *     lat: mixed,
     *     long: mixed
     * }|null
     */
    private function formatRouteStartPoint(?Route $route, string $locale): ?array
    {
        if ($route === null) {
            return null;
        }

        $nameEn = $route->start_point_en;
        $nameAr = $route->start_point_ar;

        return [
            'name' => $locale === 'ar'
                ? ($nameAr ?? $nameEn ?? '')
                : ($nameEn ?? $nameAr ?? ''),
            'name_en' => $nameEn,
            'name_ar' => $nameAr,
            'lat' => $route->start_lat,
            'long' => $route->start_long,
        ];
    }

    /**
     * @return array{
     *     reservation_id: int,
     *     stop_type: string,
     *     id: int|null,
     *     name: string|null,
     *     phone: string|null
     * }
     */
    private function formatClientReservation(Reservation $reservation, int $stopTimeId): array
    {
        $isPickup = (int) $reservation->time_id === $stopTimeId;
        $isDropoff = (int) $reservation->drop_off_time_id === $stopTimeId;

        $stopType = match (true) {
            $isPickup && $isDropoff => 'pickup_and_dropoff',
            $isPickup => 'pickup',
            $isDropoff => 'dropoff',
            default => 'unknown',
        };

        $user = $reservation->relationLoaded('user') ? $reservation->user : null;

        return [
            'id' => $user?->id,
            'name' => $user?->name,
            'phone' => $user?->phone,
            'reservation_id' => $reservation->id,
            'stop_type' => $stopType,
        ];
    }

    private function localizedRouteName(Route $route, string $locale): string
    {
        return $locale === 'ar'
            ? (string) ($route->name_ar ?? $route->name_en ?? '')
            : (string) ($route->name_en ?? $route->name_ar ?? '');
    }

    private function localizedPointName(Point $point, string $locale): string
    {
        return $locale === 'ar'
            ? (string) ($point->name_ar ?? $point->name_en ?? '')
            : (string) ($point->name_en ?? $point->name_ar ?? '');
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
