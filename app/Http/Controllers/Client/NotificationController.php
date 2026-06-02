<?php

namespace App\Http\Controllers\Client;

use App\Helpers\PaginationHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\NotificationIndexRequest;
use App\Http\Resources\FiredNotificationResource;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class NotificationController extends Controller
{
    public function __construct(private NotificationService $notificationService) {}

    public function index(NotificationIndexRequest $request)
    {
        try {
            /** @var \App\Models\User $user */
            $user = $request->user();

            $deliveries = $this->notificationService->getFiredNotificationDeliveriesForUserPaginated(
                $user,
                (int) ($request->per_page ?? 10),
            );
            $unreadCount = $this->notificationService->getUnreadDeliveriesCountForUser($user);
            $pagination = PaginationHelper::paginate($deliveries);

            return response()->json([
                'status' => 'success',
                'message' => __('api.notifications.inbox_retrieved'),
                'data' => FiredNotificationResource::collection($deliveries),
                'unread_count' => $unreadCount,
                'pagination' => $pagination,
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.notifications.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function markAsRead(Request $request, int $deliveryId)
    {
        try {
            /** @var \App\Models\User $user */
            $user = $request->user();

            $updated = $this->notificationService->markDeliveryAsReadForUser($user, $deliveryId);
            if (! $updated) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('api.notifications.delivery_not_found'),
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'message' => __('api.notifications.marked_as_read'),
                'data' => [
                    'unread_count' => $this->notificationService->getUnreadDeliveriesCountForUser($user),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.notifications.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function markAllAsRead(Request $request)
    {
        try {
            /** @var \App\Models\User $user */
            $user = $request->user();

            $markedCount = $this->notificationService->markAllDeliveriesAsReadForUser($user);

            return response()->json([
                'status' => 'success',
                'message' => __('api.notifications.marked_all_as_read'),
                'data' => [
                    'marked_count' => $markedCount,
                    'unread_count' => $this->notificationService->getUnreadDeliveriesCountForUser($user),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.notifications.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
