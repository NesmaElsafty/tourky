<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\NotificationDelivery;
use App\Models\Route;
use App\Models\Trip;
use App\Models\TripCar;

class CaptainTripNotificationService
{
    /**
     * One inbox notification per captain vehicle on the trip (title + description by language).
     */
    public function notifyCaptainsTripCreated(int $tripId): void
    {
        $trip = Trip::query()
            ->with([
                'time.point.route:id,name_en,name_ar',
                'tripCars.car:id,name,type',
                'tripCars.captain:id',
            ])
            ->find($tripId);

        if ($trip === null || $trip->time === null || $trip->time->point === null || $trip->time->point->route === null) {
            return;
        }

        $route = $trip->time->point->route;
        $pickupTime = (string) $trip->time->pickup_time;

        foreach ($trip->tripCars as $tripCar) {
            if ($tripCar->car === null) {
                continue;
            }

            $titleEn = __('api.captain_trips.notification_title_new', [], 'en');
            $titleAr = __('api.captain_trips.notification_title_new', [], 'ar');

            $notification = Notification::query()->create([
                'user_type' => 'captain',
                'title_en' => $titleEn,
                'title_ar' => $titleAr,
                'description_en' => $this->buildDescription($route, $pickupTime, $tripCar, 'en'),
                'description_ar' => $this->buildDescription($route, $pickupTime, $tripCar, 'ar'),
            ]);

            NotificationDelivery::query()->create([
                'notification_id' => $notification->id,
                'user_id' => $tripCar->captain_id,
            ]);
        }
    }

    private function buildDescription(Route $route, string $pickupTime, TripCar $tripCar, string $locale): string
    {
        $routeLabel = $locale === 'ar'
            ? ((string) ($route->name_ar ?? $route->name_en))
            : ((string) ($route->name_en ?? $route->name_ar));

        $typeKey = 'api.cars.type_labels.'.(string) $tripCar->car->type;
        $typeLabel = __($typeKey, [], $locale === 'ar' ? 'ar' : 'en');
        if ($typeLabel === $typeKey) {
            $typeLabel = (string) $tripCar->car->type;
        }

        $separator = ' · ';

        return $routeLabel.$separator.$pickupTime.$separator.$typeLabel;
    }
}
