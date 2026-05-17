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
        //
        Schema::table('reservations', function (Blueprint $table) {
            $table->decimal('price', 10, 2)->nullable()->after('status');
            $table->foreignId('drop_off_time_id')->nullable()->constrained('times')->nullOnDelete()->after('time_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn('price', 10, 2);
            $table->dropConstrainedForeignId('drop_off_time_id');
        });
    }
};
