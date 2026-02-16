<?php

use App\Http\Controllers\AuditController;
use App\Http\Controllers\VendorImportController;
use App\Models\Transaction;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    $transactions = Transaction::with('logs')
        ->orderByDesc('timestamp')
        ->get();

    return Inertia::render('Dashboard', [
        'transactions' => $transactions,
    ]);
});

Route::get('/vendor-import', [VendorImportController::class, 'index']);
Route::post('/vendor-import/preview', [VendorImportController::class, 'preview']);
Route::post('/vendor-import/import', [VendorImportController::class, 'import']);

Route::get('/audits', [AuditController::class, 'index']);
Route::post('/audits/{log}/resolve', [AuditController::class, 'resolve']);
