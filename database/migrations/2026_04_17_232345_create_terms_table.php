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
        Schema::create('terms', function (Blueprint $table) {
            $table->id();
            $table->string('name_en')->nullable();
            $table->string('name_ar')->nullable();

            $table->string('description_en')->nullable();
            $table->string('description_ar')->nullable();

            $table->boolean('is_active')->default(true);

            $table->enum('type', ['terms_conditions', 'privacy_policy', 'FAQ']);
            $table->enum('user_type', ['client', 'captain']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('terms');
    }
};
