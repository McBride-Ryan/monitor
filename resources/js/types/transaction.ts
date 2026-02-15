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
