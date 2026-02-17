<?php

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function (Request $request) {
    $query = Transaction::with(['logs', 'shipment']);

    // Account type filter
    $accountTypes = $request->query('account_types');
    if ($accountTypes && !is_array($accountTypes)) {
        $query->where('account_type', $accountTypes);
    } elseif ($accountTypes && is_array($accountTypes) && !empty(array_filter($accountTypes))) {
        $query->whereIn('account_type', array_filter($accountTypes));
    } else {
        $query->whereIn('account_type', ['checking', 'savings', 'credit']);
    }

    // Brand filter
    $origins = $request->query('order_origins');
    if ($origins) {
        $query->whereIn('order_origin', explode(',', $origins));
    }

    // Shipment status filter
    $shipmentStatus = $request->query('shipment_status');
    if ($shipmentStatus === 'unshipped') {
        $query->doesntHave('shipment');
    } elseif ($shipmentStatus) {
        $query->whereHas('shipment', fn ($q) => $q->where('status', $shipmentStatus));
    }

    // Sorting
    $sortField = $request->query('sort_field', 'timestamp');
    $sortOrder = $request->query('sort_order', 'desc');

    // Aggregations — reorder() clears any pending ORDER BY before GROUP BY to satisfy only_full_group_by
    $summary = [
        'total_sum' => (clone $query)->sum('amount'),
        'by_brand'  => (clone $query)
            ->reorder()
            ->groupBy('order_origin')
            ->selectRaw('order_origin, sum(amount) as total')
            ->pluck('total', 'order_origin')
            ->toArray(),
    ];

    $transactions = $query->orderBy($sortField, $sortOrder)
        ->paginate($request->query('per_page', 10));

    return Inertia::render('Dashboard', [
        'transactions' => $transactions,
        'summary'      => $summary,
        'filters'      => [
            'account_type'    => $accountTypes ?: null,
            'order_origins'   => $origins ? explode(',', $origins) : [],
            'shipment_status' => $shipmentStatus ?: null,
            'sort_field'      => $sortField,
            'sort_order'      => $sortOrder,
        ],
    ]);
});

Route::get('/api/transactions/{transaction}/details', function (Transaction $transaction) {
    return response()->json([
        'shipment_logs' => $transaction->shipment?->logs ?? [],
    ]);
});
