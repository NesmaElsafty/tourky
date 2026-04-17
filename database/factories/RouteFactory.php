<?php

namespace Database\Factories;

use App\Models\Route;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Route>
 */
class RouteFactory extends Factory
{
    protected $model = Route::class;

    /**
     * Cairo-only demo segments (approximate coordinates).
     *
     * @var list<array{
     *     name_en: string,
     *     name_ar: string,
     *     start_point_en: string,
     *     start_point_ar: string,
     *     start_lat: string,
     *     start_long: string,
     *     end_point_en: string,
     *     end_point_ar: string,
     *     end_lat: string,
     *     end_long: string
     * }>
     */
    private static function cairoSegments(): array
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
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $segment = fake()->randomElement(self::cairoSegments());

        return array_merge($segment, [
            'is_active' => true,
        ]);
    }
}
