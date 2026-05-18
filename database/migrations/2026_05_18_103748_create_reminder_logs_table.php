<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reminder_logs', function (Blueprint $table) {
            $table->id(); 
            $table->foreignId('deadline_id')->constrained('deadlines');
            $table->foreignId('user_id')->constrained('users'); 
            $table->tinyInteger('days_before');
            $table->timestamp('sent_at'); 
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reminder_logs');
    }
};