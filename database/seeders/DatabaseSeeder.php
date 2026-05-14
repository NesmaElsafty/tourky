<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            PermissionSeeder::class,
            RolePermissionSeeder::class,
            AdminUserSeeder::class,
            CaptainUserSeeder::class,
            ClientUserSeeder::class,
            CarSeeder::class,
            HsbcRouteSeeder::class,
            ReservationSeeder::class,
            TripSeeder::class,
            CaptainRatingSeeder::class,
            CaptainReportSeeder::class,
            TicketSeeder::class,
            FeedbackSeeder::class,
            TermSeeder::class,
            NotificationSeeder::class,
            FiredNotificationSeeder::class,
        ]);
    }
}
