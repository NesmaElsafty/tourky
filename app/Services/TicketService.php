<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketMsg;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TicketService
{
    /**
     * @return LengthAwarePaginator<int, Ticket>
     */
    public function paginateForAdmin(Request $request): LengthAwarePaginator
    {
        $request->validate([
            'status' => ['sometimes', Rule::in(Ticket::STATUSES)],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $perPage = (int) $request->input('per_page', 20);

        $query = Ticket::query()
            ->with([
                'client:id,name,phone,email,type',
                'captain:id,name,phone,type',
                'trip:id,date,status,time_id',
                'trip.time:id,pickup_time',
            ])
            ->withCount([
                'messages as admin_replies_count' => static function (Builder $q): void {
                    $q->whereHas('user', static fn (Builder $uq) => $uq->where('type', 'admin'));
                },
            ])
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        return $query->paginate($perPage);
    }

    public function findForAdmin(int $id): ?Ticket
    {
        return Ticket::query()
            ->whereKey($id)
            ->with([
                'client:id,name,phone,email,type,language',
                'captain:id,name,phone,type,language,lat,long,status,has_trip,trip_id',
                'trip:id,date,status,time_id',
                'trip.time:id,pickup_time',
                'trip.tripCars.captain:id,name,phone',
                'trip.tripCars.car:id,type,plate_numbers,plate_letters',
                'messages.user:id,name,type',
            ])
            ->withCount([
                'messages as admin_replies_count' => static function (Builder $q): void {
                    $q->whereHas('user', static fn (Builder $uq) => $uq->where('type', 'admin'));
                },
            ])
            ->first();
    }

    public function replyAsAdmin(User $admin, Ticket $ticket, string $message): Ticket
    {
        if ($admin->type !== 'admin') {
            throw ValidationException::withMessages([
                'message' => [__('api.tickets.forbidden_not_admin')],
            ]);
        }

        $body = mb_substr(trim($message), 0, 5000);
        if ($body === '') {
            throw ValidationException::withMessages([
                'message' => [__('api.tickets.validation_message_required')],
            ]);
        }

        TicketMsg::query()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $admin->id,
            'message' => $body,
        ]);

        if ($ticket->status === Ticket::STATUS_PENDING) {
            $ticket->update(['status' => Ticket::STATUS_IN_PROGRESS]);
        }

        $fresh = $ticket->fresh([
            'client:id,name,phone,email,type,language',
            'captain:id,name,phone,type,language,lat,long,status,has_trip,trip_id',
            'trip:id,date,status,time_id',
            'trip.time:id,pickup_time',
            'trip.tripCars.captain:id,name,phone',
            'trip.tripCars.car:id,type,plate_numbers,plate_letters',
            'messages.user:id,name,type',
        ]);
        $fresh->loadCount([
            'messages as admin_replies_count' => static function (Builder $q): void {
                $q->whereHas('user', static fn (Builder $uq) => $uq->where('type', 'admin'));
            },
        ]);

        return $fresh;
    }

    public function updateStatusForAdmin(Ticket $ticket, string $status): Ticket
    {
        if (! in_array($status, Ticket::STATUSES, true)) {
            throw ValidationException::withMessages([
                'status' => [__('api.tickets.validation_status_in')],
            ]);
        }

        $ticket->update(['status' => $status]);

        $fresh = $ticket->fresh([
            'client:id,name,phone,email,type,language',
            'captain:id,name,phone,type,language,lat,long,status,has_trip,trip_id',
            'trip:id,date,status,time_id',
            'trip.time:id,pickup_time',
            'trip.tripCars.captain:id,name,phone',
            'trip.tripCars.car:id,type,plate_numbers,plate_letters',
            'messages.user:id,name,type',
        ]);
        $fresh->loadCount([
            'messages as admin_replies_count' => static function (Builder $q): void {
                $q->whereHas('user', static fn (Builder $uq) => $uq->where('type', 'admin'));
            },
        ]);

        return $fresh;
    }

    /**
     * @return LengthAwarePaginator<int, Ticket>
     */
    public function paginateForClient(User $client, int $perPage = 15): LengthAwarePaginator
    {
        return Ticket::query()
            ->where('client_id', $client->id)
            ->with([
                'captain:id,name,phone',
                'trip:id,date,status,time_id',
                'trip.time:id,pickup_time',
            ])
            ->withCount([
                'messages as admin_replies_count' => static function (Builder $q): void {
                    $q->whereHas('user', static fn (Builder $uq) => $uq->where('type', 'admin'));
                },
            ])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function findForClient(User $client, int $id): ?Ticket
    {
        return Ticket::query()
            ->where('client_id', $client->id)
            ->whereKey($id)
            ->with([
                'captain:id,name,phone',
                'trip:id,date,status,time_id',
                'trip.time:id,pickup_time',
                'messages.user:id,name,type',
            ])
            ->withCount([
                'messages as admin_replies_count' => static function (Builder $q): void {
                    $q->whereHas('user', static fn (Builder $uq) => $uq->where('type', 'admin'));
                },
            ])
            ->first();
    }

    /**
     * @param  array{title: string, description: string, captain_id?: int|null, trip_id?: int|null}  $data
     */
    public function createForClient(User $client, array $data): Ticket
    {
        return Ticket::query()->create([
            'title' => $data['title'],
            'description' => $data['description'],
            'status' => Ticket::STATUS_PENDING,
            'client_id' => $client->id,
            'captain_id' => $data['captain_id'] ?? null,
            'trip_id' => $data['trip_id'] ?? null,
        ])->load([
            'captain:id,name,phone',
            'trip:id,date,status,time_id',
            'trip.time:id,pickup_time',
        ]);
    }

    /**
     * @param  array{title?: string, description?: string, captain_id?: int|null, trip_id?: int|null}  $data
     */
    public function updateForClient(User $client, Ticket $ticket, array $data): Ticket
    {
        if ($ticket->client_id !== $client->id) {
            throw ValidationException::withMessages([
                'ticket' => [__('api.tickets.not_found')],
            ]);
        }

        if ($ticket->hasAdminReply()) {
            throw ValidationException::withMessages([
                'ticket' => [__('api.tickets.locked_after_admin_reply')],
            ]);
        }

        $payload = [];
        foreach (['title', 'description', 'captain_id', 'trip_id'] as $key) {
            if (array_key_exists($key, $data)) {
                $payload[$key] = $data[$key];
            }
        }

        if ($payload !== []) {
            $ticket->update($payload);
        }

        $fresh = $ticket->fresh([
            'captain:id,name,phone',
            'trip:id,date,status,time_id',
            'trip.time:id,pickup_time',
        ]);
        $fresh->loadCount([
            'messages as admin_replies_count' => static function (Builder $q): void {
                $q->whereHas('user', static fn (Builder $uq) => $uq->where('type', 'admin'));
            },
        ]);

        return $fresh;
    }

    public function deleteForClient(User $client, Ticket $ticket): void
    {
        if ($ticket->client_id !== $client->id) {
            throw ValidationException::withMessages([
                'ticket' => [__('api.tickets.not_found')],
            ]);
        }

        if ($ticket->hasAdminReply()) {
            throw ValidationException::withMessages([
                'ticket' => [__('api.tickets.locked_after_admin_reply')],
            ]);
        }

        $ticket->delete();
    }
}
