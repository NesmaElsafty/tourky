<?php

namespace App\Services;

use App\Models\Car;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CarService
{
    public function getCarsPaginated(int $perPage = 10): LengthAwarePaginator
    {
        return Car::query()
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function getCarById(int $id): Car
    {
        return Car::query()->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createCar(array $data): Car
    {
        return Car::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateCar(Car $car, array $data): Car
    {
        $car->update($data);

        return $car->fresh();
    }

    public function deleteCar(Car $car): void
    {
        $car->delete();
    }
}
