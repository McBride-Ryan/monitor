import { Head } from '@inertiajs/react';
import { PaginatedTransactions, FilterState, TransactionSummary } from '../types/transaction';
import TransactionDashboard from '../components/TransactionDashboard';

interface Props {
    transactions: PaginatedTransactions;
    filters: FilterState;
    summary: TransactionSummary;
}

export default function Dashboard({ transactions, filters, summary }: Props) {
    return (
        <>
            <Head title="Transaction Monitor" />
            <TransactionDashboard
                initialData={transactions}
                initialFilters={filters}
                summary={summary}
            />
        </>
    );
}
