import { DataTable } from 'primereact/datatable';
import { Column } from 'primereact/column';
import { VendorSchemaMapping } from '../types/vendor';

interface Props {
    mappings: VendorSchemaMapping[];
    vendor: string | null;
}

export default function SchemaMappingEditor({ mappings, vendor }: Props) {
    const filtered = vendor
        ? mappings.filter(m => m.vendor_name === vendor)
        : mappings;

    return (
        <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div className="px-6 py-4 border-b border-gray-100">
                <h3 className="text-sm font-semibold text-gray-700 uppercase tracking-wide">
                    Column Mappings
                    {vendor && <span className="text-blue-600 ml-2">{vendor}</span>}
                </h3>
            </div>
            <DataTable
                value={filtered}
                stripedRows
                emptyMessage="No mappings configured for this vendor."
                className="p-datatable-sm"
            >
                <Column field="vendor_column" header="Vendor Column" sortable />
                <Column field="erp_column" header="ERP Column" sortable />
                <Column
                    header="Transform"
                    body={(row: VendorSchemaMapping) =>
                        row.transform_rule ? (
                            <span className="text-xs font-medium px-2 py-1 rounded-full bg-blue-100 text-blue-700">
                                {row.transform_rule.type}
                            </span>
                        ) : (
                            <span className="text-gray-400">—</span>
                        )
                    }
                />
            </DataTable>
        </div>
    );
}
