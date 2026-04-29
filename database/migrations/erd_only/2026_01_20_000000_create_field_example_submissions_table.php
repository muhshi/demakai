<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ERD-only migration: field_example_submissions table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('field_example_submissions', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // 'KBLI 2025', 'KBLI 2020', 'KBJI 2014'
            $table->string('kode');
            $table->text('content');
            $table->string('status')->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('field_example_submissions');
    }
};
