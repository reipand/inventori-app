import axios from 'axios';
import type { Product } from './productService';

export interface CartItem {
    product_id: string;
    product_name: string;
    qty: number;
    sell_price: number;
    cogs: number;
    discount_per_item: number;
}

export interface SaleItemPayload {
    product_id: string;
    qty: number;
    sell_price: number;
    cogs: number;
    discount_per_item: number;
}

export interface SalePayload {
    items: SaleItemPayload[];
    payment_method: 'cash' | 'qr';
    amount_paid: number;
    subtotal: number;
    total_discount: number;
    total: number;
}

export interface SaleItem {
    id: string;
    sale_id: string;
    product_id: string;
    qty: number;
    sell_price: number;
    cogs: number;
    discount_per_item: number;
    subtotal: number;
    product?: Product;
}

export interface Sale {
    id: string;
    transaction_date: string;
    subtotal: number;
    total_discount: number;
    total: number;
    payment_method: 'cash' | 'qr';
    amount_paid: number;
    change_amount: number;
    recorded_by: string;
    created_at: string;
    items?: SaleItem[];
}

interface ApiResponse<T> {
    success: boolean;
    data: T;
}

export async function createSale(payload: SalePayload): Promise<Sale> {
    const response = await axios.post<ApiResponse<Sale>>('/api/sales', payload);
    return response.data.data;
}

export async function getSale(id: string): Promise<Sale> {
    const response = await axios.get<ApiResponse<Sale>>(`/api/sales/${id}`);
    return response.data.data;
}
