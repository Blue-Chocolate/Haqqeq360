<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
    {
        Schema::create('programs', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['course', 'bootcamp']);
            $table->string('title_ar');
            $table->string('title_en')->nullable();
            $table->string('slug')->unique();
            $table->text('description_ar')->nullable();
            $table->text('description_en')->nullable();
            $table->foreignId('category_id')->nullable()->constrained('categories')->onDelete('set null');
            $table->enum('difficulty_level', ['beginner', 'intermediate', 'advanced'])->nullable();
            $table->enum('delivery_mode', ['online', 'blended', 'in_person']);
            $table->integer('duration_weeks')->nullable();
            $table->integer('duration_days')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->decimal('discounted_price', 10, 2)->nullable();
            $table->string('currency', 3)->default('SAR');
            $table->string('cover_image_url', 500)->nullable();
            $table->boolean('is_published')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->integer('max_participants')->nullable();
            $table->integer('current_enrollments')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->timestamp('published_at')->nullable();

            $table->index('type');
            $table->index('is_published');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('programs');
    }
};
