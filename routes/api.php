<?php

use App\Http\Controllers\AnalysisController;
use App\Http\Controllers\CsvController;
use App\Http\Controllers\NpvController;
use App\Http\Controllers\PriceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Semua route di sini otomatis mendapat prefix /api
| dan menggunakan middleware 'api' (stateless, tanpa session/CSRF).
|
*/

// ── Harga Realtime ────────────────────────────────────────────────────────────
Route::get('/prices', [PriceController::class, 'prices']);

// ── Upload & Validasi CSV Historis ────────────────────────────────────────────
Route::post('/csv/validate', [CsvController::class, 'validate']);

// ── Kalkulasi NPV ─────────────────────────────────────────────────────────────
Route::post('/npv/calculate', [NpvController::class, 'calculate']);

// ── Riwayat Analisis (CRUD) ───────────────────────────────────────────────────
Route::prefix('analysis')->group(function () {
    Route::get('/',          [AnalysisController::class, 'index']);      // GET    /api/analysis
    Route::post('/',         [AnalysisController::class, 'store']);      // POST   /api/analysis
    Route::get('/export',    [AnalysisController::class, 'export']);     // GET    /api/analysis/export
    Route::delete('/all',    [AnalysisController::class, 'destroyAll']); // DELETE /api/analysis/all
    Route::delete('/{analysis}', [AnalysisController::class, 'destroy']); // DELETE /api/analysis/{id}
});
