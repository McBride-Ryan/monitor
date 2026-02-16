import { DataTable, DataTablePageEvent, DataTableSortEvent } from 'primereact/datatable';
import { Column } from 'primereact/column';
import { Transaction, PaginatedTransactions } from '../types/transaction';

interface TransactionTableProps {
    paginatedData: PaginatedTransactions;
    onPageChange: (page: number, rows: number) => void;
    onSort: (field: string, order: 'asc' | 'desc') => void;
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

export default function TransactionTable({
    paginatedData,
    onPageChange,
    onSort
}: TransactionTableProps) {
    const handlePage = (event: DataTablePageEvent) => {
        onPageChange(event.page + 1, event.rows);
    };

    const handleSort = (event: DataTableSortEvent) => {
        const order = event.sortOrder === 1 ? 'asc' : 'desc';
        onSort(event.sortField as string, order);
    };

    return (
        <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <DataTable
                value={paginatedData.data}
                lazy
                paginator
                first={(paginatedData.current_page - 1) * paginatedData.per_page}
                rows={paginatedData.per_page}
                totalRecords={paginatedData.total}
                onPage={handlePage}
                onSort={handleSort}
                rowsPerPageOptions={[10, 20, 50]}
                sortField="timestamp"
                sortOrder={-1}
                stripedRows
                responsiveLayout="scroll"
                emptyMessage="No transactions found."
                className="p-datatable-sm"
            >
                <Column
                    field="id"
                    header="ID"
                    sortable
                    style={{ width: '70px' }}
                />
                <Column
                    field="timestamp"
                    header="Time"
                    sortable
                    body={(row: Transaction) => formatDate(row.timestamp)}
                />
                <Column
                    field="amount"
                    header="Amount"
                    sortable
                    body={(row: Transaction) => (
                        <span className="font-semibold">{formatCurrency(row.amount)}</span>
                    )}
                />
                <Column field="description" header="Description" />
                <Column
                    field="account_type"
                    header="Account"
                    sortable
                    body={(row: Transaction) => (
                        <span className="capitalize">{row.account_type}</span>
                    )}
                />
                <Column
                    field="order_origin"
                    header="Brand"
                    sortable
                />
                <Column
                    header="Status"
                    body={(row: Transaction) => {
                        const log = row.logs?.[0];
                        return log ? (
                            <span className={`text-xs font-medium px-2 py-1 rounded-full ${
                                log.status === 'success'
                                    ? 'bg-green-100 text-green-700'
                                    : 'bg-red-100 text-red-700'
                            }`}>
                                {log.status}
                            </span>
                        ) : '—';
                    }}
                />
            </DataTable>
        </div>
    );
}
