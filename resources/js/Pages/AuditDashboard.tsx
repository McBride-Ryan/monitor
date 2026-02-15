import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { AuditSummary, AuditFilters, PaginatedLogs } from '../types/audit';
import AppLayout from '../components/AppLayout';
import AuditSummaryCards from '../components/AuditSummaryCards';
import AuditExceptionTable from '../components/AuditExceptionTable';

interface Props {
    logs: PaginatedLogs;
    summary: AuditSummary;
    filters: AuditFilters;
}

export default function AuditDashboard({ logs, summary, filters }: Props) {
    const [selectedSeverity, setSelectedSeverity] = useState<string | undefined>(filters.severity);
    const [selectedType, setSelectedType] = useState<string | undefined>(filters.type);
    const [selectedResolved, setSelectedResolved] = useState<string>(filters.resolved || 'unresolved');

    const applyFilters = (severity?: string, type?: string, resolved?: string) => {
        router.get('/audits', {
            severity: severity || undefined,
            type: type || undefined,
            resolved: resolved || 'unresolved',
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const handleSeverityChange = (severity: string | undefined) => {
        setSelectedSeverity(severity);
        applyFilters(severity, selectedType, selectedResolved);
    };

    const handleTypeChange = (type: string | undefined) => {
        setSelectedType(type);
        applyFilters(selectedSeverity, type, selectedResolved);
    };

    const handleResolvedChange = (resolved: string) => {
        setSelectedResolved(resolved);
        applyFilters(selectedSeverity, selectedType, resolved);
    };

    const handleResolve = (logId: number) => {
        router.post(`/audits/${logId}/resolve`, {}, {
            preserveScroll: true,
        });
    };

    return (
        <AppLayout title="Data Audits">
            <Head title="Data Audits" />
            <main className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">
                <AuditSummaryCards summary={summary} />

                <AuditExceptionTable
                    logs={logs}
                    selectedSeverity={selectedSeverity}
                    selectedType={selectedType}
                    selectedResolved={selectedResolved}
                    onSeverityChange={handleSeverityChange}
                    onTypeChange={handleTypeChange}
                    onResolvedChange={handleResolvedChange}
                    onResolve={handleResolve}
                />
            </main>
        </AppLayout>
    );
}
