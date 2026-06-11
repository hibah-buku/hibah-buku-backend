<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviewer_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manuscript_id')->constrained('manuscripts')->onDelete('cascade');
            $table->foreignId('reviewer_id')->constrained('reviewers')->onDelete('cascade');
            $table->string('reviewer_name')->nullable();
            $table->string('reviewer_email')->nullable();
            $table->string('book_title');
            $table->foreignId('author_id')->nullable()->constrained('authors')->nullOnDelete();
            $table->string('author_email')->nullable();
            $table->string('manuscript_file_url')->nullable();
            $table->enum('status', ['assigned', 'under_review', 'completed'])->default('assigned');
            $table->timestamp('deadline_review')->nullable();
            $table->enum('rekomendasi_akhir', ['Tanpa Perbaikan', 'Perbaikan Minor', 'Perbaikan Mayor'])->nullable();
            $table->longText('general_comments')->nullable();
            $table->text('review_notes')->nullable();
            $table->decimal('final_score', 5, 2)->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->unique(['manuscript_id', 'reviewer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviewer_assignments');
    }
};
