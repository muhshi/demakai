<?php

namespace Tests\Feature;

use Tests\TestCase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\File;

class ResolveKbliSe2026CommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $outputDir = storage_path('app/se2026_resolved');
        if (File::isDirectory($outputDir)) {
            File::cleanDirectory($outputDir);
        }
    }

    public function test_resolver_generates_enhanced_excel_with_inserted_title_and_recommendation_columns(): void
    {
        $this->artisan('kbli:resolve-se2026', ['--sample' => 5])
            ->assertExitCode(0);

        $outputDir = storage_path('app/se2026_resolved');
        $files = glob($outputDir . '/*.xlsx');
        $this->assertNotEmpty($files, "Exported resolved excel file must exist in {$outputDir}");

        $spreadsheet = IOFactory::load($files[0]);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, false, false, false);

        $this->assertGreaterThanOrEqual(2, count($rows), "Workbook must have header and at least one data row");
        $headers = $rows[0];

        // Column L (index 11) is kbli_saat_ini
        $this->assertEquals('kbli_saat_ini', $headers[11] ?? null);

        // Column M (index 12) must be the newly inserted judul_kbli_saat_ini
        $this->assertEquals('judul_kbli_saat_ini', $headers[12] ?? null);

        // Column N (index 13) should be kbli_rekomendasi_genai
        $this->assertEquals('kbli_rekomendasi_genai', $headers[13] ?? null);

        // Check recommendation columns at the end
        $this->assertContains('rekomendasi_1', $headers);
        $this->assertContains('confidence_1', $headers);
        $this->assertContains('rekomendasi_2', $headers);
        $this->assertContains('confidence_2', $headers);
        $this->assertContains('rekomendasi_3', $headers);
        $this->assertContains('confidence_3', $headers);
        $this->assertContains('alasan_rekomendasi', $headers);

        // Sample limit test: with --sample=5, data rows must be 5 (total 6 rows including header)
        $this->assertCount(6, $rows, "Total rows with header must match sample size + 1");

        // First data row checks
        $firstDataRow = $rows[1];
        $judulSaatIni = $firstDataRow[12];
        $this->assertNotEmpty($judulSaatIni, "Column M (judul_kbli_saat_ini) must not be empty");

        // Verify recommendation 1 is populated
        $rek1ColIdx = array_search('rekomendasi_1', $headers, true);
        $this->assertNotFalse($rek1ColIdx);
        $this->assertNotEmpty($firstDataRow[$rek1ColIdx], "rekomendasi_1 must not be empty");
    }

    public function test_resolver_with_specific_file_and_custom_export_name(): void
    {
        $customName = 'custom_export_test.xlsx';
        $this->artisan('kbli:resolve-se2026', [
            '--sample' => 2,
            '--file' => 'sqllab_kbli_rekomendasi_20261020T095247.xlsx',
            '--export' => $customName,
        ])->assertExitCode(0);

        $targetPath = storage_path('app/se2026_resolved/' . $customName);
        $this->assertFileExists($targetPath);

        $spreadsheet = IOFactory::load($targetPath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, false, false, false);

        // 1 header + 2 sample rows = 3 rows
        $this->assertCount(3, $rows);
        $this->assertEquals('judul_kbli_saat_ini', $rows[0][12] ?? null);
    }
}
