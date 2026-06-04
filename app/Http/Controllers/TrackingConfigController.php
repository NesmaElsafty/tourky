<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class TrackingConfigController extends Controller
{
    /**
     * Public Socket.io connection hints for captain/client apps.
     */
    public function __invoke(): JsonResponse
    {
        $socketUrl = rtrim((string) config('services.tracking.socket_url'), '/');
        if ($socketUrl === '') {
            $socketUrl = rtrim((string) config('app.url'), '/');
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'socket_url' => $socketUrl,
                'path' => '/socket.io',
                'transports' => ['websocket', 'polling'],
                'events' => [
                    'location' => 'loc',
                    'trip_join' => 'trip:join',
                    'trip_leave' => 'trip:leave',
                ],
            ],
        ]);
    }
}
