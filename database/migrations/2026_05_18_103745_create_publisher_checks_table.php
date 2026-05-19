<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('publisher_checks', function (Blueprint $table) {
            $table->id(); 
            $table->foreignId('manuscript_id')->constrained('manuscripts'); 
            $table->foreignId('publisher_id')->constrained('users'); 
            $table->text('check_notes')->nullable(); 
            $table->boolean('cover_design_ok'); 
            $table->boolean('page_count_ok'); 
            $table->boolean('admin_docs_ok'); 
            $table->timestamps(); 
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('publisher_checks');
    }
};