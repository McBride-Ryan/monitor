import { useState } from 'react';
import { DataTable, DataTablePageEvent, DataTableSortEvent, DataTableRowToggleEvent } from 'primereact/datatable';
import { Column } from 'primereact/column';
import { Transaction, Shipment, ShipmentLog, PaginatedTransactions } from '../types/transaction';
import { DetailsCache } from '../hooks/useTransactions';

interface TransactionTableProps {
    paginatedData: PaginatedTransactions;
    onPageChange: (page: number, rows: number) => void;
    onSort: (field: string, order: 'asc' | 'desc') => void;
    detailsCache: DetailsCache;
    fetchDetails: (transactionId: number) => void;
}

function formatCurrency(amount: string) {
    return parseFloat(amount).toLocaleString('en-US', {
        style: 'currency',
        currency: 'USD',
    });
}

function formatDate(timestamp: string) {
    return new Date(timestamp).toLocaleString('en-US', {
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    });
}

const shipmentStatusColors: Record<string, { bg: string; text: string }> = {
    packing:          { bg: '#78350f', text: '#fde68a' },
    shipped:          { bg: '#1e3a5f', text: '#93c5fd' },
    out_for_delivery: { bg: '#3b0764', text: '#d8b4fe' },
    delivered:        { bg: '#14532d', text: '#86efac' },
    exception:        { bg: '#7f1d1d', text: '#fca5a5' },
};

function StatusBadge({ status }: { status: string }) {
    const colors = shipmentStatusColors[status] ?? { bg: '#1e293b', text: '#94a3b8' };
    return (
        <span style={{
            background: colors.bg,
            color: colors.text,
            fontSize: '0.65rem',
            fontWeight: 600,
            padding: '1px 7px',
            borderRadius: '9999px',
            textTransform: 'capitalize',
            whiteSpace: 'nowrap',
            display: 'inline-block',
        }}>
            {status.replace(/_/g, ' ')}
        </span>
    );
}

function ShipmentCell({ shipment }: { shipment?: Shipment | null }) {
    if (!shipment) {
        return <span style={{ color: '#475569', fontSize: '0.75rem' }}>Unshipped</span>;
    }
    return (
        <div className="flex flex-col gap-1">
            <span style={{ color: '#64748b', fontSize: '0.65rem', textTransform: 'uppercase', letterSpacing: '0.05em' }}>
                {shipment.carrier}
            </span>
            <StatusBadge status={shipment.status} />
        </div>
    );
}

function TrackingTimeline({ logs }: { logs: ShipmentLog[] }) {
    if (!logs.length) {
        return <p style={{ color: '#475569', fontSize: '0.875rem' }}>No tracking events yet.</p>;
    }

    const sorted = [...logs].sort(
        (a, b) => new Date(b.logged_at).getTime() - new Date(a.logged_at).getTime()
    );

    return (
        <div className="relative">
            <div style={{
                position: 'absolute',
                left: '7px',
                top: '8px',
                bottom: '8px',
                width: '1px',
                background: '#334155',
            }} />
            <div className="space-y-5 pl-6">
                {sorted.map((log, idx) => {
                    const isLatest = idx === 0;
                    const dotColor = isLatest ? '#06b6d4' : (shipmentStatusColors[log.status]?.text ?? '#475569');
                    return (
                        <div key={log.id} className="relative">
                            <div style={{
                                position: 'absolute',
                                left: '-24px',
                                top: '4px',
                                width: '8px',
                                height: '8px',
                                borderRadius: '50%',
                                background: dotColor,
                                boxShadow: isLatest ? `0 0 8px ${dotColor}` : 'none',
                            }} />
                            <div className="flex items-center gap-2 flex-wrap">
                                <StatusBadge status={log.status} />
                                {log.location && (
                                    <span style={{ color: '#64748b', fontSize: '0.72rem' }}>
                                        {log.location}
                                    </span>
                                )}
                            </div>
                            <p style={{ color: '#94a3b8', fontSize: '0.75rem', marginTop: '3px' }}>
                                {log.message}
                            </p>
                            <p style={{ color: '#475569', fontSize: '0.65rem', marginTop: '2px' }}>
                                {formatDate(log.logged_at)}
                            </p>
                        </div>
                    );
                })}
            </div>
        </div>
    );
}

function RowExpansion({ row, cachedLogs }: {
    row: Transaction;
    cachedLogs: ShipmentLog[] | 'loading' | undefined;
}) {
    const s = row.shipment;

    const timeline = () => {
        if (!s) return <p style={{ color: '#475569', fontSize: '0.875rem' }}>No shipment assigned yet.</p>;
        if (cachedLogs === 'loading') return (
            <div className="flex items-center gap-2" style={{ color: '#475569', fontSize: '0.8rem' }}>
                <span style={{
                    width: '12px', height: '12px', borderRadius: '50%',
                    border: '2px solid #334155', borderTopColor: '#06b6d4',
                    display: 'inline-block', animation: 'spin 0.8s linear infinite',
                }} />
                Loading tracking history…
            </div>
        );
        if (Array.isArray(cachedLogs)) return <TrackingTimeline logs={cachedLogs} />;
        return <p style={{ color: '#475569', fontSize: '0.875rem' }}>Expand to load tracking history.</p>;
    };

    return (
        <div style={{ background: '#080f1e', padding: '1.5rem', borderTop: '1px solid #1e293b' }}>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-8">

                {/* Left: transaction + shipment details */}
                <div className="space-y-5">
                    <section>
                        <p style={{ color: '#475569', fontSize: '0.6rem', textTransform: 'uppercase', letterSpacing: '0.1em', marginBottom: '0.5rem' }}>
                            Transaction
                        </p>
                        <dl style={{ display: 'grid', gridTemplateColumns: 'auto 1fr', gap: '0.35rem 1.25rem', fontSize: '0.8rem' }}>
                            <dt style={{ color: '#64748b' }}>Description</dt>
                            <dd style={{ color: '#e2e8f0' }}>{row.description}</dd>
                            <dt style={{ color: '#64748b' }}>Account</dt>
                            <dd style={{ color: '#e2e8f0', textTransform: 'uppercase' }}>{row.account_type}</dd>
                            <dt style={{ color: '#64748b' }}>Brand</dt>
                            <dd style={{ color: '#e2e8f0' }}>{row.order_origin}</dd>
                            <dt style={{ color: '#64748b' }}>Amount</dt>
                            <dd style={{ color: '#06b6d4', fontWeight: 700 }}>{formatCurrency(row.amount)}</dd>
                            <dt style={{ color: '#64748b' }}>Time</dt>
                            <dd style={{ color: '#e2e8f0' }}>{formatDate(row.timestamp)}</dd>
                        </dl>
                    </section>

                    {s && (
                        <section>
                            <p style={{ color: '#475569', fontSize: '0.6rem', textTransform: 'uppercase', letterSpacing: '0.1em', marginBottom: '0.5rem' }}>
                                Shipment
                            </p>
                            <dl style={{ display: 'grid', gridTemplateColumns: 'auto 1fr', gap: '0.35rem 1.25rem', fontSize: '0.8rem' }}>
                                <dt style={{ color: '#64748b' }}>Carrier</dt>
                                <dd style={{ color: '#e2e8f0', textTransform: 'uppercase' }}>{s.carrier}</dd>
                                <dt style={{ color: '#64748b' }}>Tracking #</dt>
                                <dd style={{ color: '#e2e8f0', fontFamily: 'monospace', fontSize: '0.75rem' }}>{s.tracking_number}</dd>
                                <dt style={{ color: '#64748b' }}>Status</dt>
                                <dd><StatusBadge status={s.status} /></dd>
                                <dt style={{ color: '#64748b' }}>Est. Delivery</dt>
                                <dd style={{ color: '#e2e8f0' }}>
                                    {s.estimated_delivery ? formatDate(s.estimated_delivery) : '—'}
                                </dd>
                            </dl>
                        </section>
                    )}
                </div>

                {/* Right: tracking timeline */}
                <div>
                    <p style={{ color: '#475569', fontSize: '0.6rem', textTransform: 'uppercase', letterSpacing: '0.1em', marginBottom: '0.75rem' }}>
                        Tracking History
                    </p>
                    {timeline()}
                </div>
            </div>
        </div>
    );
}

export default function TransactionTable({
    paginatedData,
    onPageChange,
    onSort,
    detailsCache,
    fetchDetails,
}: TransactionTableProps) {
    const [expandedRows, setExpandedRows] = useState<{ [key: string]: boolean }>({});

    const handlePage = (event: DataTablePageEvent) => {
        onPageChange(event.page + 1, event.rows);
    };

    const handleSort = (event: DataTableSortEvent) => {
        const order = event.sortOrder === 1 ? 'asc' : 'desc';
        onSort(event.sortField as string, order);
    };

    const handleRowToggle = (e: DataTableRowToggleEvent) => {
        const next = e.data as { [key: string]: boolean };
        setExpandedRows(next);
        // Fetch details for any row being opened
        Object.entries(next).forEach(([idStr, open]) => {
            if (open) fetchDetails(Number(idStr));
        });
    };

    return (
        <div className="rounded-xl overflow-hidden" style={{ border: '1px solid #334155' }}>
            <DataTable
                value={paginatedData.data}
                dataKey="id"
                lazy
                paginator
                first={(paginatedData.current_page - 1) * paginatedData.per_page}
                rows={paginatedData.per_page}
                totalRecords={paginatedData.total}
                onPage={handlePage}
                onSort={handleSort}
                rowsPerPageOptions={[5, 10, 20]}
                sortField="timestamp"
                sortOrder={-1}
                stripedRows
                responsiveLayout="scroll"
                emptyMessage="No transactions found."
                className="p-datatable-sm"
                expandedRows={expandedRows}
                onRowToggle={handleRowToggle}
                rowExpansionTemplate={(row) => {
                    const tx = row as Transaction;
                    return <RowExpansion row={tx} cachedLogs={detailsCache[tx.id]} />;
                }}
            >
                <Column expander style={{ width: '3rem' }} />
                <Column field="id" header="ID" sortable style={{ width: '70px' }} />
                <Column
                    field="timestamp"
                    header="Time"
                    sortable
                    style={{ width: '200px' }}
                    body={(row: Transaction) => formatDate(row.timestamp)}
                />
                <Column
                    field="amount"
                    header="Amount"
                    sortable
                    body={(row: Transaction) => (
                        <span className="font-semibold" style={{ color: '#06b6d4' }}>
                            {formatCurrency(row.amount)}
                        </span>
                    )}
                />
                <Column
                    field="description"
                    header="Description"
                    body={(row: Transaction) => (
                        <span>{row.description.slice(0, 10)}</span>
                    )}
                />
                <Column
                    field="account_type"
                    header="Account"
                    sortable
                    body={(row: Transaction) => (
                        <span className="uppercase">{row.account_type}</span>
                    )}
                />
                <Column field="order_origin" header="Brand" sortable />
                <Column
                    header="Shipment"
                    body={(row: Transaction) => <ShipmentCell shipment={row.shipment} />}
                />
            </DataTable>
        </div>
    );
}
