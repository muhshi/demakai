<?php

$tikaJar = __DIR__ . '/documents/tika-app.jar';
$documentsDir = __DIR__ . '/documents';
$dataDir = __DIR__ . '/database/data';

if (!file_exists($dataDir)) {
    mkdir($dataDir, 0777, true);
}

function extractText($pdfPath, $txtPath, $tikaJar)
{
    if (!file_exists($pdfPath)) {
        echo "File not found: $pdfPath\n";
        return false;
    }

    if (file_exists($txtPath)) {
        echo "Text file already exists, skipping extraction: $txtPath\n";
        return true;
    }

    echo "Extracting text from $pdfPath...\n";
    $command = "java -jar \"$tikaJar\" -t \"$pdfPath\" > \"$txtPath\"";
    exec($command, $output, $returnVar);

    if ($returnVar !== 0) {
        echo "Error extracting text from $pdfPath. Return code: $returnVar\n";
        return false;
    }

    echo "Extraction complete: $txtPath\n";
    return true;
}

function parseKBLI($txtPath, $csvPath)
{
    echo "Parsing KBLI from $txtPath...\n";
    $handle = fopen($txtPath, "r");
    $csv = fopen($csvPath, "w");

    if (!$handle || !$csv) {
        echo "Error opening files for KBLI parsing.\n";
        return;
    }

    // Header
    fputcsv($csv, ['kode', 'uraian']);

    $buffer = "";
    $capture = false;

    while (($line = fgets($handle)) !== false) {
        $line = trim($line);
        if (empty($line))
            continue;

        // KBLI 2025 pattern: 
        // 5 digit code usually looks like "01111" followed by text
        // But in PDF text extraction, it might be split.
        // We look for lines starting with digit codes.

        // Match code at start of line
        if (preg_match('/^(\d{1,5})\s+(.*)$/', $line, $matches)) {
            $code = $matches[1];
            $text = $matches[2];

            // Filter out obviously wrong codes (like page numbers or single digits if not expected as categories)
            // KBLI has 2, 3, 4, 5 digit codes. 
            // We'll proceed with all found digit start lines for now.
            fputcsv($csv, [$code, $text]);
        }
    }

    fclose($handle);
    fclose($csv);
    echo "KBLI CSV saved to $csvPath\n";
}

function parseKBJI($txtPath, $csvPath)
{
    echo "Parsing KBJI from $txtPath...\n";
    $handle = fopen($txtPath, "r");
    $csv = fopen($csvPath, "w");

    if (!$handle || !$csv) {
        echo "Error opening files for KBJI parsing.\n";
        return;
    }

    fputcsv($csv, ['kode', 'uraian']);

    while (($line = fgets($handle)) !== false) {
        $line = trim($line);
        if (empty($line))
            continue;

        // KBJI codes: 
        // Major group: 1 digit
        // Sub-major group: 2 digits
        // Minor group: 3 digits
        // Unit group: 4 digits

        // Identify lines starting with digits
        if (preg_match('/^(\d{1,4})\s+(.*)$/', $line, $matches)) {
            $code = $matches[1];
            $text = $matches[2];

            // Basic filtering
            if (strlen($code) <= 4) {
                fputcsv($csv, [$code, $text]);
            }
        }
    }

    fclose($handle);
    fclose($csv);
    echo "KBJI CSV saved to $csvPath\n";
}


// 1. KBLI 2025
$kbliPdf = "$documentsDir/KBLI2025.pdf";
$kbliTxt = "$documentsDir/KBLI2025.txt";
$kbliCsv = "$dataDir/KBLI2025.csv";

if (extractText($kbliPdf, $kbliTxt, $tikaJar)) {
    parseKBLI($kbliTxt, $kbliCsv);
}

// 2. KBJI 2014
$kbjiPdf = "$documentsDir/KBJI2014.pdf";
$kbjiTxt = "$documentsDir/KBJI2014.txt";
$kbjiCsv = "$dataDir/KBJI2014.csv";

if (extractText($kbjiPdf, $kbjiTxt, $tikaJar)) {
    parseKBJI($kbjiTxt, $kbjiCsv);
}

echo "Done.\n";
