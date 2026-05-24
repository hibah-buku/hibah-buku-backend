<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('willingness_forms', function (Blueprint $table) {
            $table->id();

            // Main Author
            $table->string('main_author_name');
            $table->string('main_author_email');
            $table->string('main_author_institution');
            $table->string('main_author_phone');

            // Co-Author 1 (wajib)
            $table->string('co_author_1_name');
            $table->string('co_author_1_email');
            $table->string('co_author_1_institution');

            // Co-Author 2-4 (opsional)
            $table->string('co_author_2_name')->nullable();
            $table->string('co_author_2_email')->nullable();
            $table->string('co_author_2_institution')->nullable();

            $table->string('co_author_3_name')->nullable();
            $table->string('co_author_3_email')->nullable();
            $table->string('co_author_3_institution')->nullable();

            $table->string('co_author_4_name')->nullable();
            $table->string('co_author_4_email')->nullable();
            $table->string('co_author_4_institution')->nullable();

            // Data buku
            $table->string('book_title');
            $table->enum('book_type', ['bukuajar', 'bukureferensi']);
            $table->string('field_of_study');
            $table->text('book_abstract')->nullable();
            $table->string('target_audience')->nullable();

            // Meta data
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('admin_notes')->nullable();

            //rejected
            $table->string('rejection_reason')->nullable();
            $table->timestamp('rejected_at')->nullable();

            $table->index(['status', 'rejected_at', 'created_at']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
       Schema::dropIfExists('willingness_forms');
    }

};
