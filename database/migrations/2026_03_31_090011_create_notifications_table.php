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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title', 150);
            $table->text('message');
            $table->string('type', 20)->default('info')
                ->comment("'info'|'warning'|'critical'|'reminder'");
            $table->boolean('is_read')->default(false);
            $table->jsonb('action_data')->nullable()
                ->comment('Deep link atau action payload untuk mobile');
            $table->timestamps();
            $table->softDeletes();

            // Indexes — notifikasi di-query sangat sering
            $table->index(['user_id', 'is_read']);
            $table->index(['user_id', 'type', 'is_read']);
            $table->index(['user_id', 'created_at']);
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
