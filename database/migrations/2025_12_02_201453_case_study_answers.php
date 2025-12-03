<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('case_study_answers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('case_study_id')->constrained()->cascadeOnDelete();
            $table->foreignId('learner_id')->constrained('users')->cascadeOnDelete();

            $table->longText('answer_text')->nullable();

            $table->timestamp('submitted_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('case_study_answers');
    }
};