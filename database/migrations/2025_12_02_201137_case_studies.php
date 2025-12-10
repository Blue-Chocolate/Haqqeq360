<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('case_studies', function (Blueprint $table) {
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unit_id')->nullable()->after('course_id')->constrained()->nullOnDelete();
            $table->foreignId('lesson_id')->nullable()->after('unit_id')->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('content');  
            $table->integer('duration')->default(60); // duration in minutes
            $table->string('status')->default('draft');
            $table->foreignId('instructor_id')->constrained('users')->cascadeOnDelete();
            $table->text('guidelines')->nullable()->after('content');
            $table->string('attachment')->nullable()->after('guidelines');
            $table->integer('max_score')->default(100)->after('duration');
            $table->integer('passing_score')->default(50)->after('max_score');
            $table->integer('max_attempts')->default(1)->after('passing_score');
            $table->boolean('allow_late_submission')->default(false)->after('max_attempts');
            $table->boolean('show_model_answer')->default(false)->after('allow_late_submission');
            $table->boolean('peer_review_enabled')->default(false)->after('show_model_answer');
            $table->text('model_answer')->nullable()->after('peer_review_enabled');
            $table->timestamp('available_from')->nullable()->after('model_answer');
            $table->timestamp('available_until')->nullable()->after('available_from');
        });
    }

    public function down(): void
    {
        Schema::table('case_studies', function (Blueprint $table) {
            $table->dropForeign(['unit_id']);
            $table->dropForeign(['lesson_id']);
            $table->dropColumn([
                'unit_id',
                'lesson_id',
                'guidelines',
                'attachment',
                'max_score',
                'passing_score',
                'max_attempts',
                'allow_late_submission',
                'show_model_answer',
                'peer_review_enabled',
                'model_answer',
                'available_from',
                'available_until',
            ]);
        });
    }
};