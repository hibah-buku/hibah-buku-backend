<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('review_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained('reviewer_assignments')->onDelete('cascade');
            $table->foreignId('rubric_id')->constrained('review_rubrics')->onDelete('cascade');
            $table->integer('score');
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->unique(['assignment_id', 'rubric_id']);
            $table->index('assignment_id');
            $table->index('rubric_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_scores');
    }
};
