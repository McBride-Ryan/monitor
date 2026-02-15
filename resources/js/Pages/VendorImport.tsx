import { Head, router } from '@inertiajs/react';
import { useState, useRef } from 'react';
import { Dropdown } from 'primereact/dropdown';
import { VendorSchemaMapping, ImportPreviewRow, ImportResult } from '../types/vendor';
import SchemaMappingEditor from '../components/SchemaMappingEditor';
import ImportPreviewTable from '../components/ImportPreviewTable';

interface Props {
    mappings: VendorSchemaMapping[];
    vendors: string[];
}

export default function VendorImport({ mappings, vendors }: Props) {
    const [vendor, setVendor] = useState<string | null>(vendors[0] ?? null);
    const [file, setFile] = useState<File | null>(null);
    const [preview, setPreview] = useState<ImportPreviewRow[] | null>(null);
    const [result, setResult] = useState<ImportResult | null>(null);
    const [loading, setLoading] = useState(false);
    const fileInputRef = useRef<HTMLInputElement>(null);

    const vendorOptions = vendors.map(v => ({ label: v, value: v }));

    const handlePreview = async () => {
        if (!file || !vendor) return;
        setLoading(true);
        setResult(null);

        const formData = new FormData();
        formData.append('file', file);
        formData.append('vendor', vendor);

        try {
            const response = await fetch('/vendor-import/preview', {
                method: 'POST',
                body: formData,
                headers: { 'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '' },
            });
            const data = await response.json();
            setPreview(data.preview);
        } finally {
            setLoading(false);
        }
    };

    const handleImport = async () => {
        if (!file || !vendor) return;
        setLoading(true);

        const formData = new FormData();
        formData.append('file', file);
        formData.append('vendor', vendor);

        try {
            const response = await fetch('/vendor-import/import', {
                method: 'POST',
                body: formData,
                headers: { 'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '' },
            });
            const data: ImportResult = await response.json();
            setResult(data);
        } finally {
            setLoading(false);
        }
    };

    return (
        <>
            <Head title="Vendor Import" />
            <div className="min-h-screen bg-gray-50">
                <header className="bg-white border-b border-gray-200 px-4 sm:px-6 lg:px-8 py-4">
                    <div className="max-w-7xl mx-auto flex items-center justify-between">
                        <h1 className="text-xl font-bold text-gray-900">Vendor Import</h1>
                        <a href="/" className="text-sm text-blue-600 hover:text-blue-800">
                            &larr; Dashboard
                        </a>
                    </div>
                </header>

                <main className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">
                    {/* Upload Panel */}
                    <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <h2 className="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-4">
                            Upload CSV
                        </h2>
                        <div className="flex flex-wrap gap-4 items-end">
                            <div className="w-64">
                                <label className="block text-sm text-gray-600 mb-1">Vendor</label>
                                <Dropdown
                                    value={vendor}
                                    options={vendorOptions}
                                    onChange={e => setVendor(e.value)}
                                    placeholder="Select vendor"
                                    className="w-full"
                                />
                            </div>
                            <div>
                                <label className="block text-sm text-gray-600 mb-1">CSV File</label>
                                <input
                                    ref={fileInputRef}
                                    type="file"
                                    accept=".csv,.txt"
                                    onChange={e => {
                                        setFile(e.target.files?.[0] ?? null);
                                        setPreview(null);
                                        setResult(null);
                                    }}
                                    className="block text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                                />
                            </div>
                            <button
                                onClick={handlePreview}
                                disabled={!file || !vendor || loading}
                                className="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                {loading ? 'Processing...' : 'Preview'}
                            </button>
                            {preview && (
                                <button
                                    onClick={handleImport}
                                    disabled={loading}
                                    className="px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-medium hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    Import
                                </button>
                            )}
                        </div>
                    </div>

                    {/* Import Result */}
                    {result && (
                        <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                            <h3 className="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-3">
                                Import Results
                            </h3>
                            <div className="flex gap-6">
                                <div>
                                    <span className="text-2xl font-bold text-green-600">{result.imported}</span>
                                    <span className="text-sm text-gray-500 ml-1">imported</span>
                                </div>
                                <div>
                                    <span className="text-2xl font-bold text-red-600">{result.skipped}</span>
                                    <span className="text-sm text-gray-500 ml-1">skipped</span>
                                </div>
                                <div>
                                    <span className="text-2xl font-bold text-gray-700">{result.total}</span>
                                    <span className="text-sm text-gray-500 ml-1">total</span>
                                </div>
                            </div>
                        </div>
                    )}

                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <div className="lg:col-span-1">
                            <SchemaMappingEditor mappings={mappings} vendor={vendor} />
                        </div>
                        <div className="lg:col-span-2">
                            {preview && <ImportPreviewTable preview={preview} />}
                        </div>
                    </div>
                </main>
            </div>
        </>
    );
}
