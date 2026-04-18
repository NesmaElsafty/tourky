<?php

namespace App\Http\Controllers\Captain;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Services\NotificationService;

class NotificationController extends Controller
{
    public function __construct(private NotificationService $notificationService) {}

    public function index()
    {
        try {
            $notifications = $this->notificationService->getNotificationsForUserType('captain');

            return response()->json([
                'status' => 'success',
                'message' => __('api.notifications.list_retrieved'),
                'data' => NotificationResource::collection($notifications),
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
