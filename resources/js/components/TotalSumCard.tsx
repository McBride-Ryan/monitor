interface Props {
    totalSum: number;
}

export default function TotalSumCard({ totalSum }: Props) {
    const formatted = new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(totalSum);

    return (
        <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <p className="text-sm text-gray-500 font-medium uppercase tracking-wide">Total Amount</p>
            <p className="text-3xl font-bold text-gray-900 mt-1">
                {formatted}
            </p>
        </div>
    );
}
