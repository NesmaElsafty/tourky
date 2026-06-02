<?php

namespace Database\Seeders;

use App\Models\Car;
use App\Models\Reservation;
use App\Models\Time;
use App\Models\Trip;
use App\Models\User;
use App\Services\TripService;
use App\Support\OperationalWeek;
use Database\Seeders\Concerns\ResolvesReservationDropOff;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Throwable;

class TripSeeder extends Seeder
{
    use ResolvesReservationDropOff;

    /**
     * Pending bookings per (date, time) needed so admin-style trip creation (2+ vehicles) can run.
     */
    private const CLIENT_DEMO_PENDING_COUNT = 16;

    public function run(): void
    {
        $tripService = app(TripService::class);

        $cars = $this->carsWithSeats();
        if ($cars->count() < 2) {
            Car::factory()->count(4)->create();
            $cars = $this->carsWithSeats();
        }

        $captainIds = User::query()
            ->where('type', 'captain')
            ->orderBy('id')
            ->pluck('id')
            ->values();

        if ($cars->count() < 2 || $captainIds->count() < 2) {
            return;
        }

        $clients = User::query()->where('type', 'client')->get();

        $times = Time::query()
            ->with('point')
            ->whereHas('point', function ($query): void {
                $query->whereHas('route', fn ($routeQuery) => $routeQuery->whereIn('type', ['b2c', 'b2b']));
            })
            ->get();
        if ($times->isEmpty() || $clients->isEmpty()) {
            return;
        }

        $this->seedClientPortalTripsForTodayAndHistory(
            $tripService,
            $cars,
            $captainIds,
            $clients,
            $times,
        );

        $this->seedTripsForCurrentOperationalWeek(
            $tripService,
            $cars,
            $captainIds,
            $clients,
            $times,
        );

        // Heavy groups: each > 14 pending reservations so TripService can form multi-car trips.
        $heavyGroupCount = 16;
        for ($groupIndex = 0; $groupIndex < $heavyGroupCount; $groupIndex++) {
            $time = $times->random();
            $date = now()->addDays($groupIndex + 3)->toDateString();
            $reservationsCount = fake()->numberBetween(16, 36);
            $routeTimeId = $this->resolveRouteTimeIdForPickup((int) $time->point->route_id, (int) $time->id);

            for ($i = 0; $i < $reservationsCount; $i++) {
                Reservation::query()->create($this->reservationSeedAttributes(
                    (int) $clients->random()->id,
                    (int) $time->point->route_id,
                    (int) $time->point_id,
                    (int) $time->id,
                    $routeTimeId,
                    $date,
                ));
            }
        }

        // Medium / small pending pools: stay below trip auto-formation threshold so admins see backlog queues.
        $smallPoolIterations = 40;
        for ($s = 0; $s < $smallPoolIterations; $s++) {
            $time = $times->random();
            $time->loadMissing('point');
            if ($time->point === null) {
                continue;
            }
            $routeTimeId = $this->resolveRouteTimeIdForPickup((int) $time->point->route_id, (int) $time->id);
            $date = now()->addDays(fake()->numberBetween(45, 200))->toDateString();
            $poolSize = fake()->numberBetween(4, 12);

            for ($i = 0; $i < $poolSize; $i++) {
                Reservation::query()->create($this->reservationSeedAttributes(
                    (int) $clients->random()->id,
                    (int) $time->point->route_id,
                    (int) $time->point_id,
                    (int) $time->id,
                    $routeTimeId,
                    $date,
                ));
            }
        }

        $groups = Reservation::query()
            ->select(['date', 'route_time_id'])
            ->where('status', 'pending')
            ->whereNull('trip_id')
            ->whereNotNull('date')
            ->whereNotNull('route_time_id')
            ->groupBy('date', 'route_time_id')
            ->havingRaw('COUNT(*) > 14')
            ->orderBy('date')
            ->orderBy('route_time_id')
            ->get();

        foreach ($groups as $group) {
            $date = (string) $group->date;
            $routeTimeId = (int) $group->route_time_id;
            $pendingCount = Reservation::query()
                ->where('status', 'pending')
                ->whereNull('trip_id')
                ->where('date', $date)
                ->where('route_time_id', $routeTimeId)
                ->count();
            if ($pendingCount <= 0) {
                continue;
            }

            $carsForGroup = $cars->shuffle()->values();
            $captainsForGroup = $captainIds->shuffle()->values();
            $carsData = [];
            $accumulatedSeats = 0;

            $maxCarsByReservations = max(2, $pendingCount);
            $maxCarsByResources = min($carsForGroup->count(), $captainsForGroup->count());
            $maxCars = min($maxCarsByReservations, $maxCarsByResources);

            for ($i = 0; $i < $maxCars; $i++) {
                $car = $carsForGroup->get($i);
                $captainId = $captainsForGroup->get($i);
                if (! $car || ! $captainId) {
                    break;
                }

                $seats = (int) $car->number_of_seats;
                if ($seats <= 0) {
                    continue;
                }

                $carsData[] = [
                    'captain_id' => (int) $captainId,
                    'car_id' => (int) $car->id,
                    'status' => 'planned',
                ];
                $accumulatedSeats += $seats;

                // Enforce minimum 2 cars per trip.
                if ($accumulatedSeats >= $pendingCount && count($carsData) >= 2) {
                    break;
                }
            }

            if (count($carsData) < 2 || $accumulatedSeats < $pendingCount) {
                continue;
            }

            try {
                $tripService->createTripForReservationGroup($date, $routeTimeId, $carsData);
            } catch (Throwable) {
                // Skip groups that fail validation (e.g. race with other seed steps).
            }
        }

        $this->backfillTripsMissingRouteTimeId();
        $this->backfillReservationsMissingDropOffTimeId();
    }

    /**
     * One assigned trip dated **today** and one **in the past** so the client API can show
     * `scope=today` and `scope=history` (confirmed reservations on a trip with vehicle).
     */
    private function seedClientPortalTripsForTodayAndHistory(
        TripService $tripService,
        Collection $cars,
        Collection $captainIds,
        Collection $clients,
        Collection $times,
    ): void {
        if ($clients->count() < self::CLIENT_DEMO_PENDING_COUNT) {
            return;
        }

        $carsBySeats = $cars->sortByDesc(fn (Car $car): int => (int) $car->number_of_seats)->values();
        $carA = $carsBySeats->get(0);
        $carB = $carsBySeats->get(1);
        if ($carA === null || $carB === null) {
            return;
        }

        $seatA = (int) $carA->number_of_seats;
        $seatB = (int) $carB->number_of_seats;
        if ($seatA + $seatB < self::CLIENT_DEMO_PENDING_COUNT) {
            return;
        }

        $captainA = (int) $captainIds->get(0);
        $captainB = (int) $captainIds->get(1);

        $carsData = [
            ['captain_id' => $captainA, 'car_id' => (int) $carA->id, 'status' => 'planned'],
            ['captain_id' => $captainB, 'car_id' => (int) $carB->id, 'status' => 'planned'],
        ];

        $timeA = $times->get(0);
        $timeB = $times->get(min(1, $times->count() - 1));
        if ($timeA === null || $timeB === null) {
            return;
        }

        $today = now()->toDateString();
        $historyDate = now()->subDays(10)->toDateString();

        foreach ([
            $today => $timeA,
            $historyDate => $timeB,
        ] as $date => $time) {
            try {
                $this->createPendingGroupForDateAndTime(
                    $date,
                    $time,
                    $clients,
                );
                $tripService->createTripForReservationGroup(
                    $date,
                    $this->resolveRouteTimeIdForPickup((int) $time->point->route_id, (int) $time->id),
                    $carsData
                );
            } catch (Throwable) {
                // Ignore if duplicates or validation fails so the rest of the seeder still runs.
            }
        }
    }

    /**
     * One assigned trip per Sun–Thu day in the current operational week (captain `scope=week`).
     * Past weekdays in that window are marked completed; today through Thursday stay planned.
     */
    private function seedTripsForCurrentOperationalWeek(
        TripService $tripService,
        Collection $cars,
        Collection $captainIds,
        Collection $clients,
        Collection $times,
    ): void {
        if ($clients->count() < self::CLIENT_DEMO_PENDING_COUNT || $times->count() < 3) {
            return;
        }

        $carsBySeats = $cars->sortByDesc(fn (Car $car): int => (int) $car->number_of_seats)->values();
        $carA = $carsBySeats->get(0);
        $carB = $carsBySeats->get(1);
        if ($carA === null || $carB === null) {
            return;
        }

        if ((int) $carA->number_of_seats + (int) $carB->number_of_seats < self::CLIENT_DEMO_PENDING_COUNT) {
            return;
        }

        $captainA = (int) $captainIds->get(0);
        $captainB = (int) $captainIds->get(1);

        $carsData = [
            ['captain_id' => $captainA, 'car_id' => (int) $carA->id, 'status' => 'planned'],
            ['captain_id' => $captainB, 'car_id' => (int) $carB->id, 'status' => 'planned'],
        ];

        $bounds = OperationalWeek::bounds();
        $today = now()->startOfDay();
        $timeIndex = 2;

        for ($date = $bounds['start']->copy(); $date->lte($bounds['end']); $date->addDay()) {
            $dateString = $date->toDateString();
            $time = $times->get($timeIndex % $times->count());
            $timeIndex++;

            if ($time === null) {
                continue;
            }

            $time->loadMissing('point');
            if ($time->point === null) {
                continue;
            }

            $routeTimeId = $this->resolveRouteTimeIdForPickup(
                (int) $time->point->route_id,
                (int) $time->id,
            );

            if (Trip::query()
                ->where('date', $dateString)
                ->where('route_time_id', $routeTimeId)
                ->exists()) {
                continue;
            }

            try {
                $this->createPendingGroupForDateAndTime($dateString, $time, $clients);
                $trip = $tripService->createTripForReservationGroup($dateString, $routeTimeId, $carsData);

                if ($date->lt($today)) {
                    $trip->update(['status' => 'completed']);
                }
            } catch (Throwable) {
                // Skip duplicates or validation failures.
            }
        }
    }

    private function createPendingGroupForDateAndTime(string $date, Time $time, Collection $clients): void
    {
        $time->loadMissing('point');
        if ($time->point === null) {
            return;
        }
        $routeTimeId = $this->resolveRouteTimeIdForPickup((int) $time->point->route_id, (int) $time->id);

        $picked = $clients->shuffle()->unique('id')->take(self::CLIENT_DEMO_PENDING_COUNT);
        if ($picked->count() < self::CLIENT_DEMO_PENDING_COUNT) {
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

    private function backfillTripsMissingRouteTimeId(): void
    {
        Trip::query()
            ->whereNull('route_time_id')
            ->with(['time.point'])
            ->orderBy('id')
            ->chunkById(100, function ($trips): void {
                foreach ($trips as $trip) {
                    if ($trip->time_id === null || $trip->time?->point === null) {
                        continue;
                    }

                    $routeTimeId = $this->resolveRouteTimeIdForPickup(
                        (int) $trip->time->point->route_id,
                        (int) $trip->time_id,
                    );

                    $trip->update(['route_time_id' => $routeTimeId]);
                }
            });
    }

    private function carsWithSeats()
    {
        return Car::query()
            ->whereNotNull('number_of_seats')
            ->get()
            ->filter(fn (Car $car): bool => (int) $car->number_of_seats > 0)
            ->values();
    }
}
