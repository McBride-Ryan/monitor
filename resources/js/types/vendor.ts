export interface TransformRule {
    type: 'uppercase' | 'trim' | 'date_format' | 'multiply';
    from?: string;
    to?: string;
    factor?: number;
}

export interface VendorSchemaMapping {
    id: number;
    vendor_name: string;
    vendor_column: string;
    erp_column: string;
    transform_rule: TransformRule | null;
    created_at: string;
    updated_at: string;
}

export interface ComplianceViolation {
    rule_type: string;
    field: string;
    message: string;
}

export interface ImportPreviewRow {
    original: Record<string, string>;
    mapped: Record<string, string>;
    violations: ComplianceViolation[];
}

export interface ImportResult {
    imported: number;
    skipped: number;
    total: number;
    errors: Array<{
        row: number;
        violations: ComplianceViolation[];
    }>;
}
