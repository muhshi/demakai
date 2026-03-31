<?php

namespace App\Console\Commands;

use App\Services\TikaService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PrepareClassificationData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data:prepare-classification';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Extract KBLI 2025 and KBJI 2014 data from PDFs using Tika and save as CSV';

    protected TikaService $tika;

    public function __construct(TikaService $tika)
    {
        parent::__construct();
        $this->tika = $tika;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting data preparation...');

        // Ensure directories exist
        if (!File::exists(base_path('documents'))) {
            File::makeDirectory(base_path('documents'));
        }
        if (!File::exists(database_path('data'))) {
            File::makeDirectory(database_path('data'));
        }

        // Process KBLI 2025
        $this->processKBLI();

        // Process KBJI 2014
        $this->processKBJI();

        $this->info('Data preparation completed!');
    }

    private function processKBLI()
    {
        $pdfPath = base_path('documents/KBLI2025.pdf');
        $txtPath = base_path('documents/KBLI2025.txt');
        $csvPath = database_path('data/kbli2025.csv');

        if (!File::exists($pdfPath)) {
            $this->warn("KBLI 2025 PDF not found at: $pdfPath");
            return;
        }

        $this->info('Extracting text from KBLI 2025 PDF...');
        try {
            // Check if TXT already exists to avoid re-parsing
            if (!File::exists($txtPath)) {
                $text = $this->tika->parseFile($pdfPath);
                File::put($txtPath, $text);
                $this->info("Saved raw text to: $txtPath");
            } else {
                $text = File::get($txtPath);
                $this->info("Using existing raw text from: $txtPath");
            }

            $this->info('Parsing KBLI data...');
            $lines = explode("\n", $text);
            $uniqueData = [];

            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line))
                    continue;

                // Regex for KBLI: 5 digits followed by text (e.g. "01111 Pertanian...")
                if (preg_match('/^(\d{5})\s+(.*)$/', $line, $matches)) {
                    $kode = $matches[1];
                    $uraian = trim($matches[2]);

                    if (!isset($uniqueData[$kode])) {
                        $uniqueData[$kode] = [$kode, $uraian];
                    }
                }
            }

            // CSV Header
            $data = [['kode', 'uraian']];
            foreach ($uniqueData as $row) {
                $data[] = $row;
            }

            $this->writeCsv($csvPath, $data);
            $this->info("KBLI 2025 data saved to: $csvPath. Total items: " . count($uniqueData));

        } catch (\Exception $e) {
            $this->error("Error processing KBLI: " . $e->getMessage());
        }
    }

    private function processKBJI()
    {
        $pdfPath = base_path('documents/KBJI2014.pdf');
        $txtPath = base_path('documents/KBJI2014.txt');
        $csvPath = database_path('data/kbji2014.csv');

        if (!File::exists($pdfPath)) {
            $this->warn("KBJI 2014 PDF not found at: $pdfPath");
            return;
        }

        $this->info('Extracting text from KBJI 2014 PDF...');
        try {
            if (!File::exists($txtPath)) {
                $text = $this->tika->parseFile($pdfPath);
                File::put($txtPath, $text);
                $this->info("Saved raw text to: $txtPath");
            } else {
                $text = File::get($txtPath);
                $this->info("Using existing raw text from: $txtPath");
            }

            $this->info('Parsing KBJI data...');
            $lines = explode("\n", $text);
            $uniqueData = [];

            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line))
                    continue;

                // Regex for KBJI: 4 digits followed by text (e.g. "1112 Pejabat Senior...")
                if (preg_match('/^(\d{4})\s+(.*)$/', $line, $matches)) {
                    $kode = $matches[1];
                    $uraian = trim($matches[2]);

                    if (!isset($uniqueData[$kode])) {
                        $uniqueData[$kode] = [$kode, $uraian];
                    }
                }
            }

            // CSV Header
            $data = [['kode', 'uraian']];
            foreach ($uniqueData as $row) {
                $data[] = $row;
            }

            $this->writeCsv($csvPath, $data);
            $this->info("KBJI 2014 data saved to: $csvPath. Total items: " . count($uniqueData));

        } catch (\Exception $e) {
            $this->error("Error processing KBJI: " . $e->getMessage());
        }
    }

    private function writeCsv($path, $data)
    {
        $fp = fopen($path, 'w');
        foreach ($data as $fields) {
            fputcsv($fp, $fields);
        }
        fclose($fp);
    }
}
