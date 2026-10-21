<?php

namespace App\Console\Commands;

use App\Models\PgKBLI2020;
use App\Models\PgKBLI2025;
use App\Services\SearchService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;

class RowFilter implements IReadFilter
{
    private ?int $maxRow;

    public function __construct(?int $maxRow = null)
    {
        $this->maxRow = $maxRow;
    }

    public function readCell($columnAddress, $row, $worksheetName = ''): bool
    {
        if ($this->maxRow === null) {
            return true;
        }
        return $row <= $this->maxRow;
    }
}

class ResolveKbliSe2026Command extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kbli:resolve-se2026 
                            {--sample= : Process only N sample rows} 
                            {--file= : Specific file name in KBLI SE2026/} 
                            {--export= : Custom output filename}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Resolve anomalies in SE2026 datasets with KBLI title lookup and hybrid search recommendations.';

    /**
     * Execute the console command.
     */
    public function handle(SearchService $searchService): int
    {
        ini_set('memory_limit', '-1');

        $sample = $this->option('sample') ? (int) $this->option('sample') : null;
        $fileOption = $this->option('file');
        $exportOption = $this->option('export');

        $inputDir = base_path('KBLI SE2026');
        $outputDir = storage_path('app/se2026_resolved');
        File::ensureDirectoryExists($outputDir);

        // 1. Determine input files
        $filesToProcess = [];
        if ($fileOption) {
            $candidatePath = file_exists($fileOption) ? $fileOption : $inputDir . DIRECTORY_SEPARATOR . $fileOption;
            if (!file_exists($candidatePath)) {
                $this->error("Specified file not found: {$candidatePath}");
                return 1;
            }
            $filesToProcess[] = $candidatePath;
        } else {
            $filesToProcess = glob($inputDir . '/*.xlsx');
            if (empty($filesToProcess)) {
                $this->error("No Excel (.xlsx) files found in {$inputDir}");
                return 1;
            }
        }

        $this->info("=== SE2026 KBLI Anomaly Batch Resolver ===");
        if ($sample) {
            $this->comment("Running in SAMPLE mode: processing first {$sample} rows per file.");
        }

        // 2. Pre-load dictionary of KBLI 2025 and 2020 for O(1) title lookups
        $this->line("Pre-loading KBLI 2025 and 2020 titles...");
        $kbli2025 = PgKBLI2025::pluck('judul', 'kode')->toArray();
        $kbli2020 = PgKBLI2020::pluck('judul', 'kode')->toArray();
        $this->line("Loaded " . count($kbli2025) . " KBLI 2025 records and " . count($kbli2020) . " KBLI 2020 records.");

        // 3. In-memory search cache to speed up repeated queries across 14,000+ rows
        $searchMemoryCache = [];
        $searchCached = function (string $query, int $limit = 3, ?string $cat = null) use ($searchService, &$searchMemoryCache) {
            $key = $query . '|' . $limit . '|' . ($cat ?? '');
            if (isset($searchMemoryCache[$key])) {
                return $searchMemoryCache[$key];
            }
            $res = $searchService->search($query, $limit, 'KBLI', false, $cat);
            $searchMemoryCache[$key] = $res;
            return $res;
        };

        // 4. Process each file
        foreach ($filesToProcess as $inputFile) {
            $this->info("\nProcessing file: " . basename($inputFile));
            $startTime = microtime(true);

            $reader = new XlsxReader();
            $reader->setReadDataOnly(true);
            if ($sample !== null && $sample > 0) {
                // Header is row 1, sample rows are 2 .. sample + 1
                $reader->setReadFilter(new RowFilter($sample + 1));
            }
            $sourceSpreadsheet = $reader->load($inputFile);
            $sourceSheet = $sourceSpreadsheet->getActiveSheet();
            $rawRows = $sourceSheet->toArray(null, false, false, false);

            if (empty($rawRows)) {
                $this->warn("File is empty: " . basename($inputFile));
                continue;
            }

            $originalHeaders = $rawRows[0];
            $dataRows = array_slice($rawRows, 1);
            if ($sample !== null && $sample > 0) {
                $dataRows = array_slice($dataRows, 0, $sample);
            }

            $totalDataRows = count($dataRows);
            $this->line("Rows to process: {$totalDataRows}");

            // Prepare output headers: insert judul_kbli_saat_ini right after Col L (index 11)
            $outHeaders = [];
            for ($c = 0; $c <= 11; $c++) {
                $outHeaders[] = $originalHeaders[$c] ?? "col_{$c}";
            }
            $outHeaders[] = 'judul_kbli_saat_ini'; // Col M (index 12)
            for ($c = 12; $c < count($originalHeaders); $c++) {
                $outHeaders[] = $originalHeaders[$c] ?? "col_{$c}";
            }
            // Append recommendation columns at the end
            $outHeaders[] = 'rekomendasi_1';
            $outHeaders[] = 'confidence_1';
            $outHeaders[] = 'rekomendasi_2';
            $outHeaders[] = 'confidence_2';
            $outHeaders[] = 'rekomendasi_3';
            $outHeaders[] = 'confidence_3';
            $outHeaders[] = 'alasan_rekomendasi';

            $outRows = [$outHeaders];
            $countResolved = 0;
            $countAlreadyClassified = 0;

            $bar = $this->output->createProgressBar($totalDataRows);
            $bar->start();

            foreach ($dataRows as $row) {
                // Read input columns
                $namaUsaha = trim((string)($row[9] ?? ''));
                $uraianKegiatan = trim((string)($row[10] ?? ''));
                $rawKbliSaatIni = trim((string)($row[11] ?? ''));
                $genaiKbli = trim((string)($row[12] ?? ''));
                $rekomendasiTindakan = trim((string)($row[16] ?? ''));

                // Pad 4-digit KBLI code to 5-digit (e.g., 1499 -> 01499)
                $paddedKbliSaatIni = (ctype_digit($rawKbliSaatIni) && strlen($rawKbliSaatIni) > 0 && strlen($rawKbliSaatIni) < 5)
                    ? str_pad($rawKbliSaatIni, 5, '0', STR_PAD_LEFT)
                    : $rawKbliSaatIni;

                // Lookup title for kbli_saat_ini
                $judulSaatIni = $this->lookupTitle($paddedKbliSaatIni, $kbli2025, $kbli2020);

                $rec1 = ''; $conf1 = '';
                $rec2 = ''; $conf2 = '';
                $rec3 = ''; $conf3 = '';
                $alasanRekomendasi = '';

                $categoryHint = $this->parseCategoryHint($rekomendasiTindakan);

                // If GenAI is unclassified (999999 or empty)
                if ($genaiKbli === '999999' || $genaiKbli === '' || $genaiKbli === '0') {
                    $countResolved++;

                    // Clean corporate prefixes like PT/CV, person name in parenthesis, and extraneous punctuation
                    $cleanedNama = preg_replace('/\b(PT|CV|UD|PD|PO|FA)\b\.?/i', '', $namaUsaha);
                    $cleanedNama = preg_replace('/\([^)]*\)/', '', $cleanedNama);
                    $cleanedNama = trim(preg_replace('/[(),.]/', ' ', $cleanedNama));
                    $cleanedNama = preg_replace('/\bbudidaya\b/i', 'budi daya', $cleanedNama);
                    $cleanedNama = preg_replace('/\bpembudiyaan\b/i', 'budi daya', $cleanedNama);

                    $cleanedUraian = preg_replace('/\bbudidaya\b/i', 'budi daya', $uraianKegiatan);
                    $cleanedUraian = preg_replace('/\bpembudiyaan\b/i', 'budi daya', $cleanedUraian);

                    $searchResults = [];
                    $seenCodes = [];

                    // 1. Coba pencarian dengan nama usaha yang bersih jika ringkas & informatif
                    $wordCountNama = count(array_filter(explode(' ', $cleanedNama)));
                    if (!empty($cleanedNama) && $wordCountNama >= 1 && $wordCountNama <= 5) {
                        $resNama = $searchCached($cleanedNama, 3, null);
                        foreach ($resNama as $r) {
                            $k = is_array($r) ? ($r['kode'] ?? '') : ($r->kode ?? '');
                            if ($k && !isset($seenCodes[$k])) {
                                $searchResults[] = $r;
                                $seenCodes[$k] = true;
                            }
                        }
                    }

                    // 2. Tambahkan pencarian gabungan (nama usaha + uraian) dengan batasan sektor jika ada
                    $combinedQuery = trim($cleanedNama . ' ' . $cleanedUraian);
                    if (empty($combinedQuery)) {
                        $combinedQuery = $cleanedUraian ?: $cleanedNama;
                    }

                    $resCombined = $searchCached($combinedQuery, 3, $categoryHint);
                    foreach ($resCombined as $r) {
                        $k = is_array($r) ? ($r['kode'] ?? '') : ($r->kode ?? '');
                        if ($k && !isset($seenCodes[$k])) {
                            $searchResults[] = $r;
                            $seenCodes[$k] = true;
                        }
                    }

                    // 3. Fallback unconstrained jika masih kosong
                    if (empty($searchResults)) {
                        $resFallback = $searchCached($combinedQuery, 3, null);
                        foreach ($resFallback as $r) {
                            $k = is_array($r) ? ($r['kode'] ?? '') : ($r->kode ?? '');
                            if ($k && !isset($seenCodes[$k])) {
                                $searchResults[] = $r;
                                $seenCodes[$k] = true;
                            }
                        }
                    }

                    if (!empty($searchResults[0])) {
                        $r = $searchResults[0];
                        $k = is_array($r) ? ($r['kode'] ?? '') : ($r->kode ?? '');
                        $j = is_array($r) ? ($r['judul'] ?? '') : ($r->judul ?? '');
                        $rec1 = $k . ' - ' . $j;
                        $conf1 = $this->formatConfidence($r);
                    }
                    if (!empty($searchResults[1])) {
                        $r = $searchResults[1];
                        $k = is_array($r) ? ($r['kode'] ?? '') : ($r->kode ?? '');
                        $j = is_array($r) ? ($r['judul'] ?? '') : ($r->judul ?? '');
                        $rec2 = $k . ' - ' . $j;
                        $conf2 = $this->formatConfidence($r);
                    }
                    if (!empty($searchResults[2])) {
                        $r = $searchResults[2];
                        $k = is_array($r) ? ($r['kode'] ?? '') : ($r->kode ?? '');
                        $j = is_array($r) ? ($r['judul'] ?? '') : ($r->judul ?? '');
                        $rec3 = $k . ' - ' . $j;
                        $conf3 = $this->formatConfidence($r);
                    }

                    $alasanRekomendasi = !empty($categoryHint)
                        ? "Rekomendasi hybrid search berdasarkan nama usaha & uraian kegiatan dengan batasan sektor rule [{$categoryHint}]"
                        : "Rekomendasi hybrid search berdasarkan nama usaha & uraian kegiatan";
                } else {
                    $countAlreadyClassified++;

                    $paddedGenAi = (ctype_digit($genaiKbli) && strlen($genaiKbli) > 0 && strlen($genaiKbli) < 5)
                        ? str_pad($genaiKbli, 5, '0', STR_PAD_LEFT)
                        : $genaiKbli;
                    $genAiTitle = $this->lookupTitle($paddedGenAi, $kbli2025, $kbli2020);

                    $rec1 = $paddedGenAi . ($genAiTitle ? ' - ' . $genAiTitle : '');
                    $conf1 = 'Sudah Tervalidasi';
                    $alasanRekomendasi = 'Sudah terklasifikasi oleh GenAI sebelumnya';
                }

                // Assemble row: A..L, inserted M (judulSaatIni), original N..S, new recommendation cols
                $outRow = [];
                for ($c = 0; $c < 11; $c++) {
                    $outRow[] = $row[$c] ?? '';
                }
                $outRow[] = $paddedKbliSaatIni; // Col L (index 11)
                $outRow[] = $judulSaatIni;      // Col M (index 12) - NEW

                for ($c = 12; $c < count($originalHeaders); $c++) {
                    $outRow[] = $row[$c] ?? '';
                }

                $outRow[] = $rec1;
                $outRow[] = $conf1;
                $outRow[] = $rec2;
                $outRow[] = $conf2;
                $outRow[] = $rec3;
                $outRow[] = $conf3;
                $outRow[] = $alasanRekomendasi;

                $outRows[] = $outRow;
                $bar->advance();
            }

            $bar->finish();
            $this->newLine();

            // Build output workbook
            $outputSpreadsheet = new Spreadsheet();
            $outSheet = $outputSpreadsheet->getActiveSheet();
            $outSheet->setTitle('Resolved_SE2026');
            $outSheet->fromArray($outRows, null, 'A1');

            // Format header row style
            $highestCol = $outSheet->getHighestColumn();
            $outSheet->getStyle("A1:{$highestCol}1")->getFont()->setBold(true);

            // Determine output filename
            if ($exportOption) {
                $outFilename = str_ends_with($exportOption, '.xlsx') ? $exportOption : $exportOption . '.xlsx';
            } else {
                $base = pathinfo($inputFile, PATHINFO_FILENAME);
                $outFilename = "resolved_{$base}_" . date('Ymd_His') . ".xlsx";
            }
            $targetPath = $outputDir . DIRECTORY_SEPARATOR . $outFilename;

            $writer = new XlsxWriter($outputSpreadsheet);
            $writer->save($targetPath);

            $duration = round(microtime(true) - $startTime, 2);
            $this->info("Saved resolved spreadsheet to: {$targetPath}");
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Total Rows Processed', $totalDataRows],
                    ['Anomalies Resolved (999999)', $countResolved],
                    ['Already Classified Rows', $countAlreadyClassified],
                    ['Time Elapsed', "{$duration} s"],
                    ['Output File', $outFilename],
                ]
            );
        }

        $this->info("Batch anomaly resolution completed successfully!");
        return 0;
    }

    /**
     * Look up KBLI title from 2025 and 2020 dictionaries.
     */
    protected function lookupTitle(string $code, array $kbli2025, array $kbli2020): string
    {
        $code = trim($code);
        if ($code === '') {
            return '';
        }

        $padded = ctype_digit($code) ? str_pad($code, 5, '0', STR_PAD_LEFT) : $code;
        if (isset($kbli2025[$padded])) {
            return $kbli2025[$padded];
        }
        if (isset($kbli2020[$padded])) {
            return $kbli2020[$padded];
        }

        $unpadded = ltrim($code, '0');
        if (isset($kbli2025[$unpadded])) {
            return $kbli2025[$unpadded];
        }
        if (isset($kbli2020[$unpadded])) {
            return $kbli2020[$unpadded];
        }

        if (isset($kbli2025[$code])) {
            return $kbli2025[$code];
        }
        if (isset($kbli2020[$code])) {
            return $kbli2020[$code];
        }

        return '';
    }

    /**
     * Parse category hint from recommendation action column (Col Q).
     */
    protected function parseCategoryHint(?string $text): ?string
    {
        if (empty($text)) {
            return null;
        }
        $t = trim($text);

        if (stripos($t, 'I Gol. 56') !== false || stripos($t, '56') !== false) {
            return '56';
        }
        if (stripos($t, 'G - perdagangan') !== false || stripos($t, 'perdagangan') !== false) {
            return 'G';
        }
        if (stripos($t, 'A - pertanian') !== false || stripos($t, 'pertanian') !== false) {
            return 'A';
        }
        if (stripos($t, 'Salah satu B-F') !== false || stripos($t, 'B-F') !== false) {
            return 'C';
        }
        if (stripos($t, 'B atau F') !== false) {
            return 'F';
        }
        if (stripos($t, 'L - real estat') !== false) {
            return 'L';
        }
        if (stripos($t, 'jasa H-V') !== false || stripos($t, 'H-V') !== false) {
            return 'S';
        }

        return null;
    }

    /**
     * Format confidence display string from search result.
     */
    protected function formatConfidence($item): string
    {
        if (is_array($item)) {
            if (isset($item['similarity'])) {
                return round((float)$item['similarity'] * 100, 1) . '%';
            }
            if (isset($item['distance'])) {
                $sim = max(0, 1.0 - (float)$item['distance']);
                return round($sim * 100, 1) . '%';
            }
            if (isset($item['score'])) {
                $s = (float)$item['score'];
                return $s <= 1.0 ? (round($s * 100, 1) . '%') : (round($s, 1) . ' pts');
            }
            if (isset($item['match_score'])) {
                return (int)$item['match_score'] . ' pts';
            }
        } elseif (is_object($item)) {
            if (isset($item->similarity)) {
                return round((float)$item->similarity * 100, 1) . '%';
            }
            if (isset($item->distance)) {
                $sim = max(0, 1.0 - (float)$item->distance);
                return round($sim * 100, 1) . '%';
            }
            if (isset($item->match_score)) {
                return (int)$item->match_score . ' pts';
            }
        }

        return 'N/A';
    }
}
