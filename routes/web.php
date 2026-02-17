<?php

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function (Request $request) {
    $query = Transaction::with(['logs', 'shipment.logs']);

    // Filters
    $accountTypes = $request->query('account_types');

    // If account_types is empty, null, or an array with empty/null values, default to all
    if ($accountTypes && !is_array($accountTypes)) {
        $query->where('account_type', $accountTypes);
    } elseif ($accountTypes && is_array($accountTypes) && !empty(array_filter($accountTypes))) {
        $query->whereIn('account_type', array_filter($accountTypes));
    } else {
        $query->whereIn('account_type', ['checking', 'savings', 'credit']);
    }

    $origins = $request->query('order_origins');
    if ($origins) {
        $query->whereIn('order_origin', explode(',', $origins));
    }

    $shipmentStatus = $request->query('shipment_status');
    if ($shipmentStatus === 'unshipped') {
        $query->doesntHave('shipment');
    } elseif ($shipmentStatus) {
        $query->whereHas('shipment', fn ($q) => $q->where('status', $shipmentStatus));
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
        ->paginate($request->query('per_page', 10));

    return Inertia::render('Dashboard', [
        'transactions' => $transactions,
        'summary' => $summary,
        'filters' => [
            'account_type'    => $accountTypes ?: null,
            'order_origins'   => $origins ? explode(',', $origins) : [],
            'shipment_status' => $shipmentStatus ?: null,
            'sort_field'      => $sortField,
            'sort_order'      => $sortOrder,
        ],
    ]);
});
