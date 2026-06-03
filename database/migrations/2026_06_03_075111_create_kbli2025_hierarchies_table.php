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
        Schema::create('kbli2025_hierarchies', function (Blueprint $table) {
            $table->id();
            $table->string('level'); // kategori, pokok, golongan, subgolongan
            $table->string('kode')->index();
            $table->text('judul');
            $table->text('deskripsi')->nullable();
            $table->string('parent_kode')->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kbli2025_hierarchies');
    }
};
