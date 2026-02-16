import { AuditSummary } from '../types/audit';

interface Props {
    summary: AuditSummary;
}

export default function AuditSummaryCards({ summary }: Props) {
    return (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            {/* Total Unresolved */}
            <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 className="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-2">
                    Unresolved Issues
                </h3>
                <p className="text-3xl font-bold text-gray-900">{summary.total_unresolved}</p>
            </div>

            {/* By Severity */}
            <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 className="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">
                    By Severity
                </h3>
                <div className="space-y-2">
                    <div className="flex items-center justify-between">
                        <span className="text-sm font-medium text-red-600">Critical</span>
                        <span className="text-sm font-bold text-gray-900">{summary.by_severity.critical}</span>
                    </div>
                    <div className="flex items-center justify-between">
                        <span className="text-sm font-medium text-yellow-600">Warning</span>
                        <span className="text-sm font-bold text-gray-900">{summary.by_severity.warning}</span>
                    </div>
                    <div className="flex items-center justify-between">
                        <span className="text-sm font-medium text-blue-600">Info</span>
                        <span className="text-sm font-bold text-gray-900">{summary.by_severity.info}</span>
                    </div>
                </div>
            </div>

            {/* By Type - Price & Asset */}
            <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 className="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">
                    Price & Asset
                </h3>
                <div className="space-y-2">
                    <div className="flex items-center justify-between">
                        <span className="text-sm text-gray-600">Price Issues</span>
                        <span className="text-sm font-bold text-gray-900">{summary.by_type.price_discrepancy}</span>
                    </div>
                    <div className="flex items-center justify-between">
                        <span className="text-sm text-gray-600">Broken Assets</span>
                        <span className="text-sm font-bold text-gray-900">{summary.by_type.broken_asset}</span>
                    </div>
                </div>
            </div>

            {/* By Type - Categorization */}
            <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 className="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">
                    Categorization
                </h3>
                <div className="space-y-2">
                    <div className="flex items-center justify-between">
                        <span className="text-sm text-gray-600">Orphaned Products</span>
                        <span className="text-sm font-bold text-gray-900">{summary.by_type.orphaned_product}</span>
                    </div>
                </div>
            </div>
        </div>
    );
}
