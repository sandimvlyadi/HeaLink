<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('facial_emotion_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('consultation_id')->constrained()->cascadeOnDelete();
            $table->string('detected_mood', 50);
            $table->decimal('confidence', 4, 3)->nullable()->comment('0.000–1.000');
            $table->jsonb('emotion_breakdown')->nullable()
                ->comment('{"happy": 0.1, "sad": 0.6, "anxious": 0.3}');
            $table->timestampTz('captured_at');
            $table->timestamps();

            $table->index(['consultation_id', 'captured_at']);
            $table->index('captured_at');
        });

        // GIN index untuk query di dalam emotion_breakdown JSONB
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE INDEX facial_emotion_breakdown_gin ON facial_emotion_logs USING gin(emotion_breakdown)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('facial_emotion_logs');
    }
};
