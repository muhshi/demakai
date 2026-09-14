<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_feedbacks', function (Blueprint $table) {
            $table->id();
            $table->string('category')->default('saran_fitur'); // 'saran_fitur', 'koreksi_hasil', 'kendala_bug', 'umum'
            $table->text('message');
            $table->string('sender_name')->nullable();
            $table->string('sender_contact')->nullable(); // email / no hp / wa
            $table->string('search_query')->nullable(); // kueri pencarian yang sedang dicari saat memberi feedback
            $table->string('status')->default('baru'); // 'baru', 'ditinjau', 'diterapkan', 'diarsipkan'
            $table->text('admin_notes')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('category');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_feedbacks');
    }
};
