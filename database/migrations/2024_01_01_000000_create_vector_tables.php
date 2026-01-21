<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    protected $connection = 'pgsql';

    public function up(): void
    {
        // Enable pgvector extension (only for pgsql)
        if (DB::connection('pgsql')->getDriverName() === 'pgsql') {
            DB::connection('pgsql')->statement('CREATE EXTENSION IF NOT EXISTS vector');
        }

        if (!Schema::connection('pgsql')->hasTable('kbli2020s')) {
            Schema::connection('pgsql')->create('kbli2020s', function (Blueprint $table) {
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

        if (!Schema::connection('pgsql')->hasTable('kbji2014s')) {
            Schema::connection('pgsql')->create('kbji2014s', function (Blueprint $table) {
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
        Schema::connection('pgsql')->dropIfExists('kbli2020s');
        Schema::connection('pgsql')->dropIfExists('kbji2014s');
    }
};
