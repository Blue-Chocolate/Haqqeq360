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
        Schema::create('lessons', function (Blueprint $table) {
    $table->id();
    $table->foreignId('unit_id')->constrained()->onDelete('cascade');
    $table->string('title');
    $table->text('content')->nullable();
    $table->integer('order')->default(0);
    $table->string('video_url')->nullable();
    $table->string('resource_link')->nullable();
    $table->string('attachment_path')->nullable();
    $table->boolean('published')->default(true);
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
