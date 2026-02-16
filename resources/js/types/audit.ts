export interface DataAuditLog {
    id: number;
    audit_type: string;
    severity: 'info' | 'warning' | 'critical';
    entity_type: string | null;
    entity_id: number | null;
    details: Record<string, any>;
    resolved_at: string | null;
    created_at: string;
    updated_at: string;
}

export interface AuditSummary {
    total_unresolved: number;
    by_severity: {
        critical: number;
        warning: number;
        info: number;
    };
    by_type: {
        price_discrepancy: number;
        broken_asset: number;
        orphaned_product: number;
    };
}

export interface AuditFilters {
    severity?: string;
    type?: string;
    resolved?: string;
}

export interface PaginatedLogs {
    data: DataAuditLog[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: Array<{
        url: string | null;
        label: string;
        active: boolean;
    }>;
}
