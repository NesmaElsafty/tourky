<?php

namespace App\Services;

use App\Models\Car;
use App\Models\Reservation;
use App\Models\Trip;
use App\Models\TripCar;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TripService
{
    /**
     * @return LengthAwarePaginator<int, Trip>
     */
    public function getTripsPaginated(int $perPage = 10): LengthAwarePaginator
    {
        return Trip::query()
            ->with([
                'tripCars.captain:id,name,phone',
                'tripCars.car',
                'time',
                'reservations.user:id,name,phone',
            ])
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function getTripById(int $id): Trip
    {
        return Trip::query()
            ->with([
                'tripCars.captain:id,name,phone',
                'tripCars.car',
                'time',
                'reservations.user:id,name,phone',
            ])
            ->findOrFail($id);
    }

    /**
     * @param  array<int, array{captain_id:int,car_id:int,status?:string}>  $carsData
     */
    public function createTripForReservationGroup(string $date, int $timeId, array $carsData): Trip
    {
        $pendingReservations = Reservation::query()
            ->where('status', 'pending')
            ->whereNull('trip_id')
            ->where('date', $date)
            ->where('time_id', $timeId)
            ->with('user:id,name,phone')
            ->orderBy('id')
            ->get();

        $pendingCount = $pendingReservations->count();
        if ($pendingCount === 0) {
            throw ValidationException::withMessages([
                'group' => [__('api.trips.no_pending_for_group')],
            ]);
        }

        if (count($carsData) > $pendingCount) {
            throw ValidationException::withMessages([
                'cars' => [__('api.trips.car_count_exceeds_reservations')],
            ]);
        }

        $carIds = array_values(array_unique(array_map(
            static fn (array $row): int => (int) $row['car_id'],
            $carsData
        )));

        /** @var Collection<int, Car> $carsById */
        $carsById = Car::query()->whereIn('id', $carIds)->get()->keyBy('id');

        $totalSeats = 0;
        foreach ($carsData as $index => $row) {
            $car = $carsById->get((int) $row['car_id']);
            if ($car === null) {
                throw ValidationException::withMessages([
                    "cars.$index.car_id" => [__('api.trips.invalid_car')],
                ]);
            }

            $seats = $this->seatsCount($car->number_of_seats);
            if ($seats <= 0) {
                throw ValidationException::withMessages([
                    "cars.$index.car_id" => [__('api.trips.invalid_car_seats')],
                ]);
            }

            $totalSeats += $seats;
        }

        if ($totalSeats < $pendingCount) {
            throw ValidationException::withMessages([
                'cars' => [__('api.trips.insufficient_total_seats', [
                    'seats' => $totalSeats,
                    'required' => $pendingCount,
                ])],
            ]);
        }

        $captainIds = array_values(array_unique(array_map(
            static fn (array $row): int => (int) $row['captain_id'],
            $carsData
        )));

        if (count($captainIds) !== count($carsData)) {
            throw ValidationException::withMessages([
                'cars' => [__('api.trips.validation_captain_distinct')],
            ]);
        }

        if (count($carIds) !== count($carsData)) {
            throw ValidationException::withMessages([
                'cars' => [__('api.trips.validation_car_distinct')],
            ]);
        }

        $captainCount = User::query()
            ->where('type', 'captain')
            ->whereIn('id', $captainIds)
            ->count();

        if ($captainCount !== count($captainIds)) {
            throw ValidationException::withMessages([
                'cars' => [__('api.trips.invalid_captain')],
            ]);
        }

        return DB::transaction(function () use ($carsData, $carsById, $pendingReservations, $date, $timeId): Trip {
            $trip = Trip::query()->create([
                'time_id' => $timeId,
                'date' => $date,
                'status' => (string) ($carsData[0]['status'] ?? 'planned'),
            ]);

            $offset = 0;
            $carsTotal = count($carsData);

            foreach ($carsData as $index => $row) {
                $car = $carsById->get((int) $row['car_id']);
                if ($car === null) {
                    continue;
                }

                $tripCar = TripCar::query()->create([
                    'trip_id' => $trip->id,
                    'car_id' => (int) $row['car_id'],
                    'captain_id' => (int) $row['captain_id'],
                ]);

                $remainingReservations = $pendingReservations->count() - $offset;
                $remainingCars = $carsTotal - $index;
                $minimumToKeepForNextCars = $remainingCars - 1;
                $seats = $this->seatsCount($car->number_of_seats);
                $assignCount = min($seats, $remainingReservations - $minimumToKeepForNextCars);

                if ($assignCount <= 0) {
                    throw ValidationException::withMessages([
                        'cars' => [__('api.trips.empty_vehicle_not_allowed')],
                    ]);
                }

                $assigned = $pendingReservations->slice($offset, $assignCount);
                if ($assigned->isEmpty()) {
                    throw ValidationException::withMessages([
                        'cars' => [__('api.trips.empty_vehicle_not_allowed')],
                    ]);
                }

                if ($assigned->isNotEmpty()) {
                    Reservation::query()
                        ->whereIn('id', $assigned->pluck('id')->all())
                        ->update([
                            'trip_id' => $trip->id,
                            'trip_car_id' => $tripCar->id,
                            'status' => 'confirmed',
                        ]);
                }

                $offset += $assigned->count();

                if ($offset >= $pendingReservations->count()) {
                    break;
                }
            }

            $tripId = (int) $trip->id;

            DB::afterCommit(function () use ($tripId): void {
                app(CaptainTripNotificationService::class)->notifyCaptainsTripCreated($tripId);
            });

            return Trip::query()
                ->whereKey($tripId)
                ->with([
                    'tripCars.captain:id,name,phone',
                    'tripCars.car',
                    'time',
                    'reservations.user:id,name,phone',
                ])
                ->firstOrFail();
        });
    }

    /**
     * @param  array{time_id?:int,date?:string,status?:string}  $data
     */
    public function updateTrip(Trip $trip, array $data): Trip
    {
        $trip->loadMissing('time');
        $this->ensureTripCanBeChanged($trip);

        $trip->update($data);

        return $trip->fresh([
            'tripCars.captain:id,name,phone',
            'tripCars.car',
            'time',
            'reservations.user:id,name,phone',
        ]) ?? $trip;
    }

    public function deleteTrip(Trip $trip): void
    {
        $trip->loadMissing('time');
        $this->ensureTripCanBeChanged($trip);

        DB::transaction(function () use ($trip): void {
            Reservation::query()
                ->where('trip_id', $trip->id)
                ->update([
                    'trip_id' => null,
                    'trip_car_id' => null,
                    'status' => 'pending',
                ]);

            $trip->delete();
        });
    }

    private function ensureTripCanBeChanged(Trip $trip): void
    {
        if (! $this->isAtLeast24HoursBeforeTrip($trip)) {
            throw ValidationException::withMessages([
                'trip' => [__('api.trips.locked_before_24h')],
            ]);
        }
    }

    private function isAtLeast24HoursBeforeTrip(Trip $trip): bool
    {
        if ($trip->time === null) {
            return false;
        }

        $pickupTime = trim((string) $trip->time->pickup_time);
        if ($pickupTime === '') {
            $pickupTime = '00:00';
        }

        if (preg_match('/^(\d{1,2}):(\d{2})$/', $pickupTime, $m)) {
            $pickupTime = sprintf('%02d:%02d', (int) $m[1], (int) $m[2]);
        }

        $tripStart = Carbon::parse($trip->date.' '.$pickupTime, config('app.timezone'));

        return now()->addDay()->lessThanOrEqualTo($tripStart);
    }

    private function seatsCount(string|int|null $value): int
    {
        if ($value === null) {
            return 0;
        }

        return (int) trim((string) $value);
    }
}
