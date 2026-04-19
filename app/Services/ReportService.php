<?php

namespace App\Services;

use App\Models\CaptainReport;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ReportService
{
    public function __construct(
        private ClientTripService $clientTripService,
    ) {}

    /**
     * Client submits a trip- or captain-related report (at most one per subject per reservation).
     *
     * @return Reservation|null Null if reservation is not this client's assigned trip
     */
    public function submitClientReport(User $client, Reservation $reservation, string $type, string $message): ?Reservation
    {
        if (! in_array($type, [CaptainReport::TYPE_TRIP, CaptainReport::TYPE_CAPTAIN], true)) {
            throw ValidationException::withMessages([
                'type' => [__('api.reports.validation_type_invalid')],
            ]);
        }

        if ($reservation->user_id !== $client->id) {
            return null;
        }

        if ($reservation->trip_id === null || $reservation->trip_car_id === null) {
            return null;
        }

        $reservation->loadMissing(['tripCar.captain', 'reports']);

        if (CaptainReport::query()->where('reservation_id', $reservation->id)->where('type', $type)->exists()) {
            throw ValidationException::withMessages([
                'type' => [__('api.reports.validation_already_submitted')],
            ]);
        }

        $captainId = null;
        if ($type === CaptainReport::TYPE_CAPTAIN) {
            if ($reservation->tripCar?->captain_id === null) {
                throw ValidationException::withMessages([
                    'type' => [__('api.reports.validation_no_captain')],
                ]);
            }
            $captainId = (int) $reservation->tripCar->captain_id;
        }

        CaptainReport::query()->create([
            'type' => $type,
            'reservation_id' => $reservation->id,
            'trip_id' => $reservation->trip_id,
            'captain_id' => $captainId,
            'message' => $message,
        ]);

        return $reservation->loadMissing($this->clientTripService->assignedTripEagerLoads());
    }

    /**
     * @return LengthAwarePaginator<int, CaptainReport>
     */
    public function paginateReportsForClient(User $client, int $perPage = 15): LengthAwarePaginator
    {
        return CaptainReport::query()
            ->whereHas('reservation', static fn (Builder $q) => $q->where('user_id', $client->id))
            ->with([
                'trip:id,date,status,time_id',
                'trip.time:id,pickup_time',
                'reservation:id,route_id,date',
                'reservation.route:id,name_en,name_ar',
                'captain:id,name',
                'repliedByUser:id,name',
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
            'type' => ['sometimes', 'in:trip,captain'],
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
