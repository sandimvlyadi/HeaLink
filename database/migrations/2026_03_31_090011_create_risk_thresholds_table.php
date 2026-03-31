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
        Schema::create('risk_thresholds', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('parameter_name', 50)->unique()
                  ->comment('hrv | sleep_duration | sentiment_score | phq9_score | stress_index');
            $table->decimal('low_min', 8, 3)->nullable();
            $table->decimal('low_max', 8, 3)->nullable();
            $table->decimal('medium_min', 8, 3)->nullable();
            $table->decimal('medium_max', 8, 3)->nullable();
            $table->decimal('high_min', 8, 3)->nullable();
            $table->decimal('high_max', 8, 3)->nullable();
            $table->decimal('weight', 4, 3)->default(0.25)
                  ->comment('Bobot parameter dalam kalkulasi risk score (total harus = 1.0)');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('risk_thresholds');
    }
};
