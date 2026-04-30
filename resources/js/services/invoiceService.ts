import axios from 'axios';
import type { Product } from './productService';

export interface InvoiceItem {
    id: string;
    invoice_id: string;
    product_id: string;
    qty: number;
    price_input: number;
    price_mode: 'final' | 'before_discount';
    discount_item_type: 'percent' | 'nominal' | null;
    discount_item_value: number;
    price_per_unit_final: number;
    global_discount_portion: number;
    cogs_per_unit: number;
    subtotal_final: number;
    product?: Product;
}

export interface Invoice {
    id: string;
    invoice_number: string;
    supplier_name: string;
    invoice_date: string;
    discount_global_type: 'percent' | 'nominal' | null;
    discount_global_value: number;
    total_before_discount: number;
    total_discount: number;
    total_final: number;
    recorded_by: string;
    created_at: string;
    items_count?: number;
    items?: InvoiceItem[];
}

export interface InvoiceItemPayload {
    product_id: string;
    qty: number;
    price_input: number;
    price_mode: 'final' | 'before_discount';
    discount_item_type?: 'percent' | 'nominal';
    discount_item_value?: number;
}

export interface InvoicePayload {
    invoice_number: string;
    supplier_name: string;
    invoice_date: string;
    discount_global_type?: 'percent' | 'nominal';
    discount_global_value?: number;
    items: InvoiceItemPayload[];
}

export interface InvoiceListParams {
    start_date?: string;
    end_date?: string;
    supplier_name?: string;
    search?: string;
    page?: number;
}

export interface PaginatedInvoices {
    data: Invoice[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

interface ApiResponse<T> {
    success: boolean;
    data: T;
}

export async function getInvoices(params: InvoiceListParams = {}): Promise<PaginatedInvoices> {
    const response = await axios.get<ApiResponse<PaginatedInvoices>>('/api/invoices', { params });
    return response.data.data;
}

export async function createInvoice(payload: InvoicePayload): Promise<Invoice> {
    const response = await axios.post<ApiResponse<Invoice>>('/api/invoices', payload);
    return response.data.data;
}

export async function getInvoice(id: string): Promise<Invoice> {
    const response = await axios.get<ApiResponse<Invoice>>(`/api/invoices/${id}`);
    return response.data.data;
}

export async function deleteInvoice(id: string): Promise<void> {
    await axios.delete(`/api/invoices/${id}`);
}
