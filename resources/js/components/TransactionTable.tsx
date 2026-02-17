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
        <div
            className="rounded-xl overflow-hidden"
            style={{ border: "1px solid #334155" }}
        >
            <DataTable
                value={paginatedData.data}
                lazy
                paginator
                first={
                    (paginatedData.current_page - 1) * paginatedData.per_page
                }
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
            >
                <Column
                    field="id"
                    header="ID"
                    sortable
                    style={{ width: "70px" }}
                />
                <Column
                    field="timestamp"
                    header="Time"
                    sortable
                    style={{ width: "200px" }}
                    body={(row: Transaction) => formatDate(row.timestamp)}
                />
                <Column
                    field="amount"
                    header="Amount"
                    sortable
                    body={(row: Transaction) => (
                        <span
                            className="font-semibold"
                            style={{ color: "#06b6d4" }}
                        >
                            {formatCurrency(row.amount)}
                        </span>
                    )}
                />
                <Column
                    field="description"
                    header="Description"
                    body={(row: Transaction) => (
                        <span className="">{row.description.slice(0, 20)}</span>
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
                    header="Status"
                    body={(row: Transaction) => {
                        const log = row.logs?.[0];
                        return log ? (
                            <span
                                className={`text-xs font-medium px-2 py-1 rounded-full ${
                                    log.status === "success"
                                        ? "bg-green-900 text-green-300"
                                        : "bg-red-900 text-red-300"
                                }`}
                            >
                                {log.status}
                            </span>
                        ) : (
                            "—"
                        );
                    }}
                />
            </DataTable>
        </div>
    );
}
