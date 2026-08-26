<?php

namespace App\Console\Commands;

use App\Models\FieldExampleSubmission;
use App\Models\PgKBJI2014;
use App\Models\PgKBLI2025;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use PDO;

class ExportOfflineBundleCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kbli:export-bundle {--bundle-version= : Custom bundle version name (default: auto)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate and package pre-indexed SQLite database bundle (FTS5 + Vectors) for Flutter mobile offline search';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $version = $this->option('bundle-version') ?: date('Y.m.') . (int) date('d');
        $this->info("=================================================");
        $this->info("  PINTAR KBLI - Offline Bundle Generator v{$version}");
        $this->info("=================================================");

        $bundleDir = storage_path('app/bundles');
        if (!File::exists($bundleDir)) {
            File::makeDirectory($bundleDir, 0755, true);
        }

        $tempDbPath = "{$bundleDir}/kbli_offline_temp_" . time() . ".db";
        $dbPath = "{$bundleDir}/kbli_offline_latest.db";
        $gzPath = "{$bundleDir}/kbli_offline_latest.db.gz";
        $metaPath = "{$bundleDir}/bundle_meta.json";

        // Remove old temp db if exists
        if (file_exists($tempDbPath)) {
            @unlink($tempDbPath);
        }

        $this->info("1. Creating local SQLite database at: {$tempDbPath}");

        try {
            $pdo = new PDO("sqlite:{$tempDbPath}");
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Optimizing SQLite for bulk creation
            $pdo->exec("PRAGMA journal_mode = OFF;");
            $pdo->exec("PRAGMA synchronous = 0;");
            $pdo->exec("PRAGMA cache_size = 100000;");

            // 1. Create tables
            $this->info("2. Creating schemas & FTS5 full-text search indexes...");

            $pdo->exec("
                CREATE TABLE meta (
                    key TEXT PRIMARY KEY,
                    value TEXT
                );

                CREATE TABLE kbli2025 (
                    id INTEGER PRIMARY KEY,
                    kode TEXT,
                    judul TEXT,
                    deskripsi TEXT,
                    kategori TEXT,
                    contoh_lapangan TEXT,
                    embedding BLOB
                );

                CREATE INDEX idx_kbli_kode ON kbli2025(kode);

                CREATE TABLE kbji2014 (
                    id INTEGER PRIMARY KEY,
                    kode TEXT,
                    judul TEXT,
                    deskripsi TEXT,
                    contoh_lapangan TEXT,
                    embedding BLOB
                );

                CREATE INDEX idx_kbji_kode ON kbji2014(kode);

                CREATE VIRTUAL TABLE kbli_fts USING fts5(
                    kode,
                    judul,
                    deskripsi,
                    contoh_lapangan,
                    tokenize='unicode61 remove_diacritics 2'
                );

                CREATE VIRTUAL TABLE kbji_fts USING fts5(
                    kode,
                    judul,
                    deskripsi,
                    contoh_lapangan,
                    tokenize='unicode61 remove_diacritics 2'
                );
            ");

            // 2. Export KBLI 2025
            $this->info("3. Populating KBLI 2025 master records...");
            $kbliStmt = $pdo->prepare("
                INSERT INTO kbli2025 (id, kode, judul, deskripsi, kategori, contoh_lapangan, embedding)
                VALUES (:id, :kode, :judul, :deskripsi, :kategori, :contoh_lapangan, :embedding)
            ");
            $kbliFtsStmt = $pdo->prepare("
                INSERT INTO kbli_fts (rowid, kode, judul, deskripsi, contoh_lapangan)
                VALUES (:rowid, :kode, :judul, :deskripsi, :contoh_lapangan)
            ");

            $pdo->beginTransaction();
            $kbliCount = 0;

            PgKBLI2025::chunk(500, function ($records) use ($kbliStmt, $kbliFtsStmt, &$kbliCount) {
                foreach ($records as $item) {
                    $contohJson = is_array($item->contoh_lapangan)
                        ? json_encode($item->contoh_lapangan, JSON_UNESCAPED_UNICODE)
                        : ($item->contoh_lapangan ?? '[]');

                    $contohText = is_array($item->contoh_lapangan)
                        ? implode(' ', $item->contoh_lapangan)
                        : (string) $item->contoh_lapangan;

                    // Convert pgvector / Vector object / array embedding to binary float32 blob for mobile
                    $embeddingBlob = null;
                    if (!empty($item->embedding)) {
                        $rawEmb = null;
                        if (is_object($item->embedding) && method_exists($item->embedding, 'toArray')) {
                            $rawEmb = $item->embedding->toArray();
                        } elseif (is_string($item->embedding)) {
                            $rawEmb = json_decode($item->embedding, true);
                        } elseif (is_array($item->embedding)) {
                            $rawEmb = $item->embedding;
                        } elseif (is_object($item->embedding)) {
                            $rawEmb = (array) $item->embedding;
                        }

                        if (is_array($rawEmb) && count($rawEmb) > 0) {
                            $embeddingBlob = pack('f*', ...$rawEmb);
                        }
                    }

                    $kbliStmt->bindValue(':id', $item->id, PDO::PARAM_INT);
                    $kbliStmt->bindValue(':kode', (string) $item->kode, PDO::PARAM_STR);
                    $kbliStmt->bindValue(':judul', (string) $item->judul, PDO::PARAM_STR);
                    $kbliStmt->bindValue(':deskripsi', (string) ($item->deskripsi ?? ''), PDO::PARAM_STR);
                    $kbliStmt->bindValue(':kategori', (string) ($item->kategori ?? ''), PDO::PARAM_STR);
                    $kbliStmt->bindValue(':contoh_lapangan', $contohJson, PDO::PARAM_STR);
                    $kbliStmt->bindValue(':embedding', $embeddingBlob, PDO::PARAM_LOB);
                    $kbliStmt->execute();

                    $kbliFtsStmt->execute([
                        ':rowid' => $item->id,
                        ':kode' => (string) $item->kode,
                        ':judul' => (string) $item->judul,
                        ':deskripsi' => (string) ($item->deskripsi ?? ''),
                        ':contoh_lapangan' => $contohText,
                    ]);

                    $kbliCount++;
                }
            });
            $pdo->commit();
            $this->info("   -> Processed {$kbliCount} KBLI 2025 records.");

            // 3. Export KBJI 2014
            $this->info("4. Populating KBJI 2014 master records...");
            $kbjiStmt = $pdo->prepare("
                INSERT INTO kbji2014 (id, kode, judul, deskripsi, contoh_lapangan, embedding)
                VALUES (:id, :kode, :judul, :deskripsi, :contoh_lapangan, :embedding)
            ");
            $kbjiFtsStmt = $pdo->prepare("
                INSERT INTO kbji_fts (rowid, kode, judul, deskripsi, contoh_lapangan)
                VALUES (:rowid, :kode, :judul, :deskripsi, :contoh_lapangan)
            ");

            $pdo->beginTransaction();
            $kbjiCount = 0;

            PgKBJI2014::chunk(500, function ($records) use ($kbjiStmt, $kbjiFtsStmt, &$kbjiCount) {
                foreach ($records as $item) {
                    $contohJson = is_array($item->contoh_lapangan)
                        ? json_encode($item->contoh_lapangan, JSON_UNESCAPED_UNICODE)
                        : ($item->contoh_lapangan ?? '[]');

                    $contohText = is_array($item->contoh_lapangan)
                        ? implode(' ', $item->contoh_lapangan)
                        : (string) $item->contoh_lapangan;

                    $embeddingBlob = null;
                    if (!empty($item->embedding)) {
                        $rawEmb = null;
                        if (is_object($item->embedding) && method_exists($item->embedding, 'toArray')) {
                            $rawEmb = $item->embedding->toArray();
                        } elseif (is_string($item->embedding)) {
                            $rawEmb = json_decode($item->embedding, true);
                        } elseif (is_array($item->embedding)) {
                            $rawEmb = $item->embedding;
                        } elseif (is_object($item->embedding)) {
                            $rawEmb = (array) $item->embedding;
                        }

                        if (is_array($rawEmb) && count($rawEmb) > 0) {
                            $embeddingBlob = pack('f*', ...$rawEmb);
                        }
                    }

                    $kbjiStmt->bindValue(':id', $item->id, PDO::PARAM_INT);
                    $kbjiStmt->bindValue(':kode', (string) $item->kode, PDO::PARAM_STR);
                    $kbjiStmt->bindValue(':judul', (string) $item->judul, PDO::PARAM_STR);
                    $kbjiStmt->bindValue(':deskripsi', (string) ($item->deskripsi ?? ''), PDO::PARAM_STR);
                    $kbjiStmt->bindValue(':contoh_lapangan', $contohJson, PDO::PARAM_STR);
                    $kbjiStmt->bindValue(':embedding', $embeddingBlob, PDO::PARAM_LOB);
                    $kbjiStmt->execute();

                    $kbjiFtsStmt->execute([
                        ':rowid' => $item->id,
                        ':kode' => (string) $item->kode,
                        ':judul' => (string) $item->judul,
                        ':deskripsi' => (string) ($item->deskripsi ?? ''),
                        ':contoh_lapangan' => $contohText,
                    ]);

                    $kbjiCount++;
                }
            });
            $pdo->commit();
            $this->info("   -> Processed {$kbjiCount} KBJI 2014 records.");

            // 4. Save metadata table
            $metaStmt = $pdo->prepare("INSERT INTO meta (key, value) VALUES (:key, :value)");
            $metaStmt->execute([':key' => 'version', ':value' => $version]);
            $metaStmt->execute([':key' => 'app_name', ':value' => 'PINTAR KBLI']);
            $metaStmt->execute([':key' => 'generated_at', ':value' => now()->toIso8601String()]);
            $metaStmt->execute([':key' => 'kbli_count', ':value' => (string) $kbliCount]);
            $metaStmt->execute([':key' => 'kbji_count', ':value' => (string) $kbjiCount]);

            // Re-enable normal sync & vacuum for smallest file size
            $pdo->exec("PRAGMA synchronous = 1;");
            $pdo->exec("VACUUM;");
            unset($pdo);

            $rawDbSize = filesize($tempDbPath);
            $rawDbSizeMb = round($rawDbSize / (1024 * 1024), 2);
            $this->info("5. Raw SQLite database size: {$rawDbSizeMb} MB ({$rawDbSize} bytes)");

            // 5. Compress database with gzip for download
            $this->info("6. Compressing bundle with GZIP...");
            $fpIn = fopen($tempDbPath, 'rb');
            $fpOut = gzopen($gzPath, 'wb9');
            while (!feof($fpIn)) {
                gzwrite($fpOut, fread($fpIn, 1024 * 512));
            }
            fclose($fpIn);
            gzclose($fpOut);

            // Copy temp file to permanent dbPath
            @copy($tempDbPath, $dbPath);
            @unlink($tempDbPath);

            $gzSize = filesize($gzPath);
            $gzSizeMb = round($gzSize / (1024 * 1024), 2);
            $md5Hash = md5_file($gzPath);
            $sha256Hash = hash_file('sha256', $gzPath);

            $this->info("   -> Compressed size: {$gzSizeMb} MB ({$gzSize} bytes)");
            $this->info("   -> MD5 Checksum: {$md5Hash}");

            // 6. Write bundle metadata JSON
            $metaData = [
                'status' => 'ready',
                'version' => $version,
                'generated_at' => now()->toIso8601String(),
                'kbli_count' => $kbliCount,
                'kbji_count' => $kbjiCount,
                'raw_file_size_bytes' => $rawDbSize,
                'raw_file_size_mb' => $rawDbSizeMb,
                'file_size_bytes' => $gzSize,
                'file_size_mb' => $gzSizeMb,
                'md5' => $md5Hash,
                'sha256' => $sha256Hash,
            ];
            file_put_contents($metaPath, json_encode($metaData, JSON_PRETTY_PRINT));

            $this->info("=================================================");
            $this->info("  SUCCESS! Offline Bundle v{$version} is Ready.");
            $this->info("  Bundle: {$gzPath}");
            $this->info("  Meta:   {$metaPath}");
            $this->info("=================================================");

            return Command::SUCCESS;

        } catch (Exception $e) {
            $this->error("Failed to generate offline bundle: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
