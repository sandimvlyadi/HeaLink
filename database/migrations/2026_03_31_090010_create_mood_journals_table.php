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
        Schema::create('mood_journals', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('emoji', 10)->nullable();
            $table->string('mood', 20)->comment("'very_bad'|'bad'|'neutral'|'good'|'very_good'");
            $table->text('note')->nullable();
            $table->date('journal_date');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['user_id', 'journal_date']);
            $table->index(['user_id', 'journal_date']);
            $table->index('mood'); // Analisis distribusi mood
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mood_journals');
    }
};
