import { Dropdown } from 'primereact/dropdown';
import { MultiSelect } from 'primereact/multiselect';

interface FilterSidebarProps {
    accountType: string | null;
    onAccountTypeChange: (value: string | null) => void;
    orderOrigins: string[];
    onOrderOriginsChange: (value: string[]) => void;
}

const accountTypes = [
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
        <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-4">
            <p className="text-sm text-gray-500 font-medium uppercase tracking-wide">Filters</p>

            <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Account Type</label>
                <Dropdown
                    value={accountType}
                    options={accountTypes}
                    onChange={(e) => onAccountTypeChange(e.value)}
                    placeholder="All"
                    className="w-full"
                />
            </div>

            <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Brand</label>
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
