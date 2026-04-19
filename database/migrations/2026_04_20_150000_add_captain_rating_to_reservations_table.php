<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table): void {
            $table->unsignedTinyInteger('captain_rating')->nullable()->after('dropped_off_at');
            $table->text('captain_feedback')->nullable()->after('captain_rating');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table): void {
            $table->dropColumn(['captain_rating', 'captain_feedback']);
        });
    }
};
