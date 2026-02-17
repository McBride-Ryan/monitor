import { useState } from 'react';
import { router } from '@inertiajs/react';
import { PaginatedTransactions, FilterState, TransactionSummary } from '../types/transaction';
import { useTransactions } from '../hooks/useTransactions';
import { useConnectionStatus } from '../hooks/useConnectionStatus';
import ConnectionStatusBanner from './ConnectionStatusBanner';
import TotalSumCard from './TotalSumCard';
import BrandSummaryCard from './BrandSummaryCard';
import FilterSidebar from './FilterSidebar';
import TransactionTable from './TransactionTable';

interface Props {
    initialData: PaginatedTransactions;
    initialFilters: FilterState;
    summary: TransactionSummary;
}

export default function TransactionDashboard({
    initialData,
    initialFilters,
    summary
}: Props) {
    const { paginatedData, pendingCount, clearPending, detailsCache, fetchDetails } = useTransactions({
        initialData,
        filters: initialFilters,
    });
    const status = useConnectionStatus();

    const [filters, setFilters] = useState<FilterState>(initialFilters);

    const reloadData = (newFilters: Partial<FilterState> = {}) => {
        const updated = { ...filters, ...newFilters };

        const params: Record<string, any> = {
            sort_field: updated.sort_field,
            sort_order: updated.sort_order,
            page: 1,
        };

        if (updated.account_type) {
            params.account_types = updated.account_type;
        }

        if (updated.order_origins.length > 0) {
            params.order_origins = updated.order_origins.join(',');
        }

        if (updated.shipment_status) {
            params.shipment_status = updated.shipment_status;
        }

        router.get('/', params, {
            preserveState: true,
            preserveScroll: true,
            only: ['transactions', 'summary'],
            onSuccess: () => clearPending(),
        });

        setFilters(updated);
    };

    const handlePageChange = (page: number, rows: number) => {
        const params: Record<string, any> = {
            sort_field: filters.sort_field,
            sort_order: filters.sort_order,
            page,
            per_page: rows,
        };

        if (filters.account_type) {
            params.account_types = filters.account_type;
        }

        if (filters.order_origins.length > 0) {
            params.order_origins = filters.order_origins.join(',');
        }

        if (filters.shipment_status) {
            params.shipment_status = filters.shipment_status;
        }

        router.get('/', params, {
            preserveState: true,
            preserveScroll: true,
            only: ['transactions'],
        });
    };

    const handleSort = (field: string, order: 'asc' | 'desc') => {
        reloadData({ sort_field: field, sort_order: order });
    };

    return (
        <div className="min-h-screen" style={{ background: '#0f172a' }}>
            {/* Notification bar */}
            {pendingCount > 0 && (
                <div className="animate-slide-down fixed top-0 left-0 right-0 z-50 flex items-center justify-between px-6 py-3"
                    style={{ background: '#06b6d4', color: '#0f172a' }}>
                    <span className="font-semibold text-sm">
                        {pendingCount} new transaction{pendingCount > 1 ? 's' : ''} available
                    </span>
                    <button
                        onClick={() => reloadData()}
                        className="text-xs font-bold px-3 py-1 rounded"
                        style={{ background: '#0f172a', color: '#06b6d4' }}
                    >
                        Load
                    </button>
                </div>
            )}

            <header style={{ background: '#1e293b', borderBottom: '1px solid #334155' }}
                className="px-4 sm:px-6 lg:px-8 py-4">
                <div className="max-w-7xl mx-auto flex items-center justify-between">
                    <h1 className="text-xl font-bold" style={{ color: '#f1f5f9' }}>Transaction Monitor</h1>
                    <ConnectionStatusBanner status={status} />
                </div>
            </header>

            <main className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                <div className="grid grid-cols-1 lg:grid-cols-4 gap-6">
                    <div className="lg:col-span-1 space-y-6">
                        <FilterSidebar
                            accountType={filters.account_type}
                            onAccountTypeChange={(type) =>
                                reloadData({ account_type: type })
                            }
                            orderOrigins={filters.order_origins}
                            onOrderOriginsChange={(origins) =>
                                reloadData({ order_origins: origins })
                            }
                            shipmentStatus={filters.shipment_status}
                            onShipmentStatusChange={(status) =>
                                reloadData({ shipment_status: status })
                            }
                        />
                        <TotalSumCard totalSum={summary.total_sum} />
                        <BrandSummaryCard brandTotals={summary.by_brand} />
                    </div>

                    <div className="lg:col-span-3">
                        <TransactionTable
                            paginatedData={paginatedData}
                            onPageChange={handlePageChange}
                            onSort={handleSort}
                            detailsCache={detailsCache}
                            fetchDetails={fetchDetails}
                        />
                    </div>
                </div>
            </main>
        </div>
    );
}
