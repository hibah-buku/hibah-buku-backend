<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('publisher_decisions', function (Blueprint $table) {
            $table->id(); 
            $table->foreignId('manuscript_id')->constrained('manuscripts'); 
            $table->foreignId('publisher_id')->constrained('users'); 
            $table->enum('decision', ['approved', 'revised']); 
            $table->text('revision_notes')->nullable(); 
            $table->timestamp('decided_at')->nullable();
            $table->timestamps(); 
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('publisher_decisions');
    }
};