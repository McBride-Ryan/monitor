import { useState, useEffect } from 'react';
import { Transaction, PaginatedTransactions, FilterState } from '../types/transaction';
import echo from '../echo';

interface UseTransactionsOptions {
    initialData: PaginatedTransactions;
    filters: FilterState;
}

export function useTransactions({ initialData, filters }: UseTransactionsOptions) {
    const [paginatedData, setPaginatedData] = useState<PaginatedTransactions>(initialData);
    const [pendingCount, setPendingCount] = useState(0);

    // Sync with Inertia prop updates
    useEffect(() => {
        setPaginatedData(initialData);
    }, [initialData]);

    // Auto-prepend only if: page 1 + default sort + no filters
    const shouldAutoPrepend =
        paginatedData.current_page === 1 &&
        filters.sort_field === 'timestamp' &&
        filters.sort_order === 'desc' &&
        !filters.account_type &&
        filters.order_origins.length === 0;

    useEffect(() => {
        const channel = echo.channel('transactions');

        channel.listen('TransactionCreated', (e: { transaction: Transaction }) => {
            if (shouldAutoPrepend) {
                setPaginatedData(prev => ({
                    ...prev,
                    data: [e.transaction, ...prev.data],
                    total: prev.total + 1,
                }));
            } else {
                setPendingCount(prev => prev + 1);
            }
        });

        return () => echo.leave('transactions');
    }, [shouldAutoPrepend]);

    const clearPending = () => setPendingCount(0);

    return { paginatedData, pendingCount, clearPending };
}
