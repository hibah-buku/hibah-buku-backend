<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manuscript_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manuscript_id')->constrained('manuscripts')->onDelete('cascade');
            $table->string('file_type'); // Contoh: 'draft_awal', 'revisi_1'
            $table->string('file_path');
            $table->string('original_name');
            $table->integer('file_size_kb');
            $table->string('mime_type');
            $table->timestamp('uploaded_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manuscript_files');
    }
};