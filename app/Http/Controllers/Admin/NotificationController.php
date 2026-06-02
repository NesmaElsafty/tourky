<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\PaginationHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FireNotificationRequest;
use App\Http\Requests\Admin\FiredNotificationsByUserTypeRequest;
use App\Http\Requests\Admin\NotificationIndexRequest;
use App\Http\Requests\Admin\StoreNotificationRequest;
use App\Http\Requests\Admin\UpdateNotificationRequest;
use App\Http\Resources\NotificationResource;
use App\Http\Resources\UserWithFiredNotificationsResource;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class NotificationController extends Controller
{
    public function __construct(private NotificationService $notificationService) {}

    public function index(NotificationIndexRequest $request)
    {
        try {
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
            if ($e instanceof ModelNotFoundException) {
                throw $e;
            }
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
            if ($e instanceof ModelNotFoundException) {
                throw $e;
            }
            return response()->json([
                'status' => 'error',
                'message' => __('api.notifications.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $notification = Notification::query()->findOrFail($id);
            return response()->json([
                'status' => 'success',
                'message' => __('api.notifications.retrieved'),
                'data' => new NotificationResource($notification),
            ]);
        } catch (\Exception $e) {
            if ($e instanceof ModelNotFoundException) {
                throw $e;
            }
            return response()->json([
                'status' => 'error',
                'message' => __('api.notifications.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(StoreNotificationRequest $request)
    {
        try {
            $data = $request->validated();
            $notification = $this->notificationService->createNotification($data);

            return response()->json([
                'status' => 'success',
                'message' => __('api.notifications.created'),
                'data' => new NotificationResource($notification),
            ], 201);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            if ($e instanceof ModelNotFoundException) {
                throw $e;
            }
            return response()->json([
                'status' => 'error',
                'message' => __('api.notifications.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(UpdateNotificationRequest $request, $id)
    {
        try {
            $data = $request->validated();
            $notification = Notification::query()->findOrFail($id);
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

    public function destroy($id)
    {
        try {
            $notification = Notification::query()->findOrFail($id);
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

    public function fireNotification(FireNotificationRequest $request, $id)
    {
        try {
            $onlyIds = $request->input('user_ids');
            $notification = Notification::query()->findOrFail($id);
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

    public function firedByUserType(FiredNotificationsByUserTypeRequest $request)
    {
        try {
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
