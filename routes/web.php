<?php

use Illuminate\Support\Facades\Route;
use App\Exports\KBLI2020TemplateExport;
use App\Exports\KBLI2025TemplateExport;
use App\Exports\KBJI2014TemplateExport;
use Maatwebsite\Excel\Facades\Excel;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/templates/kbli2020', fn() => Excel::download(new KBLI2020TemplateExport, 'template_kbli2020.xlsx'))->name('template.kbli2020');
Route::get('/templates/kbli2025', fn() => Excel::download(new KBLI2025TemplateExport, 'template_kbli2025.xlsx'))->name('template.kbli2025');
Route::get('/templates/kbji2014', fn() => Excel::download(new KBJI2014TemplateExport, 'template_kbji2014.xlsx'))->name('template.kbji2014');

use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\FieldExampleSubmissionController;
use App\Http\Controllers\Auth\SsoController;

Route::get('/auth/sipetra/redirect', [SsoController::class, 'redirect'])->name('sipetra.login');
Route::get('/auth/sipetra/callback', [SsoController::class, 'callback']);

Route::get('/api/search', [SearchController::class, 'search'])->name('search.api');
Route::post('/api/submissions', [FieldExampleSubmissionController::class, 'store'])->name('submissions.store');

Route::get('/cekhasilmethod', function () {
    $files = glob(base_path('python/output/*_Gabungan.html'));
    
    if (empty($files)) {
        return response("Belum ada file evaluasi Gabungan yang dibuat. Silakan jalankan script python evaluate.py terlebih dahulu.", 404)
            ->header('Content-Type', 'text/plain');
    }
    
    // Urutkan file berdasarkan waktu modifikasi terbaru
    usort($files, function($a, $b) {
        return filemtime($b) - filemtime($a);
    });
    
    return response(file_get_contents($files[0]))
        ->header('Content-Type', 'text/html');
});

Route::get('/cekhasil-sebelum', function () {
    $file = base_path('python/output/evaluasi_sebelum_db.html');
    if (!file_exists($file)) {
        return response("Belum ada laporan sebelum update (evaluasi_sebelum_db.html).", 404);
    }
    return response(file_get_contents($file))
        ->header('Content-Type', 'text/html');
});
