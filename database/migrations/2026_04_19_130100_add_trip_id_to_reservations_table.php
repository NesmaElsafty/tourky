<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table): void {
            $table->foreignId('trip_id')
                ->nullable()
                ->after('time_id')
                ->constrained('trips')
                ->nullOnDelete();
            $table->index(['trip_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table): void {
            $table->dropIndex(['trip_id', 'status']);
            $table->dropConstrainedForeignId('trip_id');
        });
    }
};
