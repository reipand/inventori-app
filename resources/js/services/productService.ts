import axios from 'axios';
import type { Category } from './categoryService';

export interface Product {
    id: string;
    sku: string;
    name: string;
    category_id: string;
    category?: Category;
    unit: string;
    buy_price: number;
    sell_price: number;
    min_stock: number;
    current_stock: number;
    created_at: string;
    updated_at: string;
}

export interface ProductPayload {
    sku: string;
    name: string;
    category_id: string;
    unit: string;
    buy_price: number;
    sell_price: number;
    min_stock: number;
}

export interface ProductListParams {
    search?: string;
    category_id?: string;
    page?: number;
}

export interface PaginatedProducts {
    data: Product[];
    current_page: number;
    last_page: number;
    total: number;
    per_page: number;
}

interface ApiResponse<T> {
    success: boolean;
    data: T;
}

export type StockStatus = 'normal' | 'rendah' | 'habis';

export function getStockStatus(product: Pick<Product, 'current_stock' | 'min_stock'>): StockStatus {
    if (product.current_stock === 0) return 'habis';
    if (product.current_stock <= product.min_stock) return 'rendah';
    return 'normal';
}

export async function getProducts(params: ProductListParams = {}): Promise<PaginatedProducts> {
    const response = await axios.get<ApiResponse<PaginatedProducts>>('/api/products', { params });
    return response.data.data;
}

export async function getProduct(id: string): Promise<Product> {
    const response = await axios.get<ApiResponse<Product>>(`/api/products/${id}`);
    return response.data.data;
}

export async function createProduct(payload: ProductPayload): Promise<Product> {
    const response = await axios.post<ApiResponse<Product>>('/api/products', payload);
    return response.data.data;
}

export async function updateProduct(id: string, payload: ProductPayload): Promise<Product> {
    const response = await axios.put<ApiResponse<Product>>(`/api/products/${id}`, payload);
    return response.data.data;
}

export async function deleteProduct(id: string): Promise<void> {
    await axios.delete(`/api/products/${id}`);
}
