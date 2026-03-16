<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    // protected $connection = 'pgsql';

    public function up(): void
    {
        if (config('database.default') === 'pgsql') {
            // Enable pgvector extension (only for pgsql)
            DB::statement('CREATE EXTENSION IF NOT EXISTS vector');
        }

        if (config('database.default') === 'pgsql' && !Schema::hasTable('kbli2020s')) {
            Schema::create('kbli2020s', function (Blueprint $table) {
                $table->id();
                $table->string('sumber')->nullable();
                $table->string('kode')->nullable()->index();
                $table->text('judul')->nullable();
                $table->text('deskripsi')->nullable();
                $table->jsonb('contoh_lapangan')->nullable();
                $table->string('level')->nullable();
                $table->boolean('is_leaf')->default(false);
                $table->string('sektor')->nullable();
                $table->string('mongo_id')->nullable();
                $table->text('payload')->nullable();
                $table->vector('embedding', 768)->nullable(); // Gemini text-embedding-004 dimension
                $table->string('embed_hash')->nullable();
            });
        }

        if (config('database.default') === 'pgsql' && !Schema::hasTable('kbji2014s')) {
            Schema::create('kbji2014s', function (Blueprint $table) {
                $table->id();
                $table->string('sumber')->nullable();
                $table->string('kode')->nullable()->index();
                $table->text('judul')->nullable();
                $table->text('deskripsi')->nullable();
                $table->jsonb('contoh_lapangan')->nullable();
                $table->string('level')->nullable();
                $table->boolean('is_leaf')->default(false);
                $table->string('sektor')->nullable();
                $table->string('mongo_id')->nullable();
                $table->text('payload')->nullable();
                $table->vector('embedding', 768)->nullable(); // Gemini text-embedding-004 dimension
                $table->string('embed_hash')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (config('database.default') === 'pgsql') {
            Schema::dropIfExists('kbli2020s');
            Schema::dropIfExists('kbji2014s');
        }
    }
};
