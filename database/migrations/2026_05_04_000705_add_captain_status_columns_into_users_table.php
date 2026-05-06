<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Captain-only semantics: keep null / defaults for non-captain rows; app enforces usage by type.
            $table->enum('status', ['available', 'unavailable', 'on_trip', 'in_vacation'])->nullable();
            $table->boolean('has_trip')->default(false);
            $table->foreignId('trip_id')->nullable()->constrained('trips')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['trip_id']);
            $table->dropColumn(['status', 'has_trip', 'trip_id']);
        });
    }
};
