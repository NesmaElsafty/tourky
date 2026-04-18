<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\PaginationHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Http\Resources\UserWithFiredNotificationsResource;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class NotificationController extends Controller
{
    public function __construct(private NotificationService $notificationService) {}

    public function index(Request $request)
    {
        try {
            $request->validate([
                'user_type' => ['nullable', Rule::in(Notification::USER_TYPES)],
            ]);
            $notifications = $this->notificationService->getNotificationsPaginated(
                (int) ($request->per_page ?? 10),
                $request->input('user_type'),
            );
            $pagination = PaginationHelper::paginate($notifications);

            return response()->json([
                'status' => 'success',
                'message' => __('api.notifications.list_retrieved'),
                'data' => NotificationResource::collection($notifications),
                'pagination' => $pagination,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.notifications.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function indexAll(Request $request)
    {
        try {
            $notifications = $this->notificationService->getNotificationsPaginated(
                (int) ($request->per_page ?? 10),
                null,
            );
            $pagination = PaginationHelper::paginate($notifications);

            return response()->json([
                'status' => 'success',
                'message' => __('api.notifications.list_retrieved'),
                'data' => NotificationResource::collection($notifications),
                'pagination' => $pagination,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.notifications.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(Request $request, Notification $notification)
    {
        try {
            return response()->json([
                'status' => 'success',
                'message' => __('api.notifications.retrieved'),
                'data' => new NotificationResource($notification),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.notifications.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'title_en' => 'required|string|max:255',
                'title_ar' => 'required|string|max:255',
                'description_en' => 'nullable|string',
                'description_ar' => 'nullable|string',
                'user_type' => ['required', Rule::in(Notification::USER_TYPES)],
            ]);
            $notification = $this->notificationService->createNotification($data);

            return response()->json([
                'status' => 'success',
                'message' => __('api.notifications.created'),
                'data' => new NotificationResource($notification),
            ], 201);
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

    public function update(Request $request, Notification $notification)
    {
        try {
            $data = $request->validate([
                'title_en' => 'sometimes|required|string|max:255',
                'title_ar' => 'sometimes|required|string|max:255',
                'description_en' => 'nullable|string',
                'description_ar' => 'nullable|string',
                'user_type' => ['sometimes', 'required', Rule::in(Notification::USER_TYPES)],
            ]);
            $notification = $this->notificationService->updateNotification($notification, $data);

            return response()->json([
                'status' => 'success',
                'message' => __('api.notifications.updated'),
                'data' => new NotificationResource($notification),
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

    public function destroy(Notification $notification)
    {
        try {
            $this->notificationService->deleteNotification($notification);

            return response()->json([
                'status' => 'success',
                'message' => __('api.notifications.deleted'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.notifications.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function fireNotification(Request $request, Notification $notification)
    {
        try {
            $request->validate([
                'user_ids' => ['sometimes', 'array'],
                'user_ids.*' => ['integer', 'distinct', 'exists:users,id'],
            ], [
                'user_ids.array' => __('api.notifications.validation_user_ids_array'),
                'user_ids.*.integer' => __('api.notifications.validation_user_ids_integer'),
                'user_ids.*.exists' => __('api.notifications.validation_user_ids_exists'),
            ]);

            $onlyIds = $request->input('user_ids');
            $count = $this->notificationService->fireNotification(
                $notification,
                is_array($onlyIds) ? $onlyIds : null,
            );

            return response()->json([
                'status' => 'success',
                'message' => __('api.notifications.fired_success', ['count' => $count]),
                'data' => [
                    'recipient_count' => $count,
                    'notification' => new NotificationResource($notification),
                ],
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

    public function firedByUserType(Request $request)
    {
        try {
            $request->validate([
                'user_type' => ['required', Rule::in(Notification::USER_TYPES)],
                'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            ], [
                'user_type.required' => __('api.notifications.validation_user_type_required'),
                'user_type.in' => __('api.notifications.validation_user_type_invalid'),
            ]);

            $users = $this->notificationService->getUsersWithFiredNotificationsPaginated(
                $request->string('user_type')->toString(),
                (int) ($request->per_page ?? 10),
            );
            $pagination = PaginationHelper::paginate($users);

            return response()->json([
                'status' => 'success',
                'message' => __('api.notifications.fired_by_user_retrieved'),
                'data' => UserWithFiredNotificationsResource::collection($users),
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
}
