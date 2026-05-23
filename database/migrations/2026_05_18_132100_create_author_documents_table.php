<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('author_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manuscript_id')->constrained('manuscripts')->onDelete('cascade');
            $table->string('document_type'); // Contoh: 'surat_pernyataan', 'ktp', 'rekening'
            $table->string('file_path');
            $table->integer('file_size_kb');
            $table->boolean('is_verified')->default(false);
            $table->timestamp('uploaded_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('author_documents');
    }
};