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
        Schema::create('sleep_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->smallInteger('duration_minutes')->unsigned();
            $table->decimal('quality_score', 4, 2)
                ->comment('0.00–10.00 dari wearable atau self-report');
            $table->string('quality_category', 10)->nullable(); // 'poor'|'fair'|'good'
            $table->time('sleep_time')->nullable();
            $table->time('wake_time')->nullable();
            $table->date('sleep_date');
            $table->timestamps();

            // Unique: 1 log tidur per user per hari
            $table->unique(['user_id', 'sleep_date']);
            // Indexes
            $table->index(['user_id', 'sleep_date']); // Query history tidur
            $table->index('quality_score');            // Filter tidur buruk
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sleep_logs');
    }
};
