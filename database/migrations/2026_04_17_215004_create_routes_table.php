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
        Schema::create('routes', function (Blueprint $table) {
            $table->id();
            $table->string('name_en')->nullable();
            $table->string('name_ar')->nullable();

            $table->string('start_point_en')->nullable();
            $table->string('start_point_ar')->nullable();

            $table->string('start_lat')->nullable();
            $table->string('start_long')->nullable();

            $table->string('end_point_en')->nullable();
            $table->string('end_point_ar')->nullable();

            $table->string('end_lat')->nullable();
            $table->string('end_long')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('routes');
    }
};
