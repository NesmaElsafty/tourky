<?php

namespace Database\Seeders;

use App\Models\Point;
use App\Models\Route;
use App\Models\RouteTime;
use App\Models\Time;
use App\Models\User;
use App\Services\ShuttleRouteScheduleService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class HsbcRouteSeeder extends Seeder
{
    /**
     * Clock time at the **first** stop for each scheduled run (e.g. morning / afternoon arrival waves).
     * Later stops use {@see ShuttleRouteScheduleService} (distance + average speed between consecutive coordinates).
     */
    private const RUN_FIRST_STOP_TIMES = ['06:15', '14:30'];

    public function run(): void
    {
        $path = database_path('data/hsbc_routes.json');
        if (! File::exists($path)) {
            $this->command?->warn('database/data/hsbc_routes.json not found; skipping HSBC routes.');

            return;
        }

        $hsbc = User::query()
            ->where('type', 'admin')
            ->where('name', 'HSBC')
            ->first();

        if ($hsbc === null) {
            $this->command?->warn('HSBC admin user not found; skipping HSBC routes.');

            return;
        }

        /** @var ShuttleRouteScheduleService $scheduleService */
        $scheduleService = app(ShuttleRouteScheduleService::class);

        /** @var list<array{sheet: string, points: list<array<string, mixed>>}> $definitions */
        $definitions = json_decode(File::get($path), true, 512, JSON_THROW_ON_ERROR);

        foreach ($definitions as $item) {
            $sheet = $item['sheet'];
            $points = $item['points'] ?? [];
            if ($points === []) {
                continue;
            }

            $first = $points[0];
            $last = $points[array_key_last($points)];

            $route = Route::query()->updateOrCreate(
                [
                    'company_id' => $hsbc->id,
                    'name_en' => $sheet,
                ],
                [
                    'name_ar' => $sheet,
                    'start_point_en' => $this->endpointLabel($first),
                    'start_point_ar' => $this->endpointLabel($first),
                    'start_lat' => $this->fmtCoord((float) $first['lat']),
                    'start_long' => $this->fmtCoord((float) $first['lon']),
                    'end_point_en' => $this->endpointLabel($last),
                    'end_point_ar' => $this->endpointLabel($last),
                    'end_lat' => $this->fmtCoord((float) $last['lat']),
                    'end_long' => $this->fmtCoord((float) $last['lon']),
                    'type' => 'b2b',
                    'is_active' => true,
                ]
            );

            RouteTime::query()->where('route_id', $route->id)->delete();
            $route->points()->delete();

            $coords = array_map(static fn (array $p): array => [
                'lat' => (float) $p['lat'],
                'lon' => (float) $p['lon'],
            ], $points);

            $runs = $scheduleService->buildPickupSchedules($coords, self::RUN_FIRST_STOP_TIMES);

            /** @var list<Point> $createdPoints */
            $createdPoints = [];
            foreach ($points as $p) {
                $createdPoints[] = Point::query()->create([
                    'route_id' => $route->id,
                    'name_en' => $this->pointName($p),
                    'name_ar' => $this->pointName($p),
                    'lat' => $this->fmtCoord((float) $p['lat']),
                    'long' => $this->fmtCoord((float) $p['lon']),
                ]);
            }

            foreach ($runs as $pickupsForRun) {
                $timeIds = [];
                foreach ($pickupsForRun as $index => $pickupTime) {
                    $time = Time::query()->create([
                        'point_id' => $createdPoints[$index]->id,
                        'pickup_time' => $pickupTime,
                        'is_active' => true,
                    ]);
                    $timeIds[] = $time->id;
                }

                RouteTime::query()->create([
                    'route_id' => $route->id,
                    'time_ids' => $timeIds,
                ]);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $p
     */
    private function endpointLabel(array $p): string
    {
        $poi = trim((string) ($p['poi_en'] ?? ''));
        $hood = trim((string) ($p['neighborhood_en'] ?? ''));
        if ($poi !== '' && $hood !== '') {
            return $poi.' — '.$hood;
        }

        return $poi !== '' ? $poi : ($hood !== '' ? $hood : 'Stop');
    }

    /**
     * @param  array<string, mixed>  $p
     */
    private function pointName(array $p): string
    {
        $poi = trim((string) ($p['poi_en'] ?? ''));
        $hood = trim((string) ($p['neighborhood_en'] ?? ''));
        $node = trim((string) ($p['node'] ?? ''));

        $base = $poi !== '' && $hood !== ''
            ? $poi.' ('.$hood.')'
            : ($poi !== '' ? $poi : ($hood !== '' ? $hood : 'Stop'));

        return $node !== '' ? $base.' ['.$node.']' : $base;
    }

    private function fmtCoord(float $value): string
    {
        return (string) round($value, 6);
    }
}
