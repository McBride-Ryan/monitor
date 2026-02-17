export interface TransactionLog {
    id: number;
    transaction_id: number;
    origin: string;
    status: string;
    logged_at: string;
}

export type ShipmentStatus = 'packing' | 'shipped' | 'out_for_delivery' | 'delivered' | 'exception';
export type Carrier = 'fedex' | 'ups' | 'usps' | 'dhl';

export interface ShipmentLog {
    id: number;
    shipment_id: number;
    status: ShipmentStatus;
    location: string | null;
    message: string;
    logged_at: string;
}

export interface Shipment {
    id: number;
    transaction_id: number;
    carrier: Carrier;
    tracking_number: string;
    status: ShipmentStatus;
    estimated_delivery: string | null;
    logs?: ShipmentLog[];
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
    shipment?: Shipment | null;
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
    shipment_status: string | null;
    sort_field: string;
    sort_order: 'asc' | 'desc';
}

export interface TransactionSummary {
    total_sum: number;
    by_brand: Record<string, number>;
}
