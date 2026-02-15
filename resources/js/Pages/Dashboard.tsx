import { Head } from '@inertiajs/react';
import { Transaction } from '../types/transaction';
import TransactionDashboard from '../components/TransactionDashboard';

interface Props {
    transactions: Transaction[];
}

export default function Dashboard({ transactions }: Props) {
    return (
        <>
            <Head title="Transaction Monitor" />
            <TransactionDashboard initialTransactions={transactions} />
        </>
    );
}
