<?php

namespace App\Services;

use App\Models\CaptainReport;
use App\Models\Reservation;
use App\Models\Trip;
use App\Models\TripCar;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReportService
{
    /**
     * Client cancels their reservation and submits a cancellation reason.
     */
    public function submitClientCancellation(User $client, Reservation $reservation, string $message): Reservation
    {
        if ($reservation->user_id !== $client->id) {
            $this->throwReservationNotFound((int) $reservation->id);
        }

        if ($reservation->trip_id === null || $reservation->trip_car_id === null) {
            throw ValidationException::withMessages([
                'reservation' => [__('api.reservations.cannot_cancel')],
            ]);
        }

        if ($reservation->status === 'cancelled') {
            throw ValidationException::withMessages([
                'status' => [__('api.reservations.already_cancelled')],
            ]);
        }

        if (! in_array($reservation->status, ['pending', 'confirmed'], true)) {
            throw ValidationException::withMessages([
                'status' => [__('api.reservations.cannot_cancel')],
            ]);
        }

        if (CaptainReport::query()
            ->where('reservation_id', $reservation->id)
            ->where('type', CaptainReport::TYPE_CLIENT)
            ->exists()) {
            throw ValidationException::withMessages([
                'message' => [__('api.reports.validation_already_submitted')],
            ]);
        }

        $reservation->loadMissing('tripCar');

        return DB::transaction(function () use ($reservation, $message): Reservation {
            CaptainReport::query()->create([
                'type' => CaptainReport::TYPE_CLIENT,
                'reservation_id' => $reservation->id,
                'trip_id' => $reservation->trip_id,
                'captain_id' => $reservation->tripCar?->captain_id,
                'message' => $message,
            ]);

            $reservation->update(['status' => 'cancelled']);

            return $reservation->fresh(['route', 'point', 'time']) ?? $reservation;
        });
    }

    /**
     * Captain rejects a client on their assigned trip and submits a reason.
     */
    public function submitCaptainRejection(
        User $captain,
        Trip $trip,
        Reservation $reservation,
        string $message,
    ): CaptainReport {
        $this->assertCaptainCanActOnReservation($captain, $trip, $reservation);

        if ($reservation->status === 'cancelled') {
            throw ValidationException::withMessages([
                'reservation' => [__('api.reservations.already_cancelled')],
            ]);
        }

        if (! in_array($reservation->status, ['pending', 'confirmed'], true)) {
            throw ValidationException::withMessages([
                'reservation' => [__('api.captain_trips.cannot_reject_client')],
            ]);
        }

        if (CaptainReport::query()
            ->where('reservation_id', $reservation->id)
            ->where('type', CaptainReport::TYPE_CAPTAIN)
            ->exists()) {
            throw ValidationException::withMessages([
                'message' => [__('api.reports.validation_already_submitted')],
            ]);
        }

        return DB::transaction(function () use ($captain, $trip, $reservation, $message): CaptainReport {
            $report = CaptainReport::query()->create([
                'type' => CaptainReport::TYPE_CAPTAIN,
                'reservation_id' => $reservation->id,
                'trip_id' => $trip->id,
                'captain_id' => $captain->id,
                'message' => $message,
            ]);

            $reservation->update(['status' => 'cancelled']);

            return $report->load([
                'reservation.user:id,name,phone',
                'trip:id,date,status',
                'captain:id,name,phone',
            ]);
        });
    }

    private function assertCaptainCanActOnReservation(User $captain, Trip $trip, Reservation $reservation): void
    {
        if ($reservation->trip_id !== $trip->id) {
            throw ValidationException::withMessages([
                'reservation' => [__('api.captain_trips.reservation_not_on_trip')],
            ]);
        }

        $tripCar = TripCar::query()
            ->where('id', (int) $reservation->trip_car_id)
            ->where('trip_id', $trip->id)
            ->where('captain_id', $captain->id)
            ->first();

        if ($tripCar === null) {
            throw ValidationException::withMessages([
                'reservation' => [__('api.captain_trips.reservation_not_your_vehicle')],
            ]);
        }

        if (in_array($trip->status, ['completed', 'cancelled'], true)) {
            throw ValidationException::withMessages([
                'trip' => [__('api.captain_trips.cannot_reject_client')],
            ]);
        }
    }

    private function throwReservationNotFound(int $reservationId): never
    {
        $exception = new ModelNotFoundException();
        $exception->setModel(Reservation::class, [$reservationId]);

        throw $exception;
    }

    /**
     * @return LengthAwarePaginator<int, CaptainReport>
     */
    public function paginateReportsForClient(User $client, int $perPage = 15): LengthAwarePaginator
    {
        return CaptainReport::query()
            ->clientCancellation()
            ->whereHas('reservation', static fn (Builder $q) => $q->where('user_id', $client->id))
            ->with([
                'trip:id,date,status,time_id',
                'trip.time:id,pickup_time',
                'reservation:id,route_id,date',
                'reservation.route:id,name_en,name_ar',
            ])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    /**
     * @return LengthAwarePaginator<int, CaptainReport>
     */
    public function paginateReportsForAdmin(Request $request, int $perPage = 20): LengthAwarePaginator
    {
        $request->validate([
            'type' => ['sometimes', 'in:client,captain'],
            'replied' => ['sometimes', 'in:0,1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $perPage = (int) $request->input('per_page', $perPage);

        $query = CaptainReport::query()
            ->with([
                'reservation.user:id,name,phone',
                'trip:id,date,status',
                'captain:id,name,phone',
                'repliedByUser:id,name',
            ]);

        if ($request->filled('type')) {
            $query->where('type', $request->string('type')->toString());
        }

        if ($request->has('replied')) {
            $replied = $request->string('replied')->toString();
            if ($replied === '1') {
                $query->whereNotNull('replied_at');
            } elseif ($replied === '0') {
                $query->whereNull('replied_at');
            }
        }

        return $query
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function replyAsAdmin(User $admin, CaptainReport $report, string $message): CaptainReport
    {
        if ($report->type !== CaptainReport::TYPE_CAPTAIN) {
            throw ValidationException::withMessages([
                'admin_reply' => [__('api.reports.validation_cannot_reply_to_client_report')],
            ]);
        }

        if ($report->replied_at !== null) {
            throw ValidationException::withMessages([
                'admin_reply' => [__('api.reports.validation_already_replied')],
            ]);
        }

        $report->update([
            'admin_reply' => $message,
            'replied_at' => now(),
            'replied_by' => $admin->id,
        ]);

        return $report->loadMissing([
            'reservation.user:id,name,phone',
            'trip:id,date,status',
            'captain:id,name,phone',
            'repliedByUser:id,name',
        ]);
    }
}
