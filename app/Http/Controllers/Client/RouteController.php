<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Resources\RouteResource;
use App\Models\Route;

class RouteController extends Controller
{
    public function index()
    {
        try {
            $routes = Route::query()
                ->where('is_active', true)
                ->withCount('points')
                ->with([
                    'points' => fn ($query) => $query->withCount('times')->orderBy('id'),
                    'points.times' => fn ($query) => $query->where('is_active', true)->orderBy('pickup_time'),
                ])
                ->get();

            return response()->json([
                'status' => 'success',
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
}
