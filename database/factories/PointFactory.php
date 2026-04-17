<?php

namespace Database\Factories;

use App\Models\Point;
use App\Models\Route;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Point>
 */
class PointFactory extends Factory
{
    protected $model = Point::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name_en' => fake()->words(2, true).' pickup',
            'name_ar' => 'نقطة '.fake()->numerify('##'),
            'lat' => (string) round(29.96 + fake()->randomFloat(4, 0, 0.16), 6),
            'long' => (string) round(31.20 + fake()->randomFloat(4, 0, 0.22), 6),
            'route_id' => Route::factory(),
        ];
    }

    /**
     * Place the point on the straight segment between the route's start and end coordinates.
     * $t should be strictly between 0 and 1 (e.g. 0.25 = quarter of the way from start to end).
     */
    public function alongRoute(Route $route, float $t): static
    {
        $t = max(0.001, min(0.999, $t));

        return $this->state(function (array $attributes) use ($route, $t): array {
            $lat0 = (float) $route->start_lat;
            $lon0 = (float) $route->start_long;
            $lat1 = (float) $route->end_lat;
            $lon1 = (float) $route->end_long;

            $lat = $lat0 + $t * ($lat1 - $lat0);
            $lon = $lon0 + $t * ($lon1 - $lon0);

            return [
                'route_id' => $route->getKey(),
                'lat' => (string) round($lat, 6),
                'long' => (string) round($lon, 6),
            ];
        });
    }
}
