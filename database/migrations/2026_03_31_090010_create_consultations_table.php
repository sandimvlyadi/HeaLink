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
        Schema::create('consultations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('patient_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('medic_id')->constrained('users')->cascadeOnDelete();
            $table->string('session_token', 128)->unique()
                  ->comment('Token untuk join video call (Agora/Jitsi/Livekit)');
            $table->string('status', 20)->default('pending')
                  ->comment("'pending'|'ongoing'|'completed'|'cancelled'");
            $table->timestampTz('scheduled_at')->nullable();
            $table->timestampTz('started_at')->nullable();
            $table->timestampTz('ended_at')->nullable();
            $table->text('notes')->nullable()->comment('Catatan dokter post-konsultasi');
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['patient_id', 'status']);
            $table->index(['medic_id', 'status']);
            $table->index(['medic_id', 'scheduled_at']); // Calendar view dokter
            $table->index('status');
            $table->index('scheduled_at');
            $table->index('deleted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consultations');
    }
};
