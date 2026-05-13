<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\PaginationHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\AdminTicketResource;
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
            if ($model === null) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('api.tickets.not_found'),
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'message' => __('api.tickets.admin_retrieved'),
                'data' => new AdminTicketResource($model),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.tickets.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function reply(Request $request, Ticket $ticket)
    {
        try {
            $request->validate([
                'message' => ['required', 'string', 'min:1', 'max:5000'],
            ], [
                'message.required' => __('api.tickets.validation_message_required'),
                'message.max' => __('api.tickets.validation_message_max'),
            ]);

            $admin = $request->user();
            if ($admin === null) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('api.auth.unauthorized'),
                ], 401);
            }

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

    public function updateStatus(Request $request, Ticket $ticket)
    {
        try {
            $data = $request->validate([
                'status' => ['required', Rule::in(Ticket::STATUSES)],
            ], [
                'status.required' => __('api.tickets.validation_status_required'),
                'status.in' => __('api.tickets.validation_status_in'),
            ]);

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
