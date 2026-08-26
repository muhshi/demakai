<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\MobileSearchController;
use App\Http\Controllers\Api\V1\SyncController;

/*
|--------------------------------------------------------------------------
| PINTAR KBLI API Routes (Mobile Flutter & Client Apps)
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {
    // 1. Search Endpoints
    Route::get('/search', [MobileSearchController::class, 'search'])->name('api.v1.search');
    Route::get('/kbli/hierarchy', [MobileSearchController::class, 'getHierarchy'])->name('api.v1.hierarchy');

    // 2. Offline Sync & Bundle Download
    Route::get('/sync/check', [SyncController::class, 'checkVersion'])->name('api.v1.sync.check');
    Route::get('/sync/bundle', [SyncController::class, 'downloadBundle'])->name('api.v1.sync.bundle');
    Route::get('/sync/model', [SyncController::class, 'downloadModel'])->name('api.v1.sync.model');

    // 3. Crowdsourcing Submissions (Online & Bulk Offline Sync)
    Route::post('/submissions', [MobileSearchController::class, 'storeSubmission'])->name('api.v1.submissions.store');
    Route::post('/submissions/bulk-sync', [MobileSearchController::class, 'bulkSyncSubmissions'])->name('api.v1.submissions.bulk_sync');
});
