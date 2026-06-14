<?php

namespace Database\Seeders;

use App\Models\Car;
use App\Models\Reservation;
use App\Models\Time;
use App\Models\TrackTrip;
use App\Models\Trip;
use App\Models\TripCar;
use App\Models\User;
use App\Services\TripService;
use Carbon\Carbon;
use Database\Seeders\Concerns\ResolvesReservationDropOff;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Throwable;

class CompletedTripSeeder extends Seeder
{
    use ResolvesReservationDropOff;

    private const TRIP_COUNT = 6;

    private const PENDING_PER_TRIP = 16;

    private const TRACK_PINGS_PER_CAPTAIN = 12;

    /**
     * @var list<string>
     */
    private const TRACK_TEXT_MESSAGES = [
        'Trip started. Heading to the first pickup point.',
        'On the way to collect clients.',
        'First client picked up successfully.',
        'Stopped briefly due to traffic.',
        'Continuing the route as scheduled.',
        'Last client dropped off. Trip completed.',
        'بدأت الرحلة. في الطريق لأول نقطة التجميع.',
        'في الطريق لاستلام العملاء.',
        'تم استلام أول عميل بنجاح.',
        'توقف مؤقت بسبب الزحمة.',
        'مكملين المسار حسب الجدول.',
        'تم توصيل آخر عميل. الرحلة اكتملت.',
    ];

    public function run(): void
    {
        $tripService = app(TripService::class);

        $cars = $this->carsWithSeats();
        if ($cars->count() < 2) {
            $this->command?->warn('CompletedTripSeeder skipped: need at least 2 cars with seats.');

            return;
        }

        $captainIds = User::query()
            ->where('type', 'captain')
            ->orderBy('id')
            ->pluck('id')
            ->values();

        if ($captainIds->count() < 2) {
            $this->command?->warn('CompletedTripSeeder skipped: need at least 2 captains.');

            return;
        }

        $clients = User::query()->where('type', 'client')->get();
        $times = Time::query()
            ->with('point.route')
            ->whereHas('point', function ($query): void {
                $query->whereHas('route', fn ($routeQuery) => $routeQuery->whereIn('type', ['b2c', 'b2b']));
            })
            ->get();

        if ($clients->count() < self::PENDING_PER_TRIP || $times->count() < self::TRIP_COUNT) {
            $this->command?->warn('CompletedTripSeeder skipped: not enough clients or schedule times.');

            return;
        }

        $carsBySeats = $cars->sortByDesc(fn (Car $car): int => (int) $car->number_of_seats)->values();
        $carA = $carsBySeats->get(0);
        $carB = $carsBySeats->get(1);

        if ($carA === null || $carB === null) {
            return;
        }

        if ((int) $carA->number_of_seats + (int) $carB->number_of_seats < self::PENDING_PER_TRIP) {
            $this->command?->warn('CompletedTripSeeder skipped: not enough seat capacity for demo groups.');

            return;
        }

        $carsData = [
            ['captain_id' => (int) $captainIds->get(0), 'car_id' => (int) $carA->id, 'status' => 'planned'],
            ['captain_id' => (int) $captainIds->get(1), 'car_id' => (int) $carB->id, 'status' => 'planned'],
        ];

        $created = 0;
        $attempts = 0;
        $maxAttempts = self::TRIP_COUNT * 4;

        while ($created < self::TRIP_COUNT && $attempts < $maxAttempts) {
            $attempts++;
            $index = $created + $attempts - 1;
            $time = $times->get($index % $times->count());
            if ($time === null) {
                continue;
            }

            $time->loadMissing('point');
            if ($time->point === null) {
                continue;
            }

            $date = now()->subDays(45 + ($index * 13))->toDateString();
            $routeTimeId = $this->resolveRouteTimeIdForPickup(
                (int) $time->point->route_id,
                (int) $time->id,
            );

            if (Trip::query()
                ->where('date', $date)
                ->where('route_time_id', $routeTimeId)
                ->exists()) {
                continue;
            }

            try {
                $this->createPendingGroupForDateAndTime($date, $time, $clients);
                $trip = $tripService->createTripForReservationGroup($date, $routeTimeId, $carsData);
                $trip->update(['status' => 'completed']);

                $this->completeReservationsForTrip($trip);
                $this->seedTrackTripsForTrip($trip);

                $created++;
            } catch (Throwable) {
                // Skip duplicates or validation failures so other demo trips can still be created.
            }
        }

        $this->command?->info(sprintf('CompletedTripSeeder: %d completed trip(s) with tracking history.', $created));
    }

    private function createPendingGroupForDateAndTime(string $date, Time $time, Collection $clients): void
    {
        $time->loadMissing('point');
        if ($time->point === null) {
            return;
        }

        $routeTimeId = $this->resolveRouteTimeIdForPickup((int) $time->point->route_id, (int) $time->id);
        $picked = $clients->shuffle()->unique('id')->take(self::PENDING_PER_TRIP);

        if ($picked->count() < self::PENDING_PER_TRIP) {
            return;
        }

        foreach ($picked as $client) {
            Reservation::query()->create($this->reservationSeedAttributes(
                (int) $client->id,
                (int) $time->point->route_id,
                (int) $time->point_id,
                (int) $time->id,
                $routeTimeId,
                $date,
            ));
        }
    }

    private function completeReservationsForTrip(Trip $trip): void
    {
        $tripStart = $this->tripDateTime($trip);

        Reservation::query()
            ->where('trip_id', $trip->id)
            ->where('status', 'confirmed')
            ->orderBy('id')
            ->each(function (Reservation $reservation) use ($tripStart): void {
                $pickedUp = $tripStart->copy()->addMinutes(fake()->numberBetween(0, 20));
                $droppedOff = $pickedUp->copy()->addMinutes(fake()->numberBetween(35, 130));

                $reservation->update([
                    'picked_up_at' => $pickedUp,
                    'dropped_off_at' => $droppedOff,
                ]);
            });
    }

    private function seedTrackTripsForTrip(Trip $trip): void
    {
        $trip->load([
            'tripCars.captain:id,lat,long',
            'time.point.route:id,start_lat,start_long',
        ]);

        $route = $trip->time?->point?->route;
        $startLat = $route?->start_lat !== null ? (float) $route->start_lat : 30.0444;
        $startLong = $route?->start_long !== null ? (float) $route->start_long : 31.2357;
        $endLat = $startLat + fake()->randomFloat(4, 0.02, 0.07);
        $endLong = $startLong + fake()->randomFloat(4, 0.02, 0.07);

        $tripStart = $this->tripDateTime($trip);
        $pingIntervalMinutes = 3;

        foreach ($trip->tripCars as $tripCar) {
            $lastLat = $startLat;
            $lastLong = $startLong;

            $this->createTrackTripMessage(
                $trip,
                $tripCar,
                fake()->randomElement(array_slice(self::TRACK_TEXT_MESSAGES, 0, 6)),
                $tripStart->copy(),
            );

            for ($ping = 0; $ping < self::TRACK_PINGS_PER_CAPTAIN; $ping++) {
                $ratio = self::TRACK_PINGS_PER_CAPTAIN <= 1
                    ? 1.0
                    : $ping / (self::TRACK_PINGS_PER_CAPTAIN - 1);

                $lastLat = $startLat + (($endLat - $startLat) * $ratio) + fake()->randomFloat(6, -0.0015, 0.0015);
                $lastLong = $startLong + (($endLong - $startLong) * $ratio) + fake()->randomFloat(6, -0.0015, 0.0015);

                $trackedAt = $tripStart->copy()->addMinutes($ping * $pingIntervalMinutes);

                TrackTrip::query()->create([
                    'trip_id' => $trip->id,
                    'captain_id' => (int) $tripCar->captain_id,
                    'car_id' => (int) $tripCar->car_id,
                    'message' => json_encode([
                        'lat' => round($lastLat, 6),
                        'long' => round($lastLong, 6),
                    ], JSON_THROW_ON_ERROR),
                    'created_at' => $trackedAt,
                    'updated_at' => $trackedAt,
                ]);

                if (in_array($ping, [3, 7], true)) {
                    $this->createTrackTripMessage(
                        $trip,
                        $tripCar,
                        fake()->randomElement(array_slice(self::TRACK_TEXT_MESSAGES, 1, 10)),
                        $trackedAt->copy()->addMinute(),
                    );
                }
            }

            $this->createTrackTripMessage(
                $trip,
                $tripCar,
                fake()->randomElement(array_slice(self::TRACK_TEXT_MESSAGES, 5, 7)),
                $tripStart->copy()->addMinutes(self::TRACK_PINGS_PER_CAPTAIN * $pingIntervalMinutes + 2),
            );

            if ($tripCar->captain !== null) {
                $tripCar->captain->update([
                    'lat' => round($lastLat, 6),
                    'long' => round($lastLong, 6),
                ]);
            }
        }
    }

    private function createTrackTripMessage(Trip $trip, TripCar $tripCar, string $message, Carbon $trackedAt): void
    {
        TrackTrip::query()->create([
            'trip_id' => $trip->id,
            'captain_id' => (int) $tripCar->captain_id,
            'car_id' => (int) $tripCar->car_id,
            'message' => $message,
            'created_at' => $trackedAt,
            'updated_at' => $trackedAt,
        ]);
    }

    private function tripDateTime(Trip $trip): Carbon
    {
        $trip->loadMissing('time:id,pickup_time');

        $date = (string) $trip->date;
        $pickupTime = (string) ($trip->time?->pickup_time ?? '08:00');

        if (preg_match('/^\d{1,2}:\d{2}$/', $pickupTime) !== 1) {
            $pickupTime = '08:00';
        }

        return Carbon::parse($date.' '.$pickupTime);
    }

    /**
     * @return Collection<int, Car>
     */
    private function carsWithSeats(): Collection
    {
        return Car::query()
            ->whereNotNull('number_of_seats')
            ->get()
            ->filter(fn (Car $car): bool => (int) $car->number_of_seats > 0)
            ->values();
    }
}
