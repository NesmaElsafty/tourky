<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trip_cars', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('trip_id')->constrained('trips')->cascadeOnDelete();
            $table->foreignId('car_id')->constrained('cars')->cascadeOnDelete();
            $table->foreignId('captain_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['trip_id', 'car_id']);
        });

        $rows = DB::table('trips')->select(['id', 'car_id', 'captain_id', 'created_at', 'updated_at'])->get();
        foreach ($rows as $row) {
            DB::table('trip_cars')->insert([
                'trip_id' => $row->id,
                'car_id' => $row->car_id,
                'captain_id' => $row->captain_id,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);
        }

        Schema::table('reservations', function (Blueprint $table): void {
            $table->foreignId('trip_car_id')
                ->nullable()
                ->after('trip_id')
                ->constrained('trip_cars')
                ->nullOnDelete();
        });

        foreach (DB::table('reservations')->whereNotNull('trip_id')->select(['id', 'trip_id'])->cursor() as $res) {
            $tripCarId = DB::table('trip_cars')
                ->where('trip_id', $res->trip_id)
                ->value('id');
            if ($tripCarId !== null) {
                DB::table('reservations')->where('id', $res->id)->update(['trip_car_id' => $tripCarId]);
            }
        }

        Schema::table('trips', function (Blueprint $table): void {
            $table->dropForeign(['captain_id']);
            $table->dropForeign(['car_id']);
            $table->dropColumn(['captain_id', 'car_id']);
        });

        Schema::table('reservations', function (Blueprint $table): void {
            $table->index(['trip_car_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table): void {
            $table->foreignId('captain_id')->nullable()->after('id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('car_id')->nullable()->after('captain_id')->constrained('cars')->cascadeOnDelete();
        });

        $tripIds = DB::table('trip_cars')->distinct()->pluck('trip_id');
        foreach ($tripIds as $tripId) {
            $first = DB::table('trip_cars')->where('trip_id', $tripId)->orderBy('id')->first();
            if ($first !== null) {
                DB::table('trips')->where('id', $tripId)->update([
                    'captain_id' => $first->captain_id,
                    'car_id' => $first->car_id,
                ]);
            }
        }

        Schema::table('reservations', function (Blueprint $table): void {
            $table->dropIndex(['trip_car_id', 'status']);
            $table->dropConstrainedForeignId('trip_car_id');
        });

        Schema::dropIfExists('trip_cars');
    }
};
