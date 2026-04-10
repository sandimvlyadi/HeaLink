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
        Schema::create('mental_status_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('risk_level', 15)->comment("'low'|'medium'|'high'|'critical'");
            $table->string('detected_emotion', 50)->nullable();
            $table->text('summary_note')->nullable();
            $table->jsonb('contributing_factors')->nullable()
                ->comment('{"hrv": 0.8, "sleep": 0.5, "sentiment": -0.7, "phq9": 0.6}');
            $table->decimal('risk_score', 5, 2)->nullable()->comment('0.00–100.00');
            $table->timestamps();

            // Indexes — tabel ini di-query intensif untuk dashboard
            $table->index(['user_id', 'created_at']);
            $table->index(['user_id', 'risk_level']);
            $table->index('risk_level');                     // Filter global by risk
            $table->index(['risk_level', 'created_at']);     // Dashboard: pasien kritis terbaru
            $table->index('risk_score');                     // Sort by score
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mental_status_logs');
    }
};
