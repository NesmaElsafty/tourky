<?php

namespace Database\Seeders;

use App\Models\Car;
use App\Models\Reservation;
use App\Models\Time;
use App\Models\User;
use App\Services\TripService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Throwable;

class TripSeeder extends Seeder
{
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

        if ($captainIds->count() < 2) {
            User::factory()->count(8)->create([
                'type' => 'captain',
                'role_id' => null,
            ]);
            $captainIds = User::query()
                ->where('type', 'captain')
                ->orderBy('id')
                ->pluck('id')
                ->values();
        }

        if ($cars->count() < 2 || $captainIds->count() < 2) {
            return;
        }

        $clients = User::query()->where('type', 'client')->get();
        if ($clients->count() < 80) {
            User::factory()->count(100 - $clients->count())->create([
                'type' => 'client',
                'role_id' => null,
            ]);
            $clients = User::query()->where('type', 'client')->get();
        }

        $times = Time::query()
            ->with('point')
            ->whereHas('point', function ($query): void {
                $query->whereHas('route', fn ($routeQuery) => $routeQuery->where('type', 'b2c'));
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

        // Create several heavy groups: each group > 14 reservations.
        for ($groupIndex = 0; $groupIndex < 6; $groupIndex++) {
            $time = $times->random();
            $date = now()->addDays($groupIndex + 4)->toDateString();
            $reservationsCount = fake()->numberBetween(15, 30);

            for ($i = 0; $i < $reservationsCount; $i++) {
                Reservation::query()->create([
                    'user_id' => $clients->random()->id,
                    'route_id' => $time->point->route_id,
                    'point_id' => $time->point_id,
                    'time_id' => $time->id,
                    'date' => $date,
                    'status' => 'pending',
                    'trip_id' => null,
                ]);
            }
        }

        $groups = Reservation::query()
            ->select(['date', 'time_id'])
            ->where('status', 'pending')
            ->whereNull('trip_id')
            ->whereNotNull('date')
            ->groupBy('date', 'time_id')
            ->havingRaw('COUNT(*) > 14')
            ->orderBy('date')
            ->orderBy('time_id')
            ->get();

        foreach ($groups as $group) {
            $date = (string) $group->date;
            $timeId = (int) $group->time_id;
            $pendingCount = Reservation::query()
                ->where('status', 'pending')
                ->whereNull('trip_id')
                ->where('date', $date)
                ->where('time_id', $timeId)
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
                $tripService->createTripForReservationGroup($date, $timeId, $carsData);
            } catch (Throwable) {
                // Skip groups that fail validation (e.g. race with other seed steps).
            }
        }
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
                $tripService->createTripForReservationGroup($date, (int) $time->id, $carsData);
            } catch (Throwable) {
                // Ignore if duplicates or validation fails so the rest of the seeder still runs.
            }
        }
    }

    private function createPendingGroupForDateAndTime(string $date, Time $time, Collection $clients): void
    {
        $time->loadMissing('point');
        if ($time->point === null) {
            return;
        }

        $picked = $clients->shuffle()->unique('id')->take(self::CLIENT_DEMO_PENDING_COUNT);
        if ($picked->count() < self::CLIENT_DEMO_PENDING_COUNT) {
            return;
        }

        foreach ($picked as $client) {
            Reservation::query()->create([
                'user_id' => $client->id,
                'route_id' => $time->point->route_id,
                'point_id' => $time->point_id,
                'time_id' => $time->id,
                'date' => $date,
                'status' => 'pending',
                'trip_id' => null,
            ]);
        }
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
