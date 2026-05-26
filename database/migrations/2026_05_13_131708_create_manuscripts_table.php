<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manuscripts', function (Blueprint $table) {
            $table->id();
            // Relasi ke tabel users dari Kelompok 1
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            // Relasi ke kontrak yang sudah divalidasi
            $table->foreignId('contract_id')->nullable()->constrained('contracts')->onDelete('set null');
            $table->string('title')->nullable();
            $table->enum('book_type', ['bukuajar', 'bukureferensi'])->nullable();
            $table->string('status')->default('initial_draft_requested'); // Status awal saat penulis diminta upload
            $table->date('deadline_draft')->nullable();
            $table->date('deadline_revision')->nullable(); 
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manuscripts');
    }
};