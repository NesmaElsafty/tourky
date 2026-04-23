<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Resources\RouteResource;
use App\Models\Route;
use Illuminate\Http\Request;

class RouteController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = $request->user();

            $routes = Route::query()
                ->where('is_active', true)
                ->where('type', 'b2c')
                ->get();

            if($user !== null && $user->type === 'client' && $user->company_id !== null)
            $routes = Route::query()
                ->where(['is_active'=> true, 'type'=> 'b2b', 'company_id'=> $user->company_id])
                ->withCount('points')
                ->with([
                    'company',
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
