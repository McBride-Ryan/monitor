import { DataTable } from 'primereact/datatable';
import { Column } from 'primereact/column';
import { ImportPreviewRow } from '../types/vendor';

interface Props {
    preview: ImportPreviewRow[];
}

export default function ImportPreviewTable({ preview }: Props) {
    const erpColumns = preview.length > 0
        ? Object.keys(preview[0].mapped)
        : [];

    return (
        <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div className="px-6 py-4 border-b border-gray-100">
                <h3 className="text-sm font-semibold text-gray-700 uppercase tracking-wide">
                    Import Preview
                </h3>
                <p className="text-xs text-gray-500 mt-1">
                    {preview.length} rows &middot;{' '}
                    {preview.filter(r => r.violations.length > 0).length} with violations
                </p>
            </div>
            <DataTable
                value={preview}
                stripedRows
                paginator
                rows={10}
                rowsPerPageOptions={[10, 25, 50]}
                emptyMessage="No preview data."
                className="p-datatable-sm"
                rowClassName={(row: ImportPreviewRow) =>
                    row.violations.length > 0 ? 'bg-red-50' : ''
                }
            >
                <Column
                    header="#"
                    body={(_row: ImportPreviewRow, options: { rowIndex: number }) => options.rowIndex + 1}
                    style={{ width: '50px' }}
                />
                {erpColumns.map(col => (
                    <Column
                        key={col}
                        header={col}
                        body={(row: ImportPreviewRow) => row.mapped[col] ?? '—'}
                    />
                ))}
                <Column
                    header="Status"
                    body={(row: ImportPreviewRow) =>
                        row.violations.length === 0 ? (
                            <span className="text-xs font-medium px-2 py-1 rounded-full bg-green-100 text-green-700">
                                Valid
                            </span>
                        ) : (
                            <span
                                className="text-xs font-medium px-2 py-1 rounded-full bg-red-100 text-red-700 cursor-help"
                                title={row.violations.map(v => v.message).join('\n')}
                            >
                                {row.violations.length} violation{row.violations.length > 1 ? 's' : ''}
                            </span>
                        )
                    }
                />
            </DataTable>
        </div>
    );
}
