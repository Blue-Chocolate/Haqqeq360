<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_id')->constrained()->cascadeOnDelete();
            
            $table->enum('type', ['mcq', 'true_false', 'written']); // question type
            $table->text('question_text');
            $table->text('explanation')->nullable(); // optional explanation for the answer
            
            $table->decimal('points', 8, 2)->default(1.00);
            $table->integer('order')->default(0); // for ordering questions
            
            $table->boolean('is_required')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};