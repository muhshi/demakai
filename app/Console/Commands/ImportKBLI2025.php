<?php

namespace App\Console\Commands;

use App\Imports\KBLI2025Import;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;

class ImportKBLI2025 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:kbli2025 {file : Path to the excel file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import KBLI 2025 data from Excel to MongoDB';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filePath = $this->argument('file');

        if (!file_exists($filePath)) {
            $this->error("File semi-path '{$filePath}' not found.");
            return Command::FAILURE;
        }

        $this->info("Importing KBLI 2025 from {$filePath}...");

        try {
            Excel::import(new KBLI2025Import, $filePath);
            $this->info('Import completed successfully!');
        } catch (\Exception $e) {
            $this->error('Import failed: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
