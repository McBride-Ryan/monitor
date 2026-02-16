<?php

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function (Request $request) {
    $query = Transaction::with('logs');

    // Filters
    $accountTypes = $request->query('account_types');

    // If account_types is empty, null, or an array with empty/null values, default to all
    if ($accountTypes && !is_array($accountTypes)) {
        // Single value like "checking"
        $query->where('account_type', $accountTypes);
    } elseif ($accountTypes && is_array($accountTypes) && !empty(array_filter($accountTypes))) {
        // Array with actual values
        $query->whereIn('account_type', array_filter($accountTypes));
    } else {
        // Default: all account types (null, empty, or array with empty values)
        $query->whereIn('account_type', ['checking', 'savings', 'credit']);
    }

    $origins = $request->query('order_origins');
    if ($origins) {
        $query->whereIn('order_origin', explode(',', $origins));
    }

    // Sorting
    $sortField = $request->query('sort_field', 'timestamp');
    $sortOrder = $request->query('sort_order', 'desc');

    // Calculate aggregations before pagination
    $summary = [
        'total_sum' => (clone $query)->sum('amount'),
        'by_brand' => (clone $query)
            ->groupBy('order_origin')
            ->selectRaw('order_origin, sum(amount) as total')
            ->pluck('total', 'order_origin')
            ->toArray(),
    ];

    // Apply sort and paginate
    $transactions = $query->orderBy($sortField, $sortOrder)
        ->paginate($request->query('per_page', 20));

    return Inertia::render('Dashboard', [
        'transactions' => $transactions,
        'summary' => $summary,
        'filters' => [
            'account_type' => $accountTypes ?: null,
            'order_origins' => $origins ? explode(',', $origins) : [],
            'sort_field' => $sortField,
            'sort_order' => $sortOrder,
        ],
    ]);
});
