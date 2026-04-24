<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\PaginationHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\CarResource;
use App\Models\Car;
use App\Services\CarService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CarController extends Controller
{
    public function __construct(private CarService $carService) {}

    public function index(Request $request)
    {
        try {
            $cars = $this->carService->getCarsPaginated((int) ($request->per_page ?? 10));
            $pagination = PaginationHelper::paginate($cars);

            return response()->json([
                'status' => 'success',
                'message' => __('api.cars.list_retrieved'),
                'data' => CarResource::collection($cars),
                'pagination' => $pagination,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.cars.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(Request $request, Car $car)
    {
        try {
            return response()->json([
                'status' => 'success',
                'message' => __('api.cars.retrieved'),
                'data' => new CarResource($car),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.cars.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'name' => 'required|string|max:255',
                'number_of_seats' => 'required|string|max:255',
                'type' => 'required|in:sedan,microbus',
                'plate_numbers' => 'required|string|max:255',
                'plate_letters' => 'required|string|max:255',
                'color' => 'required|string|max:255',
                'status' => 'required|in:active,inactive,maintenance,in_use',
            ]);
            $car = $this->carService->createCar($data);

            return response()->json([
                'status' => 'success',
                'message' => __('api.cars.created'),
                'data' => new CarResource($car),
            ], 201);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.cars.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, Car $car)
    {
        try {
            $data = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'number_of_seats' => 'nullable|string|max:255',
                'type' => 'sometimes|required|in:sedan,microbus',
                'plate_numbers' => 'nullable|string|max:255',
                'plate_letters' => 'nullable|string|max:255',
                'color' => 'nullable|string|max:255',
                'status' => 'nullable|in:active,inactive,maintenance,in_use',
            ]);
            $car = $this->carService->updateCar($car, $data);

            return response()->json([
                'status' => 'success',
                'message' => __('api.cars.updated'),
                'data' => new CarResource($car),
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.cars.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Car $car)
    {
        try {
            $this->carService->deleteCar($car);

            return response()->json([
                'status' => 'success',
                'message' => __('api.cars.deleted'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.cars.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
