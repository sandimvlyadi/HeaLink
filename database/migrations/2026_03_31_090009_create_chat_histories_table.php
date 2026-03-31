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
        Schema::create('chat_histories', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('message');
            $table->string('sender_type', 10)->comment("'user' atau 'ai'");
            $table->decimal('sentiment_score', 4, 3)->nullable()
                  ->comment('-1.000 (sangat negatif) s.d. 1.000 (sangat positif)');
            $table->string('detected_emotion', 50)->nullable();
            $table->jsonb('context_data')->nullable()
                  ->comment('Snapshot HRV, mood, dll saat pesan dikirim');
            $table->boolean('is_flagged')->default(false)
                  ->comment('Ditandai oleh dokter untuk review');
            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'created_at']);              // Riwayat chat user
            $table->index(['user_id', 'is_flagged']);              // Flagged messages
            $table->index('sentiment_score');                      // Filter negatif
            $table->index(['user_id', 'sender_type', 'created_at']); // Conversation view
        });

        // GIN index untuk full-text search di PostgreSQL
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("
                CREATE INDEX chat_histories_message_gin
                ON chat_histories
                USING gin(to_tsvector('simple', message))
            ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_histories');
    }
};
