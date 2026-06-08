<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\Trip;
use App\Models\TripCar;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CaptainLateStartPenaltyService
{
    public const PENALTY_GRACE_MINUTES = 10;

    public const CANCEL_GRACE_MINUTES = 15;

    public const PENALTY_AMOUNT = 50.0;

    /**
     * @return array{penalties_applied: int, trips_cancelled: int}
     */
    public function processOverduePlannedTrips(): array
    {
        return [
            'penalties_applied' => $this->applyDuePenalties(),
            'trips_cancelled' => $this->cancelOverdueUnstartedTrips(),
        ];
    }

    public function applyDuePenalties(): int
    {
        $tz = (string) config('app.timezone', 'UTC');
        $now = now($tz);
        $today = $now->toDateString();
        $applied = 0;

        TripCar::query()
            ->whereNull('late_start_penalty_applied_at')
            ->whereHas('trip', static function ($query) use ($today): void {
                $query->where('status', 'planned')
                    ->where('date', '<=', $today);
            })
            ->with([
                'trip:id,date,status,time_id',
                'trip.time:id,pickup_time',
                'captain:id,type,balance',
            ])
            ->orderBy('id')
            ->chunkById(100, function ($tripCars) use ($now, $tz, &$applied): void {
                foreach ($tripCars as $tripCar) {
                    if ($this->applyPenaltyIfDue($tripCar, $now, $tz)) {
                        $applied++;
                    }
                }
            });

        return $applied;
    }

    public function cancelOverdueUnstartedTrips(): int
    {
        $tz = (string) config('app.timezone', 'UTC');
        $now = now($tz);
        $today = $now->toDateString();
        $cancelled = 0;

        Trip::query()
            ->where('status', 'planned')
            ->where('date', '<=', $today)
            ->with([
                'time:id,pickup_time',
                'tripCars.captain:id,type,balance',
            ])
            ->orderBy('id')
            ->chunkById(100, function ($trips) use ($now, $tz, &$cancelled): void {
                foreach ($trips as $trip) {
                    if ($this->cancelTripIfOverdue($trip, $now, $tz)) {
                        $cancelled++;
                    }
                }
            });

        return $cancelled;
    }

    private function applyPenaltyIfDue(TripCar $tripCar, Carbon $now, string $tz): bool
    {
        $trip = $tripCar->trip;
        if ($trip === null || $trip->status !== 'planned') {
            return false;
        }

        $pickupTime = $trip->time?->pickup_time;
        if ($pickupTime === null || trim((string) $pickupTime) === '') {
            return false;
        }

        $deadline = $this->resolvePickupDeadline(
            (string) $trip->date,
            (string) $pickupTime,
            $tz,
            self::PENALTY_GRACE_MINUTES,
        );

        if ($deadline === null || $now->lessThan($deadline)) {
            return false;
        }

        return $this->deductPenaltyForTripCar($tripCar);
    }

    private function cancelTripIfOverdue(Trip $trip, Carbon $now, string $tz): bool
    {
        if ($trip->status !== 'planned') {
            return false;
        }

        $pickupTime = $trip->time?->pickup_time;
        if ($pickupTime === null || trim((string) $pickupTime) === '') {
            return false;
        }

        $deadline = $this->resolvePickupDeadline(
            (string) $trip->date,
            (string) $pickupTime,
            $tz,
            self::CANCEL_GRACE_MINUTES,
        );

        if ($deadline === null || $now->lessThan($deadline)) {
            return false;
        }

        return DB::transaction(function () use ($trip): bool {
            $lockedTrip = Trip::query()
                ->whereKey($trip->id)
                ->where('status', 'planned')
                ->lockForUpdate()
                ->first();

            if ($lockedTrip === null) {
                return false;
            }

            $tripCars = TripCar::query()
                ->where('trip_id', $lockedTrip->id)
                ->with('captain:id,type,balance')
                ->get();

            foreach ($tripCars as $tripCar) {
                $this->deductPenaltyForTripCar($tripCar);
            }

            $lockedTrip->update(['status' => 'cancelled']);

            app(TripService::class)->applyTripCancellationEffects($lockedTrip);

            return true;
        });
    }

    private function deductPenaltyForTripCar(TripCar $tripCar): bool
    {
        $captain = $tripCar->captain;
        if ($captain === null || $captain->type !== 'captain') {
            return false;
        }

        return DB::transaction(function () use ($tripCar, $captain): bool {
            $lockedTripCar = TripCar::query()
                ->whereKey($tripCar->id)
                ->whereNull('late_start_penalty_applied_at')
                ->lockForUpdate()
                ->first();

            if ($lockedTripCar === null) {
                return false;
            }

            $lockedTrip = $lockedTripCar->trip()->lockForUpdate()->first();
            if ($lockedTrip === null || $lockedTrip->status !== 'planned') {
                return false;
            }

            $lockedCaptain = User::query()
                ->whereKey($captain->id)
                ->where('type', 'captain')
                ->lockForUpdate()
                ->first();

            if ($lockedCaptain === null) {
                return false;
            }

            $lockedCaptain->balance = round((float) $lockedCaptain->balance - self::PENALTY_AMOUNT, 2);
            $lockedCaptain->save();

            $lockedTripCar->update(['late_start_penalty_applied_at' => now()]);

            return true;
        });
    }

    /**
     * First-point pickup (trip.time_id) plus the grace window.
     */
    private function resolvePickupDeadline(string $date, string $pickupTime, string $tz, int $graceMinutes): ?Carbon
    {
        $pickupTime = $this->normalizePickupTime($pickupTime);

        try {
            $pickupAt = Carbon::parse($date.' '.$pickupTime, $tz);
        } catch (\Throwable) {
            return null;
        }

        return $pickupAt->copy()->addMinutes($graceMinutes);
    }

    private function normalizePickupTime(string $value): string
    {
        $pickupTime = trim($value);
        if ($pickupTime === '') {
            return '00:00';
        }

        if (preg_match('/^(\d{1,2}):(\d{2})$/', $pickupTime, $matches)) {
            return sprintf('%02d:%02d', (int) $matches[1], (int) $matches[2]);
        }

        return $pickupTime;
    }
}
