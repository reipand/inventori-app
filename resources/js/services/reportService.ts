import axios from 'axios';

export interface StockSummaryProduct {
    id: string;
    sku: string;
    name: string;
    current_stock: number;
    min_stock: number;
    buy_price: number;
    stock_status: string;
}

export interface StockSummaryResponse {
    products: StockSummaryProduct[];
    total_value: number;
}

export async function getStockSummary(): Promise<StockSummaryResponse> {
    const r = await axios.get<{ data: StockSummaryResponse }>('/api/reports/stock-summary');
    return r.data.data;
}

export async function exportReport(params: Record<string, string>): Promise<Blob> {
    const r = await axios.get('/api/reports/export', { params, responseType: 'blob' });
    return r.data as Blob;
}
