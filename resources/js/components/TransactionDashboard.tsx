import { useState, useMemo } from 'react';
import { Transaction } from '../types/transaction';
import { useTransactions } from '../hooks/useTransactions';
import { useConnectionStatus } from '../hooks/useConnectionStatus';
import ConnectionStatusBanner from './ConnectionStatusBanner';
import TotalSumCard from './TotalSumCard';
import BrandSummaryCard from './BrandSummaryCard';
import FilterSidebar from './FilterSidebar';
import TransactionTable from './TransactionTable';

interface Props {
    initialTransactions: Transaction[];
}

export default function TransactionDashboard({ initialTransactions }: Props) {
    const { transactions } = useTransactions(initialTransactions);
    const status = useConnectionStatus();

    const [accountType, setAccountType] = useState<string | null>(null);
    const [orderOrigins, setOrderOrigins] = useState<string[]>([]);

    const filtered = useMemo(() => {
        return transactions.filter(t => {
            if (accountType && t.account_type !== accountType) return false;
            if (orderOrigins.length > 0 && !orderOrigins.includes(t.order_origin)) return false;
            return true;
        });
    }, [transactions, accountType, orderOrigins]);

    return (
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
                            accountType={accountType}
                            onAccountTypeChange={setAccountType}
                            orderOrigins={orderOrigins}
                            onOrderOriginsChange={setOrderOrigins}
                        />
                        <TotalSumCard transactions={filtered} />
                        <BrandSummaryCard transactions={filtered} />
                    </div>

                    {/* Main content */}
                    <div className="lg:col-span-3">
                        <TransactionTable transactions={filtered} />
                    </div>
                </div>
            </main>
        </div>
    );
}
