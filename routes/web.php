<?php

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
