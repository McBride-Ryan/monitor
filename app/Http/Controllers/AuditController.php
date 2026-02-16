<?php

namespace App\Http\Controllers;

use App\Models\DataAuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditController extends Controller
{
    public function index(Request $request): Response
    {
        $query = DataAuditLog::query();

        // Filter by severity
        if ($request->filled('severity')) {
            $query->bySeverity($request->severity);
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->byType($request->type);
        }

        // Filter by resolved status
        if ($request->filled('resolved')) {
            if ($request->resolved === 'unresolved') {
                $query->unresolved();
            } elseif ($request->resolved === 'resolved') {
                $query->whereNotNull('resolved_at');
            }
        } else {
            // Default: show only unresolved
            $query->unresolved();
        }

        // Get paginated logs
        $logs = $query->orderBy('created_at', 'desc')
            ->paginate(25)
            ->withQueryString();

        // Get summary stats
        $summary = [
            'total_unresolved' => DataAuditLog::unresolved()->count(),
            'by_severity' => [
                'critical' => DataAuditLog::unresolved()->bySeverity('critical')->count(),
                'warning' => DataAuditLog::unresolved()->bySeverity('warning')->count(),
                'info' => DataAuditLog::unresolved()->bySeverity('info')->count(),
            ],
            'by_type' => [
                'price_discrepancy' => DataAuditLog::unresolved()->byType('price_discrepancy')->count(),
                'broken_asset' => DataAuditLog::unresolved()->byType('broken_asset')->count(),
                'orphaned_product' => DataAuditLog::unresolved()->byType('orphaned_product')->count(),
            ],
        ];

        return Inertia::render('AuditDashboard', [
            'logs' => $logs,
            'summary' => $summary,
            'filters' => [
                'severity' => $request->severity,
                'type' => $request->type,
                'resolved' => $request->resolved ?? 'unresolved',
            ],
        ]);
    }

    public function resolve(DataAuditLog $log)
    {
        $log->update(['resolved_at' => now()]);

        return redirect()->back()->with('success', 'Audit log resolved');
    }
}
