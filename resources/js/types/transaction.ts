export interface TransactionLog {
    id: number;
    transaction_id: number;
    origin: string;
    status: string;
    logged_at: string;
}

export interface Transaction {
    id: number;
    timestamp: string;
    amount: string;
    description: string;
    account_type: string;
    order_origin: string;
    created_at: string;
    updated_at: string;
    logs: TransactionLog[];
}

export interface PaginatedTransactions {
    data: Transaction[];
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
    from: number | null;
    to: number | null;
    links: {
        first: string | null;
        last: string | null;
        prev: string | null;
        next: string | null;
    };
}

export interface FilterState {
    account_type: string | null;
    order_origins: string[];
    sort_field: string;
    sort_order: 'asc' | 'desc';
}

export interface TransactionSummary {
    total_sum: number;
    by_brand: Record<string, number>;
}
