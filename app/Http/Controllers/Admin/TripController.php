<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\PaginationHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTripRequest;
use App\Http\Requests\Admin\TripIndexRequest;
use App\Http\Requests\Admin\UpdateTripRequest;
use App\Http\Resources\TripResource;
use App\Models\Trip;
use App\Services\TripService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TripController extends Controller
{
    public function __construct(private TripService $tripService) {}

    public function index(TripIndexRequest $request)
    {
        try {
            $trips = $this->tripService->getTripsPaginated((int) ($request->per_page ?? 10));
            $pagination = PaginationHelper::paginate($trips);

            return response()->json([
                'status' => 'success',
                'message' => __('api.trips.list_retrieved'),
                'data' => TripResource::collection($trips),
                'pagination' => $pagination,
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            if ($e instanceof ModelNotFoundException) {
                throw $e;
            }
            return response()->json([
                'status' => 'error',
                'message' => __('api.trips.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $trip = Trip::query()->findOrFail($id);
            $trip->load([
                'tripCars.captain:id,name,phone,lat,long,status,has_trip,trip_id',
                'tripCars.car',
                'time',
                'routeTime:id,route_id,time_ids',
                'reservations.user:id,name,phone',
            ]);

            return response()->json([
                'status' => 'success',
                'message' => __('api.trips.retrieved'),
                'data' => new TripResource($trip),
            ]);
        } catch (\Exception $e) {
            if ($e instanceof ModelNotFoundException) {
                throw $e;
            }
            return response()->json([
                'status' => 'error',
                'message' => __('api.trips.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(StoreTripRequest $request)
    {
        try {
            $data = $request->validated();

            $trip = $this->tripService->createTripForReservationGroup(
                (string) $data['date'],
                (int) $data['route_time_id'],
                $data['cars'],
            );

            return response()->json([
                'status' => 'success',
                'message' => __('api.trips.created'),
                'data' => new TripResource($trip),
            ], 201);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            if ($e instanceof ModelNotFoundException) {
                throw $e;
            }
            return response()->json([
                'status' => 'error',
                'message' => __('api.trips.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(UpdateTripRequest $request, $id)
    {
        try {
            $data = $request->validated();

            $trip = Trip::query()->findOrFail($id);
            $updated = $this->tripService->updateTrip($trip, $data);

            return response()->json([
                'status' => 'success',
                'message' => __('api.trips.updated'),
                'data' => new TripResource($updated),
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.trips.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $trip = Trip::query()->findOrFail($id);
            $trip->delete();
            return response()->json([
                'status' => 'success',
                'message' => __('api.trips.deleted'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.trips.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }        
    }
}
