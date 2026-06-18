<?php

namespace App\Services;

use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReservationExpiryService
{
    public function cancelExpiredPendingReservations(): int
    {
        $tz = (string) config('app.timezone', 'UTC');
        $now = now($tz);
        $today = $now->toDateString();
        $cancelled = 0;

        Reservation::query()
            ->where('status', 'pending')
            ->whereNotNull('date')
            ->where('date', '<=', $today)
            ->with('time:id,pickup_time')
            ->orderBy('id')
            ->chunkById(100, function ($reservations) use ($now, $tz, &$cancelled): void {
                foreach ($reservations as $reservation) {
                    if ($this->cancelIfExpired($reservation, $now, $tz)) {
                        $cancelled++;
                    }
                }
            });

        return $cancelled;
    }

    private function cancelIfExpired(Reservation $reservation, Carbon $now, string $tz): bool
    {
        if ($reservation->status !== 'pending') {
            return false;
        }

        $pickupAt = $this->resolvePickupAt($reservation, $tz);
        if ($pickupAt === null || $pickupAt->greaterThanOrEqualTo($now)) {
            return false;
        }

        return DB::transaction(function () use ($reservation): bool {
            $locked = Reservation::query()
                ->whereKey($reservation->id)
                ->where('status', 'pending')
                ->lockForUpdate()
                ->first();

            if ($locked === null) {
                return false;
            }

            $locked->update([
                'status' => 'cancelled',
                'trip_id' => null,
                'trip_car_id' => null,
            ]);

            return true;
        });
    }

    private function resolvePickupAt(Reservation $reservation, string $tz): ?Carbon
    {
        $date = trim((string) $reservation->date);
        if ($date === '') {
            return null;
        }

        $pickupTime = $reservation->time?->pickup_time;
        if ($pickupTime === null || trim((string) $pickupTime) === '') {
            return null;
        }

        try {
            return Carbon::parse($date.' '.$this->normalizePickupTime((string) $pickupTime), $tz);
        } catch (\Throwable) {
            return null;
        }
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
