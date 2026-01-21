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
        Schema::connection('sqlite')->create('field_example_submissions', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // 'KBLI 2025', 'KBLI 2020', 'KBJI 2014'
            $table->string('kode');
            $table->text('content');
            $table->string('status')->default('pending'); // 'pending', 'approved', 'rejected'
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('sqlite')->dropIfExists('field_example_submissions');
    }
};
