<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\PaginationHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCarRequest;
use App\Http\Requests\Admin\UpdateCarRequest;
use App\Http\Resources\CarResource;
use App\Models\Car;
use App\Services\CarService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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
            if ($e instanceof ModelNotFoundException) {
                throw $e;
            }
            return response()->json([
                'status' => 'error',
                'message' => __('api.cars.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(Request $request, $id)
    {
        try {
            $car = Car::query()->findOrFail($id);
            return response()->json([
                'status' => 'success',
                'message' => __('api.cars.retrieved'),
                'data' => new CarResource($car->load('captain')),
            ]);
        } catch (\Exception $e) {
            if ($e instanceof ModelNotFoundException) {
                throw $e;
            }
            return response()->json([
                'status' => 'error',
                'message' => __('api.cars.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(StoreCarRequest $request)
    {
        try {
            $data = $request->validated();
            $car = $this->carService->createCar($data);

            return response()->json([
                'status' => 'success',
                'message' => __('api.cars.created'),
                'data' => new CarResource($car),
            ], 201);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            if ($e instanceof ModelNotFoundException) {
                throw $e;
            }
            return response()->json([
                'status' => 'error',
                'message' => __('api.cars.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(UpdateCarRequest $request, $id)
    {
        try {
            $data = $request->validated();
            $car = Car::query()->findOrFail($id);
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

    public function destroy($id)
    {
        try {
            $car = Car::query()->findOrFail($id);
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
