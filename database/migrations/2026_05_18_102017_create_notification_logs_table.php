<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id(); 
            $table->foreignId('template_id')->constrained('notification_templates'); 
            $table->foreignId('recipient_id')->constrained('users');
            $table->string('recipient_email', 255);
            $table->string('event_name', 100); 
            $table->json('payload')->nullable(); 
            $table->enum('status', ['sent', 'failed']);
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};