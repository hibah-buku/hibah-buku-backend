<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // e.g. 'account_created', 'contract_validated'
            $table->string('name');           // Human-readable name
            $table->string('subject');        // Email subject (supports :placeholder)
            $table->string('view');           // Blade view path, e.g. 'emails.account.created'
            $table->json('available_variables')->nullable(); // Docs: what vars are available
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_templates');
    }
};
