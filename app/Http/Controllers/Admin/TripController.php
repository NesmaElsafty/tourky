<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\PaginationHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\TripResource;
use App\Models\Trip;
use App\Services\TripService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TripController extends Controller
{
    public function __construct(private TripService $tripService) {}

    public function index(Request $request)
    {
        try {
            $request->validate([
                'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            ], [
                'per_page.integer' => __('api.trips.validation_per_page_integer'),
                'per_page.min' => __('api.trips.validation_per_page_min'),
                'per_page.max' => __('api.trips.validation_per_page_max'),
            ]);

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
            return response()->json([
                'status' => 'error',
                'message' => __('api.trips.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(Trip $trip)
    {
        try {
            $trip->load([
                'tripCars.captain:id,name,phone',
                'tripCars.car',
                'time',
                'reservations.user:id,name,phone',
            ]);

            return response()->json([
                'status' => 'success',
                'message' => __('api.trips.retrieved'),
                'data' => new TripResource($trip),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.trips.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'date' => ['required', 'date'],
                'time_id' => ['required', 'integer', 'exists:times,id'],
                'cars' => ['required', 'array', 'min:1'],
                'cars.*.captain_id' => ['required', 'integer', 'distinct', 'exists:users,id'],
                'cars.*.car_id' => ['required', 'integer', 'distinct', 'exists:cars,id'],
                'cars.*.status' => ['sometimes', 'string', Rule::in(['planned', 'in_progress', 'completed', 'cancelled'])],
            ],
                [
                    'date.required' => __('api.trips.validation_date_required'),
                    'date.date' => __('api.trips.validation_date_date'),
                    'time_id.required' => __('api.trips.validation_time_id_required'),
                    'time_id.exists' => __('api.trips.validation_time_id_exists'),
                    'cars.required' => __('api.trips.validation_cars_required'),
                    'cars.array' => __('api.trips.validation_cars_array'),
                    'cars.min' => __('api.trips.validation_cars_min'),
                    'cars.*.captain_id.distinct' => __('api.trips.validation_captain_distinct'),
                    'cars.*.car_id.distinct' => __('api.trips.validation_car_distinct'),
                ]);

            $trip = $this->tripService->createTripForReservationGroup(
                (string) $data['date'],
                (int) $data['time_id'],
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
            return response()->json([
                'status' => 'error',
                'message' => __('api.trips.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, Trip $trip)
    {
        try {
            $data = $request->validate([
                'time_id' => ['sometimes', 'required', 'integer', 'exists:times,id'],
                'date' => ['sometimes', 'required', 'date'],
                'status' => ['sometimes', 'required', 'string', Rule::in(['planned', 'in_progress', 'completed', 'cancelled'])],
            ]);

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

    public function destroy(Trip $trip)
    {
        try {
            $this->tripService->deleteTrip($trip);

            return response()->json([
                'status' => 'success',
                'message' => __('api.trips.deleted'),
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
}
