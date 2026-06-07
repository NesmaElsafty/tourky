<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trip_cars', function (Blueprint $table): void {
            $table->timestamp('late_start_penalty_applied_at')->nullable()->after('captain_id');
        });
    }

    public function down(): void
    {
        Schema::table('trip_cars', function (Blueprint $table): void {
            $table->dropColumn('late_start_penalty_applied_at');
        });
    }
};
