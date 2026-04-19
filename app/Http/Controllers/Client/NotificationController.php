<?php

namespace App\Http\Controllers\Client;

use App\Helpers\PaginationHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\FiredNotificationResource;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class NotificationController extends Controller
{
    public function __construct(private NotificationService $notificationService) {}

    public function index(Request $request)
    {
        try {
            $request->validate([
                'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            ], [
                'per_page.integer' => __('api.notifications.validation_per_page_integer'),
                'per_page.min' => __('api.notifications.validation_per_page_min'),
                'per_page.max' => __('api.notifications.validation_per_page_max'),
            ]);

            $user = $request->user();
            if ($user === null) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('api.auth.unauthorized'),
                ], 401);
            }

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
            $user = $request->user();
            if ($user === null) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('api.auth.unauthorized'),
                ], 401);
            }

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
            $user = $request->user();
            if ($user === null) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('api.auth.unauthorized'),
                ], 401);
            }

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
