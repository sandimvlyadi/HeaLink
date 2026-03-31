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
        Schema::create('health_screenings', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('height_cm', 5, 2)->nullable();
            $table->decimal('weight_kg', 5, 2)->nullable();
            $table->decimal('bmi', 5, 2)->nullable()
                  ->comment('Auto-calculated: weight_kg / (height_m ^ 2)');
            $table->smallInteger('systolic')->nullable()
                  ->comment('Tekanan darah sistolik (mmHg)');
            $table->smallInteger('diastolic')->nullable()
                  ->comment('Tekanan darah diastolik (mmHg)');
            $table->jsonb('phq9_answers')->nullable()
                  ->comment('Array[9] integer 0–3, jawaban PHQ-9');
            $table->smallInteger('phq9_score')->nullable()
                  ->comment('Sum phq9_answers (0–27). Auto-calculated.');
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['user_id', 'created_at']); // Query "screening terbaru user X"
            $table->index('phq9_score');               // Filter pasien dengan skor tinggi
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('health_screenings');
    }
};
