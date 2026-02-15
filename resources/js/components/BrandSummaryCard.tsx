import { useMemo } from 'react';
import { Transaction } from '../types/transaction';

interface BrandSummary {
    brand: string;
    count: number;
    total: number;
}

export default function BrandSummaryCard({ transactions }: { transactions: Transaction[] }) {
    const brands = useMemo(() => {
        const map = new Map<string, BrandSummary>();
        for (const t of transactions) {
            const existing = map.get(t.order_origin);
            if (existing) {
                existing.count++;
                existing.total += parseFloat(t.amount);
            } else {
                map.set(t.order_origin, {
                    brand: t.order_origin,
                    count: 1,
                    total: parseFloat(t.amount),
                });
            }
        }
        return Array.from(map.values()).sort((a, b) => b.total - a.total);
    }, [transactions]);

    return (
        <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <p className="text-sm text-gray-500 font-medium uppercase tracking-wide mb-3">By Brand</p>
            <div className="space-y-3">
                {brands.map(b => (
                    <div key={b.brand} className="flex items-center justify-between">
                        <div>
                            <span className="font-medium text-gray-900">{b.brand}</span>
                            <span className="text-gray-400 text-sm ml-2">({b.count})</span>
                        </div>
                        <span className="font-semibold text-gray-700">
                            ${b.total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                        </span>
                    </div>
                ))}
            </div>
        </div>
    );
}
