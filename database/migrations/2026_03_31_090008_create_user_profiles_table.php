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
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('gender', 10)->nullable(); // 'male' | 'female' | 'other'
            $table->date('dob')->nullable();
            $table->string('job', 100)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('avatar_path')->nullable();
            $table->text('bio')->nullable();
            $table->timestamps();
            $table->softDeletes();
            // user_id sudah UNIQUE — otomatis terindex
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_profiles');
    }
};
