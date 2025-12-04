<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TestPostgresConnection extends Command
{
    protected $signature = 'test:postgres-connection';
    protected $description = 'Test connection to Postgres database and check for required tables and extensions';

    public function handle()
    {
        $this->info('Testing Postgres Connection...');

        try {
            // Test Connection
            DB::connection('pgsql')->getPdo();
            $this->info('✅ Connection Successful!');

            // Check Extension
            $extensions = DB::connection('pgsql')->select("SELECT * FROM pg_extension WHERE extname = 'vector'");
            if (count($extensions) > 0) {
                $this->info('✅ pgvector extension is enabled.');
            } else {
                $this->warn('⚠️ pgvector extension is NOT enabled. You may need to run: CREATE EXTENSION vector;');
            }

            // Check Tables
            $tables = ['kbli2020s', 'kbji2014s'];
            foreach ($tables as $table) {
                if (Schema::connection('pgsql')->hasTable($table)) {
                    $count = DB::connection('pgsql')->table($table)->count();
                    $this->info("✅ Table '{$table}' exists with {$count} records.");
                } else {
                    $this->error("❌ Table '{$table}' does NOT exist.");
                }
            }

        } catch (\Exception $e) {
            $this->error('❌ Connection Failed: ' . $e->getMessage());
        }
    }
}
