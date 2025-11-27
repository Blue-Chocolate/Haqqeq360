<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bootcamps', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->boolean('special')->default(false);
            $table->text('description');
            $table->integer('duration_weeks')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->decimal('discounted_price', 10, 2)->nullable();           
            $table->enum('level', ['beginner', 'intermediate', 'advanced'])->default('beginner');
            $table->date('start_date')->nullable();
            $table->enum('mode', ['online', 'hybrid', 'offline'])->default('online');
            $table->integer('seats')->nullable();
            $table->boolean('certificate')->default(false);
            $table->string('cover_image')->nullable();
            $table->foreignId('instructor_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bootcamps');
    }
};