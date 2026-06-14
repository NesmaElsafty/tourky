<?php

namespace App\Services;

use App\Models\Point;
use App\Models\Reservation;
use App\Models\TrackTrip;
use App\Models\Trip;
use App\Models\TripCar;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TrackTripService
{
    /**
     * @param  array{
     *     trip_id: int,
     *     car_id: int,
     *     captain_id: int,
     *     message_type: 'arrival'|'acceptance',
     *     point_id?: int|null,
     *     client_id?: int|null
     * }  $data
     */
    public function storeTrackTripForCaptain(User $captain, array $data): TrackTrip
    {
        if ((int) $data['captain_id'] !== (int) $captain->id) {
            throw ValidationException::withMessages([
                'captain_id' => [__('api.track_trips.validation_captain_mismatch')],
            ]);
        }

        $trip = Trip::query()->findOrFail((int) $data['trip_id']);
        $this->assertTripInProgress($trip);

        $tripCar = TripCar::query()
            ->where('trip_id', $trip->id)
            ->where('captain_id', $captain->id)
            ->where('car_id', (int) $data['car_id'])
            ->first();

        if ($tripCar === null) {
            throw ValidationException::withMessages([
                'car_id' => [__('api.captain_trips.not_assigned')],
            ]);
        }

        $pointId = isset($data['point_id']) ? (int) $data['point_id'] : null;
        $clientId = isset($data['client_id']) ? (int) $data['client_id'] : null;


        $message = 'there is an action here';
        return TrackTrip::query()->create([
            'trip_id' => $trip->id,
            'captain_id' => $captain->id,
            'car_id' => (int) $data['car_id'],
            'point_id' => $pointId,
            'message' => $message,
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

    private function buildArrivalMessage(User $captain, ?int $pointId): string
    {
        $point = Point::query()->findOrFail((int) $pointId);
        $pointName = app()->getLocale() === 'ar'
            ? ($point->name_ar ?: $point->name_en)
            : ($point->name_en ?: $point->name_ar);

        return __('api.track_trips.message_arrival', [
            'captain' => $captain->name,
            'point' => $pointName ?? (string) $point->id,
            'at' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    private function buildAcceptanceMessage(User $captain, Trip $trip, ?int $clientId): string
    {
        $client = User::query()->findOrFail((int) $clientId);
        $trip->loadMissing('time:id,pickup_time');

        return __('api.track_trips.message_acceptance', [
            'captain' => $captain->name,
            'client' => $client->name,
            'trip_id' => $trip->id,
            'date' => $trip->date,
            'time' => $trip->time?->pickup_time ?? '',
        ]);
    }

    private function assertClientOnTripCar(Trip $trip, TripCar $tripCar, ?int $clientId): void
    {
        $exists = Reservation::query()
            ->where('trip_id', $trip->id)
            ->where('trip_car_id', $tripCar->id)
            ->where('user_id', (int) $clientId)
            ->exists();

        if (! $exists) {
            throw ValidationException::withMessages([
                'client_id' => [__('api.track_trips.validation_client_not_on_trip')],
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
                'trip' => [__('api.tracking.trip_not_in_progress')],
            ]);
        }
    }
}
