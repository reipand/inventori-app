import axios from 'axios';
import type { Product } from './productService';

export interface Transaction {
    id: string;
    product_id: string;
    product?: Product;
    type: 'masuk' | 'keluar';
    quantity: number;
    price_per_unit: number;
    supplier_name: string | null;
    transaction_date: string;
    recorded_by: string;
    created_at: string;
}

export interface TransactionInPayload {
    product_id: string;
    quantity: number;
    transaction_date: string;
    supplier_name: string;
    price_per_unit: number;
}

export interface TransactionOutPayload {
    product_id: string;
    quantity: number;
    transaction_date: string;
    price_per_unit: number;
}

export interface TransactionResult {
    transaction: Transaction;
    current_stock: number;
    low_stock_warning: boolean;
}

export interface TransactionListParams {
    type?: 'masuk' | 'keluar' | '';
    product_id?: string;
    start_date?: string;
    end_date?: string;
    page?: number;
}

export interface PaginatedTransactions {
    data: Transaction[];
    current_page: number;
    last_page: number;
    total: number;
    per_page: number;
}

interface ApiResponse<T> {
    success: boolean;
    data: T;
}

export async function getTransactions(params: TransactionListParams = {}): Promise<PaginatedTransactions> {
    const response = await axios.get<ApiResponse<PaginatedTransactions>>('/api/transactions', { params });
    return response.data.data;
}

export async function createTransactionIn(payload: TransactionInPayload): Promise<TransactionResult> {
    const response = await axios.post<ApiResponse<TransactionResult>>('/api/transactions/in', payload);
    return response.data.data;
}

export async function createTransactionOut(payload: TransactionOutPayload): Promise<TransactionResult> {
    const response = await axios.post<ApiResponse<TransactionResult>>('/api/transactions/out', payload);
    return response.data.data;
}
