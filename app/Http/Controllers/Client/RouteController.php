<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Resources\PointResource;
use App\Http\Resources\RouteResource;
use App\Models\Route;
use App\Models\User;
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
            
            if($user->company_id !== null) {

                $routes->where(['type'=> 'b2b', 'company_id'=> $user->company_id]);
            }else{
                $routes->where('type', 'b2c');
            }
            $routes = $routes->get();

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

    // get points for a route
    public function getPoints(Request $request, $routeId)
    {
        try {
            $route = Route::find($routeId);
            if($route === null) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('api.routes.not_found'),
                ], 404);
            }
            $points = $route->points()->with('times')->get();
            return response()->json([
                'status' => 'success',
                'data' => PointResource::collection($points),
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
