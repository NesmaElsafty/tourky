<?php

namespace App\Http\Controllers\Client;

use App\Helpers\PaginationHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\ClientTicketResource;
use App\Models\Ticket;
use App\Services\TicketService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TicketController extends Controller
{
    public function __construct(
        private TicketService $ticketService,
    ) {}

    public function index(Request $request)
    {
        try {
            $request->validate([
                'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            ]);

            $user = $request->user();
            if ($user === null) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('api.auth.unauthorized'),
                ], 401);
            }

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
            $user = $request->user();
            if ($user === null) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('api.auth.unauthorized'),
                ], 401);
            }

            $model = $this->ticketService->findForClient($user, (int) $ticket->id);
            if ($model === null) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('api.tickets.not_found'),
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'message' => __('api.tickets.client_retrieved'),
                'data' => new ClientTicketResource($model),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.tickets.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'title' => ['required', 'string', 'max:255'],
                'description' => ['required', 'string', 'min:1', 'max:10000'],
                'captain_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where('type', 'captain')],
                'trip_id' => ['nullable', 'integer', 'exists:trips,id'],
            ], [
                'title.required' => __('api.tickets.validation_title_required'),
                'description.required' => __('api.tickets.validation_description_required'),
            ]);

            $user = $request->user();
            if ($user === null) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('api.auth.unauthorized'),
                ], 401);
            }

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

    public function update(Request $request, Ticket $ticket)
    {
        try {
            $data = $request->validate([
                'title' => ['sometimes', 'string', 'max:255'],
                'description' => ['sometimes', 'string', 'min:1', 'max:10000'],
                'captain_id' => ['sometimes', 'nullable', 'integer', Rule::exists('users', 'id')->where('type', 'captain')],
                'trip_id' => ['sometimes', 'nullable', 'integer', 'exists:trips,id'],
            ]);

            if (! $request->hasAny(['title', 'description', 'captain_id', 'trip_id'])) {
                throw ValidationException::withMessages([
                    'title' => [__('api.tickets.validation_at_least_one_field')],
                ]);
            }

            $user = $request->user();
            if ($user === null) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('api.auth.unauthorized'),
                ], 401);
            }

            $model = $this->ticketService->findForClient($user, (int) $ticket->id);
            if ($model === null) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('api.tickets.not_found'),
                ], 404);
            }

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
            $user = $request->user();
            if ($user === null) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('api.auth.unauthorized'),
                ], 401);
            }

            $model = $this->ticketService->findForClient($user, (int) $ticket->id);
            if ($model === null) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('api.tickets.not_found'),
                ], 404);
            }

            $this->ticketService->deleteForClient($user, $model);

            return response()->json([
                'status' => 'success',
                'message' => __('api.tickets.client_deleted'),
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
}
