import { Dropdown } from 'primereact/dropdown';
import { MultiSelect } from 'primereact/multiselect';

interface FilterSidebarProps {
    accountType: string | null;
    onAccountTypeChange: (value: string | null) => void;
    orderOrigins: string[];
    onOrderOriginsChange: (value: string[]) => void;
}

const accountTypeOptions = [
    { label: 'All', value: null },
    { label: 'Checking', value: 'checking' },
    { label: 'Savings', value: 'savings' },
    { label: 'Credit', value: 'credit' },
];

const originOptions = [
    { label: 'Brand 1', value: 'Brand_1' },
    { label: 'Brand 2', value: 'Brand_2' },
    { label: 'Brand 3', value: 'Brand_3' },
    { label: 'Brand 4', value: 'Brand_4' },
];

export default function FilterSidebar({
    accountType,
    onAccountTypeChange,
    orderOrigins,
    onOrderOriginsChange,
}: FilterSidebarProps) {
    return (
        <div className="rounded-xl p-6 space-y-4" style={{ background: '#1e293b', border: '1px solid #334155' }}>
            <p className="text-xs font-medium uppercase tracking-widest" style={{ color: '#64748b' }}>Filters</p>

            <div>
                <label className="block text-sm font-medium mb-1" style={{ color: '#94a3b8' }}>
                    Account Type
                </label>
                <Dropdown
                    value={accountType}
                    options={accountTypeOptions}
                    onChange={(e) => onAccountTypeChange(e.value)}
                    optionLabel="label"
                    optionValue="value"
                    placeholder="All"
                    className="w-full"
                />
            </div>

            <div>
                <label className="block text-sm font-medium mb-1" style={{ color: '#94a3b8' }}>
                    Brand
                </label>
                <MultiSelect
                    value={orderOrigins}
                    options={originOptions}
                    onChange={(e) => onOrderOriginsChange(e.value)}
                    placeholder="All Brands"
                    className="w-full"
                    display="chip"
                />
            </div>
        </div>
    );
}
