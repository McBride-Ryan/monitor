interface Props {
    brandTotals: Record<string, number>;
}

export default function BrandSummaryCard({ brandTotals }: Props) {
    const sortedBrands = Object.entries(brandTotals).sort(([, a], [, b]) => b - a);
    const maxTotal = sortedBrands[0]?.[1] ?? 1;

    const formatCurrency = (amount: number) =>
        new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(amount);

    return (
        <div className="rounded-xl p-6" style={{ background: '#1e293b', border: '1px solid #334155' }}>
            <p className="text-xs font-medium uppercase tracking-widest mb-4" style={{ color: '#64748b' }}>
                By Brand
            </p>
            <div className="space-y-4">
                {sortedBrands.map(([brand, total], index) => {
                    const pct = (total / maxTotal) * 100;
                    return (
                        <div key={brand}>
                            <div className="flex items-center justify-between mb-1">
                                <div className="flex items-center gap-2">
                                    <span className="text-xs font-bold w-4" style={{ color: '#475569' }}>
                                        {index + 1}
                                    </span>
                                    <span className="text-sm font-medium" style={{ color: '#e2e8f0' }}>
                                        {brand}
                                    </span>
                                </div>
                                <span className="text-sm font-semibold" style={{ color: '#cbd5e1' }}>
                                    {formatCurrency(total)}
                                </span>
                            </div>
                            <div className="h-1 rounded-full" style={{ background: '#334155' }}>
                                <div
                                    className="h-1 rounded-full"
                                    style={{ width: `${pct}%`, background: '#06b6d4' }}
                                />
                            </div>
                        </div>
                    );
                })}
            </div>
        </div>
    );
}
