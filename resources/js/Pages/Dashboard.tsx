import { Head } from '@inertiajs/react';
import { Transaction } from '../types/transaction';
import AppLayout from '../components/AppLayout';
import TransactionDashboard from '../components/TransactionDashboard';

interface Props {
    transactions: Transaction[];
}

export default function Dashboard({ transactions }: Props) {
    return (
        <AppLayout title="Transaction Monitor">
            <Head title="Transaction Monitor" />
            <TransactionDashboard initialTransactions={transactions} />
        </AppLayout>
    );
}
