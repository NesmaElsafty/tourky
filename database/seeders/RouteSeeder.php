<?php

namespace Database\Seeders;

use App\Models\Point;
use App\Models\Role;
use App\Models\Route;
use App\Models\Time;
use App\Models\User;
use Illuminate\Database\Seeder;

class RouteSeeder extends Seeder
{
    public function run(): void
    {
        $companyRoleId = Role::query()->where('name_en', 'Company')->value('id');
        $companyUserId = $companyRoleId !== null
            ? User::query()->where('type', 'admin')->where('role_id', $companyRoleId)->value('id')
            : null;

        $definitions = $this->cairoRoutes();
        if ($companyUserId !== null) {
            $definitions[] = $this->companyShuttleRoute((int) $companyUserId);
        }

        foreach ($definitions as $routeData) {
            $points = $routeData['points'];
            unset($routeData['points']);

            $routeData = array_merge([
                'type' => 'b2c',
                'company_id' => null,
                'is_active' => true,
            ], $routeData);

            $route = Route::query()->create($routeData);

            foreach ($points as $pointData) {
                $times = $pointData['times'] ?? [];
                $t = (float) $pointData['t'];
                unset($pointData['t'], $pointData['times']);

                $point = Point::factory()
                    ->alongRoute($route, $t)
                    ->create(array_merge($pointData, [
                        'route_id' => $route->id,
                    ]));

                foreach ($times as $timeRow) {
                    Time::query()->create([
                        'point_id' => $point->id,
                        'pickup_time' => $timeRow['pickup_time'],
                        'is_active' => $timeRow['is_active'] ?? true,
                    ]);
                }
            }
        }
    }

    /**
     * Curated Cairo routes; each point uses fraction $t along start→end for lat/long.
     * Each point has many scheduled pickup times.
     *
     * @return list<array<string, mixed>>
     */
    private function cairoRoutes(): array
    {
        return [
            [
                'name_en' => 'Tahrir to Ramses',
                'name_ar' => 'التحرير إلى محطة رمسيس',
                'start_point_en' => 'Tahrir Square',
                'start_point_ar' => 'ميدان التحرير',
                'start_lat' => '30.047800',
                'start_long' => '31.233600',
                'end_point_en' => 'Ramses Railway Station',
                'end_point_ar' => 'محطة مصر (رمسيس)',
                'end_lat' => '30.062100',
                'end_long' => '31.246800',
                'is_active' => true,
                'points' => [
                    [
                        't' => 0.22,
                        'name_en' => 'American University in Cairo (Downtown)',
                        'name_ar' => 'الجامعة الأمريكية (وسط البلد)',
                        'times' => [
                            ['pickup_time' => '06:08', 'is_active' => true],
                            ['pickup_time' => '14:10', 'is_active' => true],
                        ],
                    ],
                    [
                        't' => 0.48,
                        'name_en' => 'Bab El Louq',
                        'name_ar' => 'باب اللوق',
                        'times' => [
                            ['pickup_time' => '06:14', 'is_active' => true],
                            ['pickup_time' => '14:20', 'is_active' => true],
                        ],
                    ],
                    [
                        't' => 0.9,
                        'name_en' => 'Ramses Railway Station',
                        'name_ar' => 'محطة مصر (رمسيس)',
                        'times' => [
                            ['pickup_time' => '06:26', 'is_active' => true],
                            ['pickup_time' => '19:00', 'is_active' => true],
                        ],
                    ],
                ],
            ],
            [
                'name_en' => 'Maadi to Downtown',
                'name_ar' => 'المعادي إلى وسط البلد',
                'start_point_en' => 'Maadi Corniche',
                'start_point_ar' => 'كورنيش المعادي',
                'start_lat' => '29.960800',
                'start_long' => '31.250500',
                'end_point_en' => 'Tahrir Square',
                'end_point_ar' => 'ميدان التحرير',
                'end_lat' => '30.047800',
                'end_long' => '31.233600',
                'is_active' => true,
                'points' => [
                    [
                        't' => 0.25,
                        'name_en' => 'Zahraa Maadi',
                        'name_ar' => 'زهراء المعادي',
                        'times' => [
                            ['pickup_time' => '05:52', 'is_active' => true],
                            ['pickup_time' => '13:45', 'is_active' => true],
                        ],
                    ],
                    [
                        't' => 0.5,
                        'name_en' => 'Sayeda Zeinab',
                        'name_ar' => 'السيدة زينب',
                        'times' => [
                            ['pickup_time' => '06:02', 'is_active' => true],
                            ['pickup_time' => '14:05', 'is_active' => true],
                        ],
                    ],
                    [
                        't' => 0.78,
                        'name_en' => 'Opera House (Gezira)',
                        'name_ar' => 'دار الأوبرا',
                        'times' => [
                            ['pickup_time' => '06:12', 'is_active' => true],
                            ['pickup_time' => '15:30', 'is_active' => true],
                        ],
                    ],
                ],
            ],
            [
                'name_en' => 'Heliopolis to Cairo Airport',
                'name_ar' => 'مصر الجديدة إلى مطار القاهرة',
                'start_point_en' => 'Korba, Heliopolis',
                'start_point_ar' => 'الكوربة، مصر الجديدة',
                'start_lat' => '30.087500',
                'start_long' => '31.324400',
                'end_point_en' => 'Cairo International Airport (T3)',
                'end_point_ar' => 'مطار القاهرة الدولي',
                'end_lat' => '30.121900',
                'end_long' => '31.405600',
                'is_active' => true,
                'points' => [
                    [
                        't' => 0.2,
                        'name_en' => 'Roxy Square',
                        'name_ar' => 'ميدان روكسي',
                        'times' => [
                            ['pickup_time' => '06:22', 'is_active' => true],
                            ['pickup_time' => '12:00', 'is_active' => true],
                            ['pickup_time' => '18:30', 'is_active' => true],
                        ],
                    ],
                    [
                        't' => 0.45,
                        'name_en' => 'Cairo Festival City Mall',
                        'name_ar' => 'كايرو فستيفال سيتي',
                        'times' => [
                            ['pickup_time' => '06:32', 'is_active' => true],
                            ['pickup_time' => '14:15', 'is_active' => true],
                        ],
                    ],
                    [
                        't' => 0.7,
                        'name_en' => 'Ring Road (airport branch)',
                        'name_ar' => 'الطريق الدائري (فرع المطار)',
                        'times' => [
                            ['pickup_time' => '06:42', 'is_active' => true],
                            ['pickup_time' => '22:00', 'is_active' => true],
                        ],
                    ],
                ],
            ],
            [
                'name_en' => 'Giza Pyramids to Zamalek',
                'name_ar' => 'الأهرام إلى الزمالك',
                'start_point_en' => 'Giza Pyramids entrance',
                'start_point_ar' => 'منطقة أهرامات الجيزة',
                'start_lat' => '29.979200',
                'start_long' => '31.134200',
                'end_point_en' => 'Zamalek (26th of July Corridor)',
                'end_point_ar' => 'الزمالك',
                'end_lat' => '30.061000',
                'end_long' => '31.223600',
                'is_active' => true,
                'points' => [
                    [
                        't' => 0.25,
                        'name_en' => 'Remaya Square',
                        'name_ar' => 'ميدان الرماية',
                        'times' => [
                            ['pickup_time' => '07:10', 'is_active' => true],
                            ['pickup_time' => '16:00', 'is_active' => true],
                        ],
                    ],
                    [
                        't' => 0.5,
                        'name_en' => 'Mohandessin (Gameat Al Dewal)',
                        'name_ar' => 'المهندسين',
                        'times' => [
                            ['pickup_time' => '07:22', 'is_active' => true],
                            ['pickup_time' => '15:45', 'is_active' => true],
                        ],
                    ],
                    [
                        't' => 0.75,
                        'name_en' => 'Agouza bridge (Nile side)',
                        'name_ar' => 'كوبري العجوزة',
                        'times' => [
                            ['pickup_time' => '07:34', 'is_active' => true],
                            ['pickup_time' => '19:10', 'is_active' => true],
                        ],
                    ],
                ],
            ],
            [
                'name_en' => 'Old Cairo to Khan el-Khalili',
                'name_ar' => 'مصر القديمة إلى خان الخليلي',
                'start_point_en' => 'Coptic Cairo',
                'start_point_ar' => 'مصر القديمة',
                'start_lat' => '30.005200',
                'start_long' => '31.230400',
                'end_point_en' => 'Khan el-Khalili',
                'end_point_ar' => 'خان الخليلي',
                'end_lat' => '30.047300',
                'end_long' => '31.261600',
                'is_active' => true,
                'points' => [
                    [
                        't' => 0.28,
                        'name_en' => 'Mar Girgis metro',
                        'name_ar' => 'مترو مار جرجس',
                        'times' => [
                            ['pickup_time' => '06:36', 'is_active' => true],
                            ['pickup_time' => '13:20', 'is_active' => true],
                        ],
                    ],
                    [
                        't' => 0.55,
                        'name_en' => 'Bab Zuweila',
                        'name_ar' => 'باب زويلة',
                        'times' => [
                            ['pickup_time' => '06:44', 'is_active' => true],
                            ['pickup_time' => '14:00', 'is_active' => true],
                        ],
                    ],
                    [
                        't' => 0.82,
                        'name_en' => 'Al-Azhar Mosque area',
                        'name_ar' => 'منطقة الأزهر',
                        'times' => [
                            ['pickup_time' => '06:52', 'is_active' => true],
                            ['pickup_time' => '17:30', 'is_active' => true],
                        ],
                    ],
                ],
            ],
            [
                'name_en' => 'Nasr City to New Administrative Capital',
                'name_ar' => 'مدينة نصر إلى العاصمة الإدارية',
                'start_point_en' => 'Abbas El Akkad, Nasr City',
                'start_point_ar' => 'عباس العقاد، مدينة نصر',
                'start_lat' => '30.050800',
                'start_long' => '31.394400',
                'end_point_en' => 'New Capital ring road (west access)',
                'end_point_ar' => 'العاصمة الإدارية (طريق الدائري)',
                'end_lat' => '30.007400',
                'end_long' => '31.491300',
                'is_active' => true,
                'points' => [
                    [
                        't' => 0.2,
                        'name_en' => 'Stadium metro interchange',
                        'name_ar' => 'محطة ستاد',
                        'times' => [
                            ['pickup_time' => '06:52', 'is_active' => true],
                            ['pickup_time' => '14:40', 'is_active' => true],
                        ],
                    ],
                    [
                        't' => 0.45,
                        'name_en' => 'New Cairo Fifth Settlement',
                        'name_ar' => 'التجمع الخامس',
                        'times' => [
                            ['pickup_time' => '07:02', 'is_active' => true],
                            ['pickup_time' => '15:15', 'is_active' => true],
                        ],
                    ],
                    [
                        't' => 0.7,
                        'name_en' => 'Badr City checkpoint',
                        'name_ar' => 'مدينة بدر',
                        'times' => [
                            ['pickup_time' => '07:12', 'is_active' => true],
                            ['pickup_time' => '18:45', 'is_active' => true],
                        ],
                    ],
                    [
                        't' => 0.9,
                        'name_en' => 'Capital Business District approach',
                        'name_ar' => 'منطقة الأعمال بالعاصمة',
                        'times' => [
                            ['pickup_time' => '07:22', 'is_active' => true],
                            ['pickup_time' => '20:00', 'is_active' => true],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * B2B route tied to a company admin (seeded after AdminUserSeeder).
     *
     * @return array<string, mixed>
     */
    private function companyShuttleRoute(int $companyUserId): array
    {
        return [
            'name_en' => 'Company shuttle (Heliopolis to Airport)',
            'name_ar' => 'نقل الشركة (مصر الجديدة إلى المطار)',
            'start_point_en' => 'Korba, Heliopolis',
            'start_point_ar' => 'الكوربة، مصر الجديدة',
            'start_lat' => '30.087500',
            'start_long' => '31.324400',
            'end_point_en' => 'Cairo International Airport (T3)',
            'end_point_ar' => 'مطار القاهرة الدولي',
            'end_lat' => '30.121900',
            'end_long' => '31.405600',
            'type' => 'b2b',
            'company_id' => $companyUserId,
            'is_active' => true,
            'points' => [
                [
                    't' => 0.35,
                    'name_en' => 'Cairo Festival City Mall',
                    'name_ar' => 'كايرو فستيفال سيتي',
                    'times' => [
                        ['pickup_time' => '06:40', 'is_active' => true],
                        ['pickup_time' => '14:20', 'is_active' => true],
                    ],
                ],
                [
                    't' => 0.72,
                    'name_en' => 'Ring Road (airport branch)',
                    'name_ar' => 'الطريق الدائري (فرع المطار)',
                    'times' => [
                        ['pickup_time' => '06:50', 'is_active' => true],
                        ['pickup_time' => '21:30', 'is_active' => true],
                    ],
                ],
            ],
        ];
    }
}
