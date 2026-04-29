<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ERD-only migration: KBLI 2020 table without vector/pgvector columns.
 */
return new class extends Migration
{
    // Run on 'pgsql' connection (overridden to SQLite by laravel-erd config)
    protected $connection = 'pgsql';

    public function up(): void
    {
        Schema::create('kbli2020s', function (Blueprint $table) {
            $table->id();
            $table->string('sumber')->nullable();
            $table->string('kode')->nullable()->index();
            $table->text('judul')->nullable();
            $table->text('deskripsi')->nullable();
            $table->json('contoh_lapangan')->nullable();
            $table->string('level')->nullable();
            $table->boolean('is_leaf')->default(false);
            $table->string('sektor')->nullable();
            $table->string('mongo_id')->nullable();
            $table->text('payload')->nullable();
            // vector column omitted (not supported in SQLite)
            $table->string('embed_hash')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kbli2020s');
    }
};
