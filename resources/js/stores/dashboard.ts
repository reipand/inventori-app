import { defineStore } from 'pinia';
import { ref } from 'vue';
import axios from 'axios';
import type { Transaction } from '@/services/transactionService';
import type { Product } from '@/services/productService';

export interface DashboardStats {
    totalProducts: number;
    lowStockCount: number;
    outOfStockCount: number;
    todayTransactions: number;
    todayRevenue: number;
    revenueGrowth: number;
    transactionGrowth: number;
}

export interface ChartPoint {
    label: string;
    masuk: number;
    keluar: number;
}

export const useDashboardStore = defineStore('dashboard', () => {
    const loading = ref(true);
    const stats = ref<DashboardStats>({
        totalProducts: 0,
        lowStockCount: 0,
        outOfStockCount: 0,
        todayTransactions: 0,
        todayRevenue: 0,
        revenueGrowth: 0,
        transactionGrowth: 0,
    });
    const chartData = ref<ChartPoint[]>([]);
    const recentTransactions = ref<Transaction[]>([]);
    const lowStockProducts = ref<Product[]>([]);
    const spotlightProducts = ref<Product[]>([]);

    async function fetchAll() {
        loading.value = true;
        try {
            const today = new Date().toISOString().split('T')[0];
            const yesterday = new Date(Date.now() - 86400000).toISOString().split('T')[0];
            const sevenDaysAgo = new Date(Date.now() - 6 * 86400000).toISOString().split('T')[0];

            const [productsRes, lowStockRes, txTodayRes, txYesterdayRes, txWeekRes] = await Promise.all([
                axios.get('/api/products', { params: { page: 1 } }),
                axios.get('/api/products/low-stock'),
                axios.get('/api/transactions', { params: { start_date: today, end_date: today, page: 1 } }),
                axios.get('/api/transactions', { params: { start_date: yesterday, end_date: yesterday, page: 1 } }),
                axios.get('/api/transactions', { params: { start_date: sevenDaysAgo, end_date: today, page: 1 } }),
            ]);

            const lowStockList: Product[] = lowStockRes.data.data;
            const txToday: Transaction[] = txTodayRes.data.data.data ?? [];
            const txYesterday: Transaction[] = txYesterdayRes.data.data.data ?? [];
            const txWeek: Transaction[] = txWeekRes.data.data.data ?? [];

            const todayRevenue = txToday
                .filter((t) => t.type === 'keluar')
                .reduce((sum, t) => sum + t.price_per_unit * t.quantity, 0);
            const yesterdayRevenue = txYesterday
                .filter((t) => t.type === 'keluar')
                .reduce((sum, t) => sum + t.price_per_unit * t.quantity, 0);

            stats.value = {
                totalProducts: productsRes.data.data.total,
                lowStockCount: lowStockList.filter((p) => p.current_stock > 0).length,
                outOfStockCount: lowStockList.filter((p) => p.current_stock === 0).length,
                todayTransactions: txToday.length,
                todayRevenue,
                revenueGrowth: yesterdayRevenue > 0
                    ? Math.round(((todayRevenue - yesterdayRevenue) / yesterdayRevenue) * 100)
                    : 0,
                transactionGrowth: txYesterday.length > 0
                    ? Math.round(((txToday.length - txYesterday.length) / txYesterday.length) * 100)
                    : 0,
            };

            // Use week transactions for recent list
            recentTransactions.value = txWeek.slice(0, 8);
            lowStockProducts.value = lowStockList.slice(0, 6);
            spotlightProducts.value = productsRes.data.data.data.slice(0, 8);

            // Build chart from week data (no extra API calls)
            chartData.value = buildChartData(txWeek);
        } catch (e) {
            console.error('Dashboard fetch error', e);
        } finally {
            loading.value = false;
        }
    }

    function buildChartData(transactions: Transaction[]): ChartPoint[] {
        const days = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
        return Array.from({ length: 7 }, (_, i) => {
            const d = new Date(Date.now() - (6 - i) * 86400000);
            const dateStr = d.toISOString().split('T')[0];
            const dayTxs = transactions.filter((t) => t.transaction_date.startsWith(dateStr));
            return {
                label: days[d.getDay()],
                masuk: dayTxs.filter((t) => t.type === 'masuk').length,
                keluar: dayTxs.filter((t) => t.type === 'keluar').length,
            };
        });
    }

    return { loading, stats, chartData, recentTransactions, lowStockProducts, spotlightProducts, fetchAll };
});
