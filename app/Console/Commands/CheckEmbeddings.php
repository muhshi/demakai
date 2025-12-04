<?php

namespace App\Console\Commands;

use App\Models\PgKBJI2014;
use App\Models\PgKBLI2020;
use Illuminate\Console\Command;

class CheckEmbeddings extends Command
{
    protected $signature = 'embeddings:check';
    protected $description = 'Check how many records have embeddings';

    public function handle()
    {
        $this->checkModel(PgKBLI2020::class, 'KBLI2020');
        $this->checkModel(PgKBJI2014::class, 'KBJI2014');
    }

    protected function checkModel($model, $name)
    {
        $total = $model::count();
        $filled = $model::whereNotNull('embedding')->count();
        $empty = $total - $filled;

        $this->info("=== {$name} ===");
        $this->info("Total Records: {$total}");
        $this->info("With Embeddings: {$filled}");
        $this->info("Without Embeddings: {$empty}");
        $this->newLine();
    }
}
