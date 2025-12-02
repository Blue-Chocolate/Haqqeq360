<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            
            $table->enum('status', ['in_progress', 'submitted', 'graded'])->default('in_progress');
            
            $table->timestamp('started_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('graded_at')->nullable();
            
            // Scoring
            $table->decimal('score', 8, 2)->nullable(); // actual score earned
            $table->decimal('total_points', 8, 2)->nullable(); // total possible points
            $table->decimal('percentage', 5, 2)->nullable(); // percentage score
            $table->boolean('passed')->nullable();
            
            // Metadata
            $table->integer('attempt_number')->default(1);
            $table->json('question_order')->nullable(); // if questions are shuffled
            
            $table->timestamps();
            
            // Ensure unique attempt numbers per user per test
            $table->unique(['test_id', 'user_id', 'attempt_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_attempts');
    }
};