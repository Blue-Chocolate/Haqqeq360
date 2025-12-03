<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('case_studies', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            
            // open / closed
            $table->enum('status', ['open', 'closed'])->default('open');
            
            // duration in minutes or hours (your choice)
            $table->integer('duration')->comment('duration in minutes');
            
            $table->longText('content'); // case text / problem scenario
            $table->foreignId('instructor_id')->constrained('users')->cascadeOnDelete();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('case_studies');
    }
};
