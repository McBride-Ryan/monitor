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
        <div className="rounded-xl p-6" style={{
            background: '#1e293b',
            borderTop: '2px solid #06b6d4',
            border: '1px solid #334155',
        }}>
            <p className="text-xs font-medium uppercase tracking-widest" style={{ color: '#64748b' }}>
                Total Amount
            </p>
            <p className="text-3xl font-bold mt-2" style={{ color: '#06b6d4' }}>
                {formatted}
            </p>
        </div>
    );
}
