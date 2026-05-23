<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('book_metadata', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manuscript_id')->constrained('manuscripts')->onDelete('cascade');
            $table->text('abstract')->nullable();
            $table->integer('page_count')->nullable();
            $table->string('category')->nullable();
            $table->string('field_of_study')->nullable();
            $table->string('institution');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_metadata');
    }
};