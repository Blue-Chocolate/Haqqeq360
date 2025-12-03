<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('case_study_answer_files', function (Blueprint $table) {
            $table->id();

            $table->foreignId('answer_id')
                  ->constrained('case_study_answers')
                  ->cascadeOnDelete();

            $table->string('file_path');
            $table->string('original_name');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('case_study_answer_files');
    }
};