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
            $table->foreignId('notification_template_id')->nullable()->constrained()->nullOnDelete();
            $table->string('template_code');       // Snapshot of template code at send time
            $table->string('recipient_email');
            $table->string('recipient_name')->nullable();
            $table->nullableMorphs('notifiable');  // polymorphic: User, Manuscript, etc.
            $table->string('subject');
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            $table->json('payload')->nullable();   // Variables passed to the template
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['recipient_email', 'template_code']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};
