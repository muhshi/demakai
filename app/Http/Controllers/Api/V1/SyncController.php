<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PgKBJI2014;
use App\Models\PgKBLI2025;
use App\Models\FieldExampleSubmission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SyncController extends Controller
{
    /**
     * Check if a newer offline bundle version is available for Flutter.
     */
    public function checkVersion(Request $request): JsonResponse
    {
        $metaPath = storage_path('app/bundles/bundle_meta.json');
        $bundlePathGz = storage_path('app/bundles/kbli_offline_latest.db.gz');
        $bundlePathDb = storage_path('app/bundles/kbli_offline_latest.db');

        $activeBundle = file_exists($bundlePathGz) ? $bundlePathGz : (file_exists($bundlePathDb) ? $bundlePathDb : null);

        if (file_exists($metaPath) && $activeBundle) {
            $meta = json_decode(file_get_contents($metaPath), true) ?? [];
            $fileSize = filesize($activeBundle);
            $meta['file_size_bytes'] = $fileSize;
            $meta['file_size_mb'] = round($fileSize / (1024 * 1024), 2);
            $meta['download_url'] = url('/api/v1/sync/bundle');
            $meta['model_download_url'] = url('/api/v1/sync/model');

            return response()->json([
                'status' => 'success',
                'data' => $meta
            ]);
        }

        // Default dynamic fallback if bundle hasn't been generated yet
        $kbliCount = PgKBLI2025::count();
        $kbjiCount = PgKBJI2014::count();
        $approvedSubmissions = FieldExampleSubmission::where('status', 'approved')->count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'version' => '2026.08.1',
                'generated_at' => now()->toIso8601String(),
                'kbli_count' => $kbliCount,
                'kbji_count' => $kbjiCount,
                'examples_count' => $approvedSubmissions,
                'file_size_mb' => 0,
                'is_ready' => false,
                'download_url' => url('/api/v1/sync/bundle'),
                'model_download_url' => url('/api/v1/sync/model'),
                'message' => 'Paket offline sedang disiapkan. Silakan jalankan php artisan kbli:export-bundle pada server.'
            ]
        ]);
    }

    /**
     * Download the latest compressed SQLite database bundle for offline use.
     */
    public function downloadBundle(): BinaryFileResponse|JsonResponse
    {
        $bundleGz = storage_path('app/bundles/kbli_offline_latest.db.gz');
        $bundleDb = storage_path('app/bundles/kbli_offline_latest.db');

        if (file_exists($bundleGz)) {
            return response()->download($bundleGz, 'kbli_offline.db.gz', [
                'Content-Type' => 'application/gzip',
                'Cache-Control' => 'no-cache, must-revalidate',
            ]);
        }

        if (file_exists($bundleDb)) {
            return response()->download($bundleDb, 'kbli_offline.db', [
                'Content-Type' => 'application/x-sqlite3',
                'Cache-Control' => 'no-cache, must-revalidate',
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Paket offline database belum digenerate di server. Jalankan: php artisan kbli:export-bundle'
        ], 404);
    }

    /**
     * Download the on-device embedding ONNX model.
     */
    public function downloadModel(): BinaryFileResponse|JsonResponse
    {
        $modelPath = storage_path('app/models/kbli_model.onnx');

        if (file_exists($modelPath)) {
            return response()->download($modelPath, 'kbli_model.onnx', [
                'Content-Type' => 'application/octet-stream',
                'Cache-Control' => 'public, max-age=86400',
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Model ONNX belum digenerate di server. Jalankan script python export_onnx_model.py.'
        ], 404);
    }
}
