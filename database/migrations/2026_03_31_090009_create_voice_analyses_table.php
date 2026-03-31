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
        Schema::create('voice_analyses', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('audio_path')->comment('Path di Laravel Storage');
            $table->decimal('stress_level', 5, 2)->nullable()->comment('0–100');
            $table->string('detected_emotion', 50)->nullable()
                  ->comment('calm, anxious, sad, angry, neutral');
            $table->decimal('confidence_score', 4, 3)->nullable()->comment('0.000–1.000');
            $table->jsonb('raw_analysis')->nullable()
                  ->comment('Full payload dari AI service (OpenAI/Gemini)');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'created_at']);
            $table->index('stress_level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('voice_analyses');
    }
};
