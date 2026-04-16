<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Target the 'pgsql' connection specifically since default is 'sqlite'
        $connection = DB::connection('pgsql');

        // Check if extension exists, if not try to create it here too just in case
        $connection->statement('CREATE EXTENSION IF NOT EXISTS vector');

        // Add embedding column
        // We use raw SQL to ensure it works even without package discovery
        $connection->statement('ALTER TABLE kbli2025s ADD COLUMN IF NOT EXISTS embedding vector(768)');
        $connection->statement('ALTER TABLE kbji2014s ADD COLUMN IF NOT EXISTS embedding vector(768)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $connection = DB::connection('pgsql');

        try {
            $connection->statement('ALTER TABLE kbli2025s DROP COLUMN IF EXISTS embedding');
            $connection->statement('ALTER TABLE kbji2014s DROP COLUMN IF EXISTS embedding');
        } catch (\Exception $e) {
            // Ignore if column doesn't exist
        }
    }
};
