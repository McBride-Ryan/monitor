import { useMemo } from 'react';
import { Transaction } from '../types/transaction';

export default function TotalSumCard({ transactions }: { transactions: Transaction[] }) {
    const total = useMemo(() =>
        transactions.reduce((sum, t) => sum + parseFloat(t.amount), 0),
        [transactions]
    );

    return (
        <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <p className="text-sm text-gray-500 font-medium uppercase tracking-wide">Total Amount</p>
            <p className="text-3xl font-bold text-gray-900 mt-1">
                ${total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
            </p>
            <p className="text-sm text-gray-400 mt-1">{transactions.length} transactions</p>
        </div>
    );
}
