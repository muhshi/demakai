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
        // Only run if connection is NOT pgsql (assumed sqlite for dev)
        if (config('database.default') !== 'pgsql') {

            // KBLI 2025
            // KBLI 2025
            if (!Schema::hasTable('kbli2025s')) {
                Schema::create('kbli2025s', function (Blueprint $table) {
                    $table->id();
                    $table->string('kode')->nullable()->index();
                    $table->text('uraian')->nullable(); // Added as requested
                    $table->text('judul')->nullable();
                    $table->text('deskripsi')->nullable();
                    $table->json('contoh_lapangan')->nullable();
                    $table->string('level')->nullable();
                    $table->boolean('is_leaf')->default(false);
                    $table->string('sumber')->nullable();
                    $table->string('sektor')->nullable();
                    $table->string('mongo_id')->nullable();
                    $table->text('payload')->nullable();
                    $table->string('embed_hash')->nullable();
                    $table->timestamps(); // Added as requested
                });
            }

            // KBLI 2020 (Legacy - keeping simple)
            if (!Schema::hasTable('kbli2020s')) {
                Schema::create('kbli2020s', function (Blueprint $table) {
                    $table->id();
                    $table->string('kode')->nullable()->index();
                    $table->text('judul')->nullable();
                    $table->text('deskripsi')->nullable();
                    $table->json('contoh_lapangan')->nullable();
                    $table->string('level')->nullable();
                    $table->timestamps();
                });
            }

            // KBJI 2014
            if (!Schema::hasTable('kbji2014s')) {
                Schema::create('kbji2014s', function (Blueprint $table) {
                    $table->id();
                    $table->string('kode')->nullable()->index();
                    $table->text('uraian')->nullable(); // Added as requested
                    $table->text('judul')->nullable();
                    $table->text('deskripsi')->nullable();
                    $table->json('contoh_lapangan')->nullable();
                    $table->string('level')->nullable();
                    $table->timestamps(); // Added as requested
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (config('database.default') !== 'pgsql') {
            Schema::dropIfExists('kbli2025s');
            Schema::dropIfExists('kbli2020s');
            Schema::dropIfExists('kbji2014s');
        }
    }
};
