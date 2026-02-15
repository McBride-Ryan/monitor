import { DataTable } from 'primereact/datatable';
import { Column } from 'primereact/column';
import { Dropdown } from 'primereact/dropdown';
import { Button } from 'primereact/button';
import { router } from '@inertiajs/react';
import { PaginatedLogs, DataAuditLog } from '../types/audit';

interface Props {
    logs: PaginatedLogs;
    selectedSeverity?: string;
    selectedType?: string;
    selectedResolved: string;
    onSeverityChange: (severity: string | undefined) => void;
    onTypeChange: (type: string | undefined) => void;
    onResolvedChange: (resolved: string) => void;
    onResolve: (logId: number) => void;
}

export default function AuditExceptionTable({
    logs,
    selectedSeverity,
    selectedType,
    selectedResolved,
    onSeverityChange,
    onTypeChange,
    onResolvedChange,
    onResolve,
}: Props) {
    const severityOptions = [
        { label: 'All Severities', value: '' },
        { label: 'Critical', value: 'critical' },
        { label: 'Warning', value: 'warning' },
        { label: 'Info', value: 'info' },
    ];

    const typeOptions = [
        { label: 'All Types', value: '' },
        { label: 'Price Discrepancy', value: 'price_discrepancy' },
        { label: 'Broken Asset', value: 'broken_asset' },
        { label: 'Orphaned Product', value: 'orphaned_product' },
    ];

    const resolvedOptions = [
        { label: 'Unresolved Only', value: 'unresolved' },
        { label: 'Resolved Only', value: 'resolved' },
        { label: 'All', value: 'all' },
    ];

    const severityBadge = (log: DataAuditLog) => {
        const colors = {
            critical: 'bg-red-100 text-red-700',
            warning: 'bg-yellow-100 text-yellow-700',
            info: 'bg-blue-100 text-blue-700',
        };

        return (
            <span className={`px-2 py-1 rounded-full text-xs font-medium ${colors[log.severity]}`}>
                {log.severity}
            </span>
        );
    };

    const typeLabel = (log: DataAuditLog) => {
        const labels: Record<string, string> = {
            price_discrepancy: 'Price Discrepancy',
            broken_asset: 'Broken Asset',
            orphaned_product: 'Orphaned Product',
        };

        return labels[log.audit_type] || log.audit_type;
    };

    const detailsBody = (log: DataAuditLog) => {
        return (
            <div className="text-sm">
                <div className="font-medium text-gray-900">{log.details.message}</div>
                {log.details.sku && (
                    <div className="text-gray-500 mt-1">SKU: {log.details.sku}</div>
                )}
            </div>
        );
    };

    const actionBody = (log: DataAuditLog) => {
        if (log.resolved_at) {
            return (
                <span className="text-xs text-green-600 font-medium">
                    Resolved
                </span>
            );
        }

        return (
            <Button
                label="Resolve"
                size="small"
                severity="success"
                outlined
                onClick={() => onResolve(log.id)}
            />
        );
    };

    const handlePageChange = (event: any) => {
        const page = event.page + 1;
        const url = logs.links[page]?.url;
        if (url) {
            router.visit(url, { preserveScroll: true });
        }
    };

    return (
        <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div className="px-6 py-4 border-b border-gray-100">
                <h3 className="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-4">
                    Audit Exceptions
                </h3>
                <div className="flex flex-wrap gap-3">
                    <Dropdown
                        value={selectedSeverity || ''}
                        options={severityOptions}
                        onChange={(e) => onSeverityChange(e.value || undefined)}
                        placeholder="Filter by severity"
                        className="w-48"
                    />
                    <Dropdown
                        value={selectedType || ''}
                        options={typeOptions}
                        onChange={(e) => onTypeChange(e.value || undefined)}
                        placeholder="Filter by type"
                        className="w-48"
                    />
                    <Dropdown
                        value={selectedResolved}
                        options={resolvedOptions}
                        onChange={(e) => onResolvedChange(e.value)}
                        className="w-48"
                    />
                </div>
            </div>
            <DataTable
                value={logs.data}
                stripedRows
                paginator
                rows={logs.per_page}
                totalRecords={logs.total}
                lazy
                first={(logs.current_page - 1) * logs.per_page}
                onPage={handlePageChange}
                emptyMessage="No audit exceptions found."
                className="p-datatable-sm"
            >
                <Column
                    field="severity"
                    header="Severity"
                    body={severityBadge}
                    style={{ width: '100px' }}
                />
                <Column
                    header="Type"
                    body={typeLabel}
                    style={{ width: '150px' }}
                />
                <Column
                    header="Details"
                    body={detailsBody}
                />
                <Column
                    field="created_at"
                    header="Created"
                    body={(log: DataAuditLog) => new Date(log.created_at).toLocaleDateString()}
                    style={{ width: '120px' }}
                />
                <Column
                    header="Action"
                    body={actionBody}
                    style={{ width: '120px' }}
                />
            </DataTable>
        </div>
    );
}
