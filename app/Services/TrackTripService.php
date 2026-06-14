<?php

namespace App\Services;

use App\Models\Point;
use App\Models\Reservation;
use App\Models\TrackTrip;
use App\Models\Trip;
use App\Models\TripCar;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TrackTripService
{
    public function __construct(
        private NotificationService $notificationService,
    ) {}

    /**
     * @param  array{
     *     trip_id: int,
     *     car_id: int,
     *     captain_id: int,
     *     point_id?: int|null
     * }  $data
     */
    public function storeTrackTripForCaptain(User $captain, array $data): TrackTrip
    {
        $this->assertValidCaptain($captain, (int) $data['captain_id']);

        $trip = Trip::query()->findOrFail((int) $data['trip_id']);
        $this->assertTripInProgress($trip);

        $tripCar = $this->assertCarAssignedToCaptain(
            $trip,
            $captain,
            (int) $data['car_id'],
        );

        $pointId = (int) $data['point_id'];
        $this->assertValidPointForTrip($trip, $pointId);

        $trackTrip = TrackTrip::query()->create([
            'trip_id' => $trip->id,
            'captain_id' => $captain->id,
            'car_id' => (int) $data['car_id'],
            'point_id' => $pointId,
            'message' => null,
        ]);

        $this->notifyClientsCaptainArrived($captain, $tripCar, $pointId, $trackTrip);

        return $trackTrip->load([
            'captain:id,name',
            'point:id,name_en,name_ar',
        ]);
    }

    public function storeLocationForCaptain(User $captain, Trip $trip, float $lat, float $long): TrackTrip
    {
        $tripCar = $this->resolveTripCar($captain, $trip);

        $this->assertTripInProgress($trip);

        return TrackTrip::query()->create([
            'trip_id' => $trip->id,
            'captain_id' => $captain->id,
            'car_id' => $tripCar->car_id,
            'message' => json_encode([
                'lat' => round($lat, 6),
                'long' => round($long, 6),
            ], JSON_THROW_ON_ERROR),
        ]);
    }

    public function recordReservationPickup(User $captain, Trip $trip, Reservation $reservation): TrackTrip
    {
        return $this->recordReservationLog($captain, $trip, $reservation);
    }

    public function recordReservationDropoff(User $captain, Trip $trip, Reservation $reservation): TrackTrip
    {
        return $this->recordReservationLog($captain, $trip, $reservation);
    }

    public function recordClientRejection(User $captain, Trip $trip, Reservation $reservation, string $reason): TrackTrip
    {
        $trackTrip = $this->recordReservationLog($captain, $trip, $reservation, $reason);

        $reservation->loadMissing(['user:id,name,language', 'point:id,name_en,name_ar']);
        $client = $reservation->user;

        if ($client !== null) {
            $this->notifyClientRejected($captain, $reservation, $reason, $trackTrip);
        }

        return $trackTrip;
    }

    /**
     * @return LengthAwarePaginator<int, TrackTrip>
     */
    public function paginateForAdmin(Request $request, int $perPage = 20): LengthAwarePaginator
    {
        $request->validate([
            'trip_id' => ['sometimes', 'integer', 'min:1'],
            'captain_id' => ['sometimes', 'integer', 'min:1'],
            'car_id' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $perPage = (int) $request->input('per_page', $perPage);

        $query = TrackTrip::query()
            ->with([
                'trip:id,date,status',
                'captain:id,name,phone',
                'car:id,plate_numbers,plate_letters',
                'point:id,name_en,name_ar',
                'client:id,name,phone',
            ]);

        if ($request->filled('trip_id')) {
            $query->where('trip_id', (int) $request->input('trip_id'));
        }

        if ($request->filled('captain_id')) {
            $query->where('captain_id', (int) $request->input('captain_id'));
        }

        if ($request->filled('car_id')) {
            $query->where('car_id', (int) $request->input('car_id'));
        }

        return $query
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function findForAdmin(int $id): TrackTrip
    {
        return TrackTrip::query()
            ->with([
                'trip:id,date,status,time_id',
                'trip.time:id,pickup_time',
                'captain:id,name,phone',
                'car:id,plate_numbers,plate_letters',
                'point:id,name_en,name_ar',
                'client:id,name,phone',
            ])
            ->findOrFail($id);
    }

    /**
     * Clients booked on this vehicle at this pickup point (confirmed or pending only).
     *
     * @return Collection<int, User>
     */
    private function clientsAtPointOnTripCar(TripCar $tripCar, int $pointId): Collection
    {
        $clientIds = Reservation::query()
            ->where('trip_id', $tripCar->trip_id)
            ->where('trip_car_id', $tripCar->id)
            ->where('point_id', $pointId)
            ->whereIn('status', ['confirmed', 'pending'])
            ->pluck('user_id');

        if ($clientIds->isEmpty()) {
            return new Collection;
        }

        return User::query()
            ->whereIn('id', $clientIds)
            ->where('type', 'client')
            ->get();
    }

    private function notifyClientsCaptainArrived(User $captain, TripCar $tripCar, int $pointId, TrackTrip $trackTrip): void
    {
        $clients = $this->clientsAtPointOnTripCar($tripCar, $pointId);
        if ($clients->isEmpty()) {
            return;
        }

        $point = Point::query()->find($pointId);
        if ($point === null) {
            return;
        }

        $at = now()->format('Y-m-d H:i:s');
        $content = [
            'title_en' => __('api.track_trips.notification_title_arrival', [], 'en'),
            'title_ar' => __('api.track_trips.notification_title_arrival', [], 'ar'),
            'description_en' => trans('api.track_trips.message_arrival', [
                'captain' => $captain->name,
                'point' => $point->name_en ?: $point->name_ar,
                'at' => $at,
            ], 'en'),
            'description_ar' => trans('api.track_trips.message_arrival', [
                'captain' => $captain->name,
                'point' => $point->name_ar ?: $point->name_en,
                'at' => $at,
            ], 'ar'),
        ];

        foreach ($clients as $client) {
            $this->notificationService->deliverDirectToUser($client, $content, [
                'type' => 'captain_arrival',
                'track_trip_id' => (string) $trackTrip->id,
                'trip_id' => (string) $trackTrip->trip_id,
                'point_id' => (string) $pointId,
            ]);
        }
    }

    private function recordReservationLog(
        User $captain,
        Trip $trip,
        Reservation $reservation,
        ?string $rejectReason = null,
    ): TrackTrip {
        $reservation->loadMissing(['tripCar:id,trip_id,captain_id,car_id', 'user:id,name', 'point:id,name_en,name_ar']);

        $tripCar = TripCar::query()
            ->where('id', (int) $reservation->trip_car_id)
            ->where('trip_id', $trip->id)
            ->where('captain_id', $captain->id)
            ->firstOrFail();

        $message = null;
        if ($rejectReason !== null && $rejectReason !== '') {
            $message = json_encode([
                'log' => 'reject',
                'reason' => $rejectReason,
            ], JSON_THROW_ON_ERROR);
        }

        return TrackTrip::query()->create([
            'trip_id' => $trip->id,
            'captain_id' => $captain->id,
            'car_id' => $tripCar->car_id,
            'point_id' => $reservation->point_id,
            'client_id' => $reservation->user_id,
            'message' => $message,
        ]);
    }

    private function notifyClientRejected(
        User $captain,
        Reservation $reservation,
        string $reason,
        TrackTrip $trackTrip,
    ): void {
        $client = $reservation->user;
        $point = $reservation->point;

        if ($client === null) {
            return;
        }

        $at = now()->format('Y-m-d H:i:s');
        $params = [
            'captain' => $captain->name,
            'client' => $client->name,
            'reason' => $reason,
            'point' => $point?->name_en ?: $point?->name_ar,
            'at' => $at,
        ];
        $paramsAr = [
            'captain' => $captain->name,
            'client' => $client->name,
            'reason' => $reason,
            'point' => $point?->name_ar ?: $point?->name_en,
            'at' => $at,
        ];

        $this->notificationService->deliverDirectToUser($client, [
            'title_en' => __('api.track_trips.notification_title_rejection', [], 'en'),
            'title_ar' => __('api.track_trips.notification_title_rejection', [], 'ar'),
            'description_en' => trans('api.track_trips.notification_rejection', $params, 'en'),
            'description_ar' => trans('api.track_trips.notification_rejection', $paramsAr, 'ar'),
        ], [
            'type' => 'captain_rejection',
            'track_trip_id' => (string) $trackTrip->id,
            'trip_id' => (string) $trackTrip->trip_id,
            'reservation_id' => (string) $reservation->id,
        ]);
    }

    private function assertValidCaptain(User $captain, int $captainId): void
    {
        if ((int) $captain->id !== $captainId) {
            throw ValidationException::withMessages([
                'captain_id' => [__('api.track_trips.validation_captain_mismatch')],
            ]);
        }

        if ($captain->type !== 'captain') {
            throw ValidationException::withMessages([
                'captain_id' => [__('api.trips.invalid_captain')],
            ]);
        }
    }

    private function assertCarAssignedToCaptain(Trip $trip, User $captain, int $carId): TripCar
    {
        $tripCar = TripCar::query()
            ->where('trip_id', $trip->id)
            ->where('captain_id', $captain->id)
            ->where('car_id', $carId)
            ->first();

        if ($tripCar === null) {
            throw ValidationException::withMessages([
                'car_id' => [__('api.captain_trips.not_assigned')],
            ]);
        }

        return $tripCar;
    }

    private function assertValidPointForTrip(Trip $trip, ?int $pointId): void
    {
        if ($pointId === null || $pointId <= 0) {
            throw ValidationException::withMessages([
                'point_id' => [__('api.track_trips.validation_point_id_required')],
            ]);
        }

        $point = Point::query()->findOrFail($pointId);

        $trip->loadMissing(['time.point', 'routeTime']);
        $routeId = $trip->routeTime?->route_id ?? $trip->time?->point?->route_id;

        if ($routeId === null || (int) $point->route_id !== (int) $routeId) {
            throw ValidationException::withMessages([
                'point_id' => [__('api.track_trips.validation_point_not_on_trip_route')],
            ]);
        }
    }

    private function resolveTripCar(User $captain, Trip $trip): TripCar
    {
        $tripCar = TripCar::query()
            ->where('trip_id', $trip->id)
            ->where('captain_id', $captain->id)
            ->first();

        if ($tripCar === null) {
            throw ValidationException::withMessages([
                'trip' => [__('api.captain_trips.not_assigned')],
            ]);
        }

        return $tripCar;
    }

    private function assertTripInProgress(Trip $trip): void
    {
        if ($trip->status !== 'in_progress') {
            throw ValidationException::withMessages([
                'trip_id' => [__('api.tracking.trip_not_in_progress')],
            ]);
        }
    }
}
