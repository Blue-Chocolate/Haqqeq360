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
                Schema::create('article_tag_pivot', function (Blueprint $table) {
            $table->foreignId('article_id')->constrained('knowledge_base_articles')->onDelete('cascade');
            $table->foreignId('tag_id')->constrained('article_tags')->onDelete('cascade');
            $table->primary(['article_id', 'tag_id']);
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
