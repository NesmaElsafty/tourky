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
        Schema::table('track_trips', function (Blueprint $table) {
            //
            $table->foreignId('point_id')->nullable()->constrained('points')->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('users')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('track_trips', function (Blueprint $table) {
            $table->dropForeign(['point_id']);
            $table->dropForeign(['client_id']);
            $table->dropColumn(['point_id', 'client_id']);
        });
    }
};
