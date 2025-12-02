<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tests', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->morphs('testable'); // testable_type, testable_id for polymorphic relation
            
            // Test settings
            $table->integer('duration_minutes')->nullable(); // null = no time limit
            $table->decimal('passing_score', 5, 2)->default(50.00); // percentage
            $table->integer('max_attempts')->default(1);
            $table->boolean('shuffle_questions')->default(false);
            $table->boolean('show_correct_answers')->default(true);
            $table->boolean('show_results_immediately')->default(true);
            
            // Scheduling
            $table->timestamp('available_from')->nullable();
            $table->timestamp('available_until')->nullable();
            
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tests');
    }
};