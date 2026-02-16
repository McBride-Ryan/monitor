import { useState, useEffect, useRef } from 'react';
import { createPortal } from 'react-dom';
import { router } from '@inertiajs/react';
import { Toast } from 'primereact/toast';
import { Button } from 'primereact/button';
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
    const { paginatedData, pendingCount, clearPending } = useTransactions({
        initialData,
        filters: initialFilters,
    });
    const status = useConnectionStatus();
    const toast = useRef<Toast>(null);

    const [filters, setFilters] = useState<FilterState>(initialFilters);

    const reloadData = (newFilters: Partial<FilterState> = {}) => {
        const updated = { ...filters, ...newFilters };

        const params: Record<string, any> = {
            sort_field: updated.sort_field,
            sort_order: updated.sort_order,
            page: 1,
        };

        // Convert single account_type to array for backend
        if (updated.account_type) {
            params.account_types = updated.account_type;
        }
        // If null/All, don't send param (backend defaults to all three)

        if (updated.order_origins.length > 0) {
            params.order_origins = updated.order_origins.join(',');
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

        router.get('/', params, {
            preserveState: true,
            preserveScroll: true,
            only: ['transactions'],
        });
    };

    const handleSort = (field: string, order: 'asc' | 'desc') => {
        reloadData({ sort_field: field, sort_order: order });
    };

    useEffect(() => {
        if (pendingCount > 0) {
            toast.current?.clear();
            toast.current?.show({
                severity: 'info',
                summary: 'New Transactions',
                detail: `${pendingCount} new transaction(s) available`,
                sticky: true,
                content: (props) => (
                    <div className="p-toast-message-content">
                        <span className="p-toast-message-text">
                            <span className="p-toast-summary">{props.message.summary}</span>
                            <div className="p-toast-detail">{props.message.detail}</div>
                        </span>
                        <Button
                            label="Refresh"
                            size="small"
                            onClick={() => {
                                reloadData();
                                toast.current?.clear();
                            }}
                        />
                    </div>
                ),
            });
        } else {
            toast.current?.clear();
        }
    }, [pendingCount]);

    return (
        <>
            {createPortal(
                <Toast ref={toast} />,
                document.body
            )}
            <div className="min-h-screen bg-gray-50">
                <header className="bg-white border-b border-gray-200 px-4 sm:px-6 lg:px-8 py-4">
                    <div className="max-w-7xl mx-auto flex items-center justify-between">
                        <h1 className="text-xl font-bold text-gray-900">Transaction Monitor</h1>
                        <ConnectionStatusBanner status={status} />
                    </div>
                </header>

                <main className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                    <div className="grid grid-cols-1 lg:grid-cols-4 gap-6">
                        {/* Sidebar */}
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
                            />
                            <TotalSumCard totalSum={summary.total_sum} />
                            <BrandSummaryCard brandTotals={summary.by_brand} />
                        </div>

                        {/* Main content */}
                        <div className="lg:col-span-3">
                            <TransactionTable
                                paginatedData={paginatedData}
                                onPageChange={handlePageChange}
                                onSort={handleSort}
                            />
                        </div>
                    </div>
                </main>
            </div>
        </>
    );
}
