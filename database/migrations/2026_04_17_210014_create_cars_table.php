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
        Schema::create('cars', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('number_of_seats')->nullable();
            $table->enum('type', ['sedan', 'microbus'])->nullable();
            $table->string('plate_numbers')->nullable();
            $table->string('plate_letters')->nullable();
            $table->string('color')->nullable();
            $table->enum('status', ['active', 'inactive', 'maintenance', 'in_use'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cars');
    }
};
