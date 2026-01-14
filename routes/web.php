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
