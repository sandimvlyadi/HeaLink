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
        Schema::create('wearable_data', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('hrv_score', 6, 2)->nullable()
                  ->comment('Heart Rate Variability dalam ms');
            $table->smallInteger('heart_rate')->nullable()
                  ->comment('BPM');
            $table->decimal('stress_index', 5, 2)->nullable()
                  ->comment('0–100, derived dari HRV');
            $table->string('device_type', 50)->nullable()
                  ->comment('e.g. Garmin, Apple Watch, Simulated');
            $table->boolean('is_simulated')->default(false);
            $table->timestampTz('recorded_at'); // timezone-aware
            $table->timestamps();

            // Indexes — tabel ini akan SANGAT besar, indexing krusial
            $table->index(['user_id', 'recorded_at']);            // Query utama: data user dalam range waktu
            $table->index(['user_id', 'recorded_at', 'hrv_score']); // Covering index untuk dashboard chart
            $table->index('recorded_at');                         // Global query by time range
            $table->index('is_simulated');                        // Filter simulasi vs real
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wearable_data');
    }
};
