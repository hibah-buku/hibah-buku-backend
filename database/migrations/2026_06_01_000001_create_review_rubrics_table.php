<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('review_rubrics', function (Blueprint $table) {
            $table->id();
            $table->string('criteria_name');
            $table->integer('max_score')->default(100);
            $table->enum('applicable_book_type', ['Buku Ajar', 'Buku Referensi', 'Both'])->default('Both');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_rubrics');
    }
};
