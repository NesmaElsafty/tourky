<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\PaginationHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TicketReplyRequest;
use App\Http\Requests\Admin\TicketStatusUpdateRequest;
use App\Http\Resources\AdminTicketResource;
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

    public function index(Request $request)
    {
        try {
            $paginator = $this->ticketService->paginateForAdmin($request);

            return response()->json([
                'status' => 'success',
                'message' => __('api.tickets.admin_list_retrieved'),
                'data' => AdminTicketResource::collection($paginator),
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
            $model = $this->ticketService->findForAdmin((int) $ticket->id);

            return response()->json([
                'status' => 'success',
                'message' => __('api.tickets.admin_retrieved'),
                'data' => new AdminTicketResource($model),
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

    public function reply(TicketReplyRequest $request, Ticket $ticket)
    {
        try {
            /** @var \App\Models\User $admin */
            $admin = $request->user();

            $messageRaw = $request->input('message');
            $message = is_string($messageRaw) ? trim($messageRaw) : '';
            if ($message === '') {
                throw ValidationException::withMessages([
                    'message' => [__('api.tickets.validation_message_required')],
                ]);
            }

            $updated = $this->ticketService->replyAsAdmin($admin, $ticket, mb_substr($message, 0, 5000));

            return response()->json([
                'status' => 'success',
                'message' => __('api.tickets.admin_reply_saved'),
                'data' => new AdminTicketResource($updated),
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

    public function updateStatus(TicketStatusUpdateRequest $request, Ticket $ticket)
    {
        try {
            $data = $request->validated();

            $updated = $this->ticketService->updateStatusForAdmin($ticket, $data['status']);

            return response()->json([
                'status' => 'success',
                'message' => __('api.tickets.admin_status_updated'),
                'data' => new AdminTicketResource($updated),
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
