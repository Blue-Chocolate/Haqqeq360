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
        Schema::create('about_pages', function (Blueprint $table) {
            $table->id();
            
            // Hero Section (EPIC 11.1)
            $table->string('hero_title')->default('من نحن في أكاديمية حقق 360');
            $table->text('hero_description')->nullable(); // ≤ 30 words
            $table->string('hero_background_image')->nullable();
            $table->integer('hero_overlay_opacity')->default(40); // 0-100
            
            // About Us Content (EPIC 11.2)
            $table->text('about_content')->nullable(); // ≤ 120 words
            $table->boolean('show_about_icons')->default(false);
            
            // Vision Section (EPIC 11.3)
            $table->string('vision_title')->default('رؤيتنا');
            $table->text('vision_content')->nullable(); // ≤ 60 words
            $table->string('vision_icon')->nullable();
            $table->boolean('show_vision_section')->default(true);
            
            // Status and metadata
            $table->boolean('is_active')->default(true);
            $table->string('status')->default('published'); // draft, published
            $table->integer('display_order')->default(0);
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('about_pages');
    }
};