<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\PgKBLI2025;
use App\Models\PgKBJI2014;
use Carbon\Carbon;

class SyncPostgresToSqliteSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Starting synchronization from PostgreSQL to SQLite...');

        $now = Carbon::now();

        // --- KBLI 2025 ---
        $this->command->info('Fetching KBLI 2025 data from Postgres...');
        // Explicitly use the model which is configured for pgsql
        $pgKbli = PgKBLI2025::all();

        $sqliteKbliData = [];
        foreach ($pgKbli as $item) {
            $uraian = "Judul: {$item->judul} - Deskripsi: {$item->deskripsi}";

            $sqliteKbliData[] = [
                'kode' => $item->kode,
                'judul' => $item->judul,
                'deskripsi' => $item->deskripsi,
                'uraian' => $uraian,
                'contoh_lapangan' => is_array($item->contoh_lapangan) ? json_encode($item->contoh_lapangan) : $item->contoh_lapangan,
                'level' => $item->level,
                'sumber' => $item->sumber ?? 'KBLI 2025',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        $this->command->info('Truncating SQLite table kbli2025s...');
        DB::connection('sqlite')->table('kbli2025s')->truncate();

        $this->command->info("Inserting " . count($sqliteKbliData) . " records into SQLite kbli2025s...");
        foreach (array_chunk($sqliteKbliData, 500) as $chunk) {
            DB::connection('sqlite')->table('kbli2025s')->insert($chunk);
        }

        // --- KBJI 2014 ---
        $this->command->info('Fetching KBJI 2014 data from Postgres...');
        $pgKbji = PgKBJI2014::all();

        $sqliteKbjiData = [];
        foreach ($pgKbji as $item) {
            $uraian = "Judul: {$item->judul} - Deskripsi: {$item->deskripsi}";

            $sqliteKbjiData[] = [
                'kode' => $item->kode,
                'judul' => $item->judul,
                'deskripsi' => $item->deskripsi,
                'uraian' => $uraian,
                'contoh_lapangan' => is_array($item->contoh_lapangan) ? json_encode($item->contoh_lapangan) : $item->contoh_lapangan,
                'level' => $item->level,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        $this->command->info('Truncating SQLite table kbji2014s...');
        DB::connection('sqlite')->table('kbji2014s')->truncate();

        $this->command->info("Inserting " . count($sqliteKbjiData) . " records into SQLite kbji2014s...");
        foreach (array_chunk($sqliteKbjiData, 500) as $chunk) {
            DB::connection('sqlite')->table('kbji2014s')->insert($chunk);
        }

        $this->command->info('Synchronization completed successfully!');
    }
}
