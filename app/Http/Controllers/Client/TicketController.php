<?php

namespace App\Http\Controllers\Client;

use App\Helpers\PaginationHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\AddTicketMessageRequest;
use App\Http\Requests\Client\StoreTicketRequest;
use App\Http\Requests\Client\TicketIndexRequest;
use App\Http\Requests\Client\UpdateTicketRequest;
use App\Http\Resources\ClientTicketResource;
use App\Models\Ticket;
use App\Services\TicketService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TicketController extends Controller
{
    public function __construct(
        private TicketService $ticketService,
    ) {}

    public function index(TicketIndexRequest $request)
    {
        try {
            /** @var \App\Models\User $user */
            $user = $request->user();

            $perPage = (int) $request->input('per_page', 15);
            $paginator = $this->ticketService->paginateForClient($user, $perPage);

            return response()->json([
                'status' => 'success',
                'message' => __('api.tickets.client_list_retrieved'),
                'data' => ClientTicketResource::collection($paginator),
                'pagination' => PaginationHelper::paginate($paginator),
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.tickets.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(Request $request, Ticket $ticket)
    {
        try {
            /** @var \App\Models\User $user */
            $user = $request->user();

            $model = $this->ticketService->findForClient($user, (int) $ticket->id);

            return response()->json([
                'status' => 'success',
                'message' => __('api.tickets.client_retrieved'),
                'data' => new ClientTicketResource($model),
            ]);
        } catch (\Exception $e) {
            if ($e instanceof ModelNotFoundException) {
                throw $e;
            }
            return response()->json([
                'status' => 'error',
                'message' => __('api.tickets.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(StoreTicketRequest $request)
    {
        try {
            $data = $request->validated();

            /** @var \App\Models\User $user */
            $user = $request->user();

            if (isset($data['captain_id'])) {
                $data['captain_id'] = (int) $data['captain_id'];
            }
            if (isset($data['trip_id'])) {
                $data['trip_id'] = (int) $data['trip_id'];
            }

            $ticket = $this->ticketService->createForClient($user, [
                'title' => $data['title'],
                'description' => mb_substr(trim($data['description']), 0, 10000),
                'captain_id' => $data['captain_id'] ?? null,
                'trip_id' => $data['trip_id'] ?? null,
            ]);

            $ticket->loadCount([
                'messages as admin_replies_count' => static fn ($q) => $q->whereHas('user', static fn ($uq) => $uq->where('type', 'admin')),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => __('api.tickets.client_created'),
                'data' => new ClientTicketResource($ticket),
            ], 201);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.tickets.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // add message to ticket
    public function addMessage(AddTicketMessageRequest $request, Ticket $ticket)
    {
        try {
            /** @var \App\Models\User $user */
            $user = $request->user();
            if ($ticket->client_id !== $user->id) {
                throw ValidationException::withMessages([
                    'message' => [__('api.tickets.not_found')],
                ]);
            }
            $ticket = $this->ticketService->replyAsClient($ticket, $request->input('message'));
            return response()->json([
                'status' => 'success',
                'message' => __('api.tickets.client_reply_saved'),
                'data' => new ClientTicketResource($ticket),
            ]);
        } catch (\Exception $e) {
            if ($e instanceof ModelNotFoundException) {
                throw $e;
            }
            return response()->json([
                'status' => 'error',
                'message' => __('api.tickets.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(UpdateTicketRequest $request, Ticket $ticket)
    {
        try {
            $data = $request->validated();

            if (! $request->hasAny(['title', 'description', 'captain_id', 'trip_id'])) {
                throw ValidationException::withMessages([
                    'title' => [__('api.tickets.validation_at_least_one_field')],
                ]);
            }

            /** @var \App\Models\User $user */
            $user = $request->user();

            $model = $this->ticketService->findForClient($user, (int) $ticket->id);

            if (isset($data['description']) && is_string($data['description'])) {
                $data['description'] = mb_substr(trim($data['description']), 0, 10000);
            }

            $updated = $this->ticketService->updateForClient($user, $model, $data);

            return response()->json([
                'status' => 'success',
                'message' => __('api.tickets.client_updated'),
                'data' => new ClientTicketResource($updated),
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            if ($e instanceof ModelNotFoundException) {
                throw $e;
            }
            return response()->json([
                'status' => 'error',
                'message' => __('api.tickets.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Request $request, Ticket $ticket)
    {
        try {
            /** @var \App\Models\User $user */
            $user = $request->user();

            $model = $this->ticketService->findForClient($user, (int) $ticket->id);

            $this->ticketService->deleteForClient($user, $model);

            return response()->json([
                'status' => 'success',
                'message' => __('api.tickets.client_deleted'),
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            if ($e instanceof ModelNotFoundException) {
                throw $e;
            }
            return response()->json([
                'status' => 'error',
                'message' => __('api.tickets.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
