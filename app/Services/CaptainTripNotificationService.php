<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\NotificationDelivery;
use App\Models\Reservation;
use App\Models\Route;
use App\Models\Trip;
use App\Models\TripCar;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class CaptainTripNotificationService
{
    public function __construct(private NotificationService $notificationService) {}

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

            $captain = $tripCar->captain;
            if ($captain !== null && $captain->hasFcmToken()) {
                $notification->pushToUser($captain);
            }
        }
    }

    public function notifyTripStarted(User $captain, Trip $trip): void
    {
        $this->notifyTripStatusChange($captain, $trip, 'started');
    }

    public function notifyTripCancelled(User $captain, Trip $trip): void
    {
        $this->notifyTripStatusChange($captain, $trip, 'cancelled');
    }

    private function notifyTripStatusChange(User $captain, Trip $trip, string $event): void
    {
        $trip->loadMissing(['time.point.route']);

        if ($trip->time === null) {
            return;
        }

        $route = $trip->time->point?->route;
        $pickupTime = (string) $trip->time->pickup_time;
        $date = (string) $trip->date;
        $fcmType = $event === 'started' ? 'trip_started' : 'trip_cancelled';

        $titleKey = $event === 'started'
            ? 'api.captain_trips.notification_title_trip_started'
            : 'api.captain_trips.notification_title_trip_cancelled';
        $clientBodyKey = $event === 'started'
            ? 'api.captain_trips.notification_trip_started_client'
            : 'api.captain_trips.notification_trip_cancelled_client';
        $adminBodyKey = $event === 'started'
            ? 'api.captain_trips.notification_trip_started_admin'
            : 'api.captain_trips.notification_trip_cancelled_admin';

        $paramsEn = [
            'captain' => $captain->name,
            'trip_id' => (string) $trip->id,
            'route' => $this->routeLabel($route, 'en'),
            'date' => $date,
            'time' => $pickupTime,
        ];
        $paramsAr = [
            'captain' => $captain->name,
            'trip_id' => (string) $trip->id,
            'route' => $this->routeLabel($route, 'ar'),
            'date' => $date,
            'time' => $pickupTime,
        ];

        $clientContent = [
            'title_en' => __($titleKey, [], 'en'),
            'title_ar' => __($titleKey, [], 'ar'),
            'description_en' => trans($clientBodyKey, $paramsEn, 'en'),
            'description_ar' => trans($clientBodyKey, $paramsAr, 'ar'),
        ];
        $adminContent = [
            'title_en' => __($titleKey, [], 'en'),
            'title_ar' => __($titleKey, [], 'ar'),
            'description_en' => trans($adminBodyKey, $paramsEn, 'en'),
            'description_ar' => trans($adminBodyKey, $paramsAr, 'ar'),
        ];
        $fcmData = [
            'type' => $fcmType,
            'trip_id' => (string) $trip->id,
        ];

        $reservationStatuses = $event === 'started' ? ['pending', 'confirmed'] : null;

        foreach ($this->tripClients($trip, $reservationStatuses) as $client) {
            $this->notificationService->deliverDirectToUser($client, $clientContent, $fcmData);
        }

        foreach ($this->admins() as $admin) {
            $this->notificationService->deliverDirectToUser($admin, $adminContent, $fcmData);
        }
    }

    /**
     * @param  list<string>|null  $reservationStatuses
     * @return Collection<int, User>
     */
    private function tripClients(Trip $trip, ?array $reservationStatuses = null): Collection
    {
        $query = Reservation::query()
            ->where('trip_id', $trip->id)
            ->whereNotNull('user_id');

        if ($reservationStatuses !== null) {
            $query->whereIn('status', $reservationStatuses);
        }

        $clientIds = $query->distinct()->pluck('user_id');

        if ($clientIds->isEmpty()) {
            return new Collection;
        }

        return User::query()
            ->whereIn('id', $clientIds)
            ->where('type', 'client')
            ->get();
    }

    /**
     * @return Collection<int, User>
     */
    private function admins(): Collection
    {
        return User::query()->where('type', 'admin')->get();
    }

    private function routeLabel(?Route $route, string $locale): string
    {
        if ($route === null) {
            return '';
        }

        return $locale === 'ar'
            ? (string) ($route->name_ar ?: $route->name_en)
            : (string) ($route->name_en ?: $route->name_ar);
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
