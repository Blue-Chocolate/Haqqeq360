<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_attempt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            
            // For MCQ and True/False
            $table->foreignId('selected_option_id')->nullable()->constrained('question_options')->nullOnDelete();
            
            // For written questions
            $table->text('written_answer')->nullable();
            
            // Grading
            $table->boolean('is_correct')->nullable(); // auto-graded for MCQ/True-False
            $table->decimal('points_earned', 8, 2)->nullable();
            $table->text('feedback')->nullable(); // instructor feedback for written answers
            $table->foreignId('graded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('graded_at')->nullable();
            
            $table->timestamps();
            
            // One answer per question per attempt
            $table->unique(['test_attempt_id', 'question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_answers');
    }
};