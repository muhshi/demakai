<?php

namespace App\Console\Commands;

use App\Models\KBJI2014;
use App\Models\KBLI2020;
use App\Models\KBLI2025;
use App\Models\PgKBJI2014;
use App\Models\PgKBLI2020;
use App\Models\PgKBLI2025;
use Illuminate\Console\Command;

class SyncMongoToPostgres extends Command
{
    protected $signature = 'sync:mongo-to-postgres {--model= : Focus on a specific model (KBJI2014, KBLI2020, KBLI2025)}';
    protected $description = 'Sync KBLI and KBJI data from MongoDB to Postgres';

    public function handle()
    {
        $this->info('Starting sync from MongoDB to Postgres...');
        $modelFilter = $this->option('model');

        if (!$modelFilter || strtolower($modelFilter) === 'kbli2020') {
            $this->syncKBLI();
        }

        if (!$modelFilter || strtolower($modelFilter) === 'kbli2025') {
            $this->syncKBLI2025();
        }

        if (!$modelFilter || strtolower($modelFilter) === 'kbji2014') {
            $this->syncKBJI();
        }

        $this->info('Sync completed successfully!');
    }

    protected function syncKBLI()
    {
        $this->info('Syncing KBLI2020...');
        $count = KBLI2020::count();
        $bar = $this->output->createProgressBar($count);
        $bar->start();

        KBLI2020::chunk(100, function ($items) use ($bar) {
            foreach ($items as $item) {
                PgKBLI2020::updateOrCreate(
                    ['mongo_id' => (string) $item->_id],
                    [
                        'sumber' => $item->sumber ?? null,
                        'kode' => $item->kode ?? $item->kode_4_digit_id ?? null, // Adjust field name if needed
                        'judul' => $item->judul ?? null,
                        'deskripsi' => $item->deskripsi ?? null,
                        'contoh_lapangan' => $item->contoh_lapangan ?? null,
                        'level' => $item->level ?? null,
                        'is_leaf' => $item->is_leaf ?? false,
                        'sektor' => $item->sektor ?? null,
                    ]
                );
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
    }

    protected function syncKBLI2025()
    {
        $this->info('Syncing KBLI2025...');
        $count = KBLI2025::count();
        $bar = $this->output->createProgressBar($count);
        $bar->start();

        KBLI2025::chunk(100, function ($items) use ($bar) {
            foreach ($items as $item) {
                PgKBLI2025::updateOrCreate(
                    ['mongo_id' => (string) $item->_id],
                    [
                        'sumber' => $item->sumber ?? 'KBLI 2025',
                        'kode' => $item->Kode ?? null,
                        'judul' => $item->Judul ?? null,
                        'deskripsi' => $item->Deskripsi ?? null,
                        'contoh_lapangan' => $item->contoh_lapangan ?? null,
                        'level' => $item->level ?? null,
                        'is_leaf' => $item->is_leaf ?? false,
                        'sektor' => $item->Kategori ?? null,
                    ]
                );
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
    }

    protected function syncKBJI()
    {
        $this->info('Syncing KBJI2014...');
        $count = KBJI2014::count();
        $bar = $this->output->createProgressBar($count);
        $bar->start();

        KBJI2014::chunk(100, function ($items) use ($bar) {
            foreach ($items as $item) {
                PgKBJI2014::updateOrCreate(
                    ['mongo_id' => (string) $item->_id],
                    [
                        'sumber' => $item->sumber ?? null,
                        'kode' => $item->kode ?? null,
                        'judul' => $item->judul ?? null,
                        'deskripsi' => $item->deskripsi ?? null,
                        'contoh_lapangan' => $item->contoh_lapangan ?? null,
                        'level' => $item->level ?? null,
                        'is_leaf' => $item->is_leaf ?? false,
                        'sektor' => $item->sektor ?? null,
                    ]
                );
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
    }
}
