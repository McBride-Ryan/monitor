interface Props {
    brandTotals: Record<string, number>;
}

export default function BrandSummaryCard({ brandTotals }: Props) {
    const sortedBrands = Object.entries(brandTotals).sort(([, a], [, b]) => b - a);

    const formatCurrency = (amount: number) => {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(amount);
    };

    return (
        <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <p className="text-sm text-gray-500 font-medium uppercase tracking-wide mb-3">By Brand</p>
            <div className="space-y-3">
                {sortedBrands.map(([brand, total]) => (
                    <div key={brand} className="flex items-center justify-between">
                        <span className="font-medium text-gray-900">{brand}</span>
                        <span className="font-semibold text-gray-700">
                            {formatCurrency(total)}
                        </span>
                    </div>
                ))}
            </div>
        </div>
    );
}
