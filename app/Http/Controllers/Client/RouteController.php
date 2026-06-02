<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Resources\PointResource;
use App\Http\Resources\RouteResource;
use App\Http\Resources\RouteTimeResource;
use App\Models\Route;
use App\Models\RouteTime;
use App\Models\Time;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class RouteController extends Controller
{
    public function index(Request $request)
    {
        try {
            /** @var User|null $user */
            $user = $request->user('sanctum');
            // dd($user);
            if ($user === null) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('api.auth.unauthorized'),
                ], 401);
            }

            $routes = Route::query();
            $routes->where('is_active', true);

            if ($user->company_id !== null) {

                $routes->where(['type' => 'b2b', 'company_id' => $user->company_id]);
            } else {
                $routes->where('type', 'b2c');
            }
            $routes = $routes->get();

            return response()->json([
                'status' => 'success',
                'message' => __('api.routes.list_retrieved'),
                'data' => RouteResource::collection($routes),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.routes.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // get points for a route
    public function getPoints(Request $request, $routeId)
    {
        try {
            $route = Route::query()->findOrFail($routeId);
            $points = $route->points()->with('times')->get();

            return response()->json([
                'status' => 'success',
                'message' => __('api.routes.points_retrieved'),
                'data' => PointResource::collection($points),
            ]);
        } catch (\Exception $e) {
            if ($e instanceof ModelNotFoundException) {
                throw $e;
            }
            return response()->json([
                'status' => 'error',
                'message' => __('api.routes.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // get route times by route id
    public function getRouteTimes(Request $request, int $routeId)
    {
        try {
            Route::query()->findOrFail($routeId);
            $routeTimes = RouteTime::where('route_id', $routeId)->get();

            return response()->json([
                'status' => 'success',
                'message' => __('api.routes.times_retrieved'),
                'data' => RouteTimeResource::collection($routeTimes),
            ]);
        } catch (\Exception $e) {
            if ($e instanceof ModelNotFoundException) {
                throw $e;
            }
            return response()->json([
                'status' => 'error',
                'message' => __('api.routes.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // get route times by time id (drop-off options after the selected pickup time)
    public function getRouteTimesByTimeId(Request $request, int $timeId)
    {
        try {
            $pickupTime = Time::query()->find($timeId);
            if ($pickupTime === null) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('api.reservations.invalid_time'),
                ], 404);
            }

            $referencePickupTime = $this->normalizePickupTime((string) $pickupTime->pickup_time);

            $routeTimes = RouteTime::query()
                ->containingTime($timeId)
                ->get()
                ->map(function (RouteTime $routeTime) use ($referencePickupTime): RouteTime {
                    $routeTime->setAttribute(
                        'time_ids',
                        $this->filterTimeIdsWithPickupAfter($routeTime->time_ids ?? [], $referencePickupTime),
                    );

                    return $routeTime;
                })
                ->filter(static fn (RouteTime $routeTime): bool => $routeTime->time_ids !== [])
                ->values();

            return response()->json([
                'status' => 'success',
                'message' => __('api.routes.times_retrieved'),
                'data' => RouteTimeResource::collection($routeTimes),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.routes.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @param  list<int|string>  $timeIds
     * @return list<int>
     */
    private function filterTimeIdsWithPickupAfter(array $timeIds, string $referencePickupTime): array
    {
        $orderedIds = collect($timeIds)
            ->map(static fn ($id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->values();

        if ($orderedIds->isEmpty()) {
            return [];
        }

        $timesById = Time::query()
            ->whereIn('id', $orderedIds->all())
            ->get()
            ->keyBy('id');

        return $orderedIds
            ->filter(function (int $id) use ($timesById, $referencePickupTime): bool {
                $time = $timesById->get($id);
                if ($time === null) {
                    return false;
                }

                return $this->normalizePickupTime((string) $time->pickup_time) > $referencePickupTime;
            })
            ->values()
            ->all();
    }

    private function normalizePickupTime(string $pickupTime): string
    {
        $pickupTime = trim($pickupTime);
        if ($pickupTime === '') {
            return '00:00';
        }

        if (preg_match('/^(\d{1,2}):(\d{2})$/', $pickupTime, $matches)) {
            return sprintf('%02d:%02d', (int) $matches[1], (int) $matches[2]);
        }

        return $pickupTime;
    }
}
