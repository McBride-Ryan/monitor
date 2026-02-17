import { useState, useEffect } from 'react';
import { Transaction, Shipment, ShipmentLog, PaginatedTransactions, FilterState } from '../types/transaction';
import echo from '../echo';

interface UseTransactionsOptions {
    initialData: PaginatedTransactions;
    filters: FilterState;
}

// 'loading' blocks duplicate fetches; ShipmentLog[] is the resolved data
export type DetailsCache = Record<number, ShipmentLog[] | 'loading'>;

export function useTransactions({ initialData, filters }: UseTransactionsOptions) {
    const [paginatedData, setPaginatedData] = useState<PaginatedTransactions>(initialData);
    const [pendingCount, setPendingCount] = useState(0);
    const [detailsCache, setDetailsCache] = useState<DetailsCache>({});

    // Sync with Inertia prop updates; clear cache on page/filter change
    useEffect(() => {
        setPaginatedData(initialData);
        setDetailsCache({});
    }, [initialData]);

    // Auto-prepend only if: page 1 + default sort + no filters
    const shouldAutoPrepend =
        paginatedData.current_page === 1 &&
        filters.sort_field === 'timestamp' &&
        filters.sort_order === 'desc' &&
        !filters.account_type &&
        filters.order_origins.length === 0 &&
        !filters.shipment_status;

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

        channel.listen('ShipmentUpdated', (e: { shipment: Shipment }) => {
            const { logs, ...shipmentFields } = e.shipment;

            // 1. Patch shipment fields in table rows (no logs — keeps row state lean)
            setPaginatedData(prev => ({
                ...prev,
                data: prev.data.map(tx => {
                    if (tx.id !== shipmentFields.transaction_id) return tx;
                    return {
                        ...tx,
                        shipment: tx.shipment
                            ? { ...tx.shipment, ...shipmentFields }
                            : shipmentFields,
                    };
                }),
            }));

            // 2. If details for this transaction are already cached, merge in the new logs
            if (logs) {
                setDetailsCache(prev => {
                    if (prev[shipmentFields.transaction_id] === undefined) return prev;
                    return { ...prev, [shipmentFields.transaction_id]: logs };
                });
            }
        });

        return () => echo.leave('transactions');
    }, [shouldAutoPrepend]);

    const fetchDetails = (transactionId: number) => {
        // 'loading' or resolved array both mean we already have or are fetching data
        if (detailsCache[transactionId] !== undefined) return;

        // Mark loading immediately — prevents duplicate in-flight requests
        setDetailsCache(prev => ({ ...prev, [transactionId]: 'loading' }));

        fetch(`/api/transactions/${transactionId}/details`)
            .then(r => r.json())
            .then((data: { shipment_logs: ShipmentLog[] }) => {
                setDetailsCache(prev => ({
                    ...prev,
                    [transactionId]: data.shipment_logs ?? [],
                }));
            })
            .catch(() => {
                // Remove loading marker so the row can retry on next expand
                setDetailsCache(prev => {
                    const next = { ...prev };
                    delete next[transactionId];
                    return next;
                });
            });
    };

    const clearPending = () => setPendingCount(0);

    return { paginatedData, pendingCount, clearPending, detailsCache, fetchDetails };
}
