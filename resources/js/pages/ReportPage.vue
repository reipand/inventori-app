<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-start justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-gray-900">Laporan Inventaris</h1>
        <p class="mt-1 text-sm text-gray-500">Ringkasan stok dan riwayat transaksi</p>
      </div>
    </div>

    <!-- Tabs -->
    <div class="flex gap-1 border-b border-gray-200 overflow-x-auto scrollbar-none -mb-px">
      <button
        v-for="tab in tabs"
        :key="tab.id"
        @click="activeTab = tab.id"
        class="px-4 py-2.5 text-sm font-medium border-b-2 whitespace-nowrap transition-colors shrink-0"
        :class="activeTab === tab.id
          ? 'border-blue-600 text-blue-600'
          : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
      >
        {{ tab.label }}
      </button>
    </div>

    <!-- ── STOCK SUMMARY TAB ── -->
    <div v-if="activeTab === 'stock'" class="space-y-5">
      <!-- Summary KPI -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="kpi-card">
          <p class="text-sm font-medium text-gray-500">Total Produk</p>
          <p class="mt-2 text-3xl font-bold text-gray-900">{{ loadingStock ? '—' : (stockSummary?.length ?? 0) }}</p>
          <p class="mt-1 text-xs text-gray-400">Semua SKU terdaftar</p>
        </div>
        <div class="kpi-card border-yellow-100">
          <p class="text-sm font-medium text-yellow-700">Stok Rendah / Habis</p>
          <p class="mt-2 text-3xl font-bold text-yellow-700">{{ loadingStock ? '—' : criticalCount }}</p>
          <p class="mt-1 text-xs text-yellow-500">Perlu perhatian segera</p>
        </div>
        <div class="kpi-card">
          <p class="text-sm font-medium text-gray-500">Total Nilai Stok</p>
          <p class="mt-2 text-2xl font-bold text-gray-900">{{ loadingStock ? '—' : fmt(totalValue) }}</p>
          <p class="mt-1 text-xs text-gray-400">Berdasarkan harga beli</p>
        </div>
      </div>

      <!-- Table header with export -->
      <div class="flex flex-wrap items-center justify-between gap-2">
        <p class="text-sm text-gray-500">Ringkasan stok seluruh produk</p>
        <button class="btn-export" @click="doExport('stock')" :disabled="exportingStock">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
          {{ exportingStock ? 'Mengunduh...' : 'Ekspor CSV' }}
        </button>
      </div>

      <!-- Stock table -->
      <div class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">
        <div v-if="loadingStock" class="p-6 space-y-3">
          <div v-for="i in 6" :key="i" class="skeleton h-11 rounded-xl" />
        </div>
        <div v-else-if="stockError" class="p-6 alert-error">{{ stockError }}</div>
        <div v-else class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-gray-100 bg-gray-50/70">
                <th class="th">Nama Produk</th>
                <th class="th">SKU</th>
                <th class="th">Kategori</th>
                <th class="th text-right">Stok</th>
                <th class="th text-right">Min</th>
                <th class="th text-right">Harga Beli</th>
                <th class="th text-right">Nilai Stok</th>
                <th class="th text-center">Status</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="!stockSummary?.length">
                <td colspan="8" class="py-12 text-center text-gray-400 text-sm">Tidak ada data.</td>
              </tr>
              <tr
                v-for="p in (stockSummary ?? [])"
                :key="p.id"
                class="border-b border-gray-50 hover:bg-blue-50/20 transition-colors"
              >
                <td class="td font-medium text-gray-900">{{ p.name }}</td>
                <td class="td font-mono text-xs text-gray-400">{{ p.sku }}</td>
                <td class="td text-gray-500">{{ p.category ?? '—' }}</td>
                <td class="td text-right font-semibold" :class="p.current_stock === 0 ? 'text-red-600' : p.current_stock <= p.min_stock ? 'text-yellow-700' : 'text-gray-900'">
                  {{ p.current_stock }}
                </td>
                <td class="td text-right text-gray-400">{{ p.min_stock }}</td>
                <td class="td text-right text-gray-700">{{ fmt(p.buy_price) }}</td>
                <td class="td text-right font-semibold text-gray-900">{{ fmt(p.current_stock * p.buy_price) }}</td>
                <td class="td text-center">
                  <span v-if="p.current_stock === 0" class="badge badge-red">Habis</span>
                  <span v-else-if="p.current_stock <= p.min_stock" class="badge badge-yellow">Rendah</span>
                  <span v-else class="badge badge-green">Normal</span>
                </td>
              </tr>
            </tbody>
            <tfoot v-if="stockSummary?.length">
              <tr class="border-t border-gray-200 bg-gray-50/70">
                <td colspan="6" class="px-4 py-3 text-right text-sm font-semibold text-gray-700">Total Nilai Stok</td>
                <td class="px-4 py-3 text-right text-sm font-bold text-blue-700">{{ fmt(totalValue) }}</td>
                <td></td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>

    <!-- ── TRANSACTIONS TAB ── -->
    <div v-if="activeTab === 'transactions'" class="space-y-5">
      <!-- Filter card -->
      <div class="rounded-2xl border border-gray-200 bg-white shadow-sm p-5 space-y-4">
        <p class="text-sm font-semibold text-gray-700">Filter Transaksi</p>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
          <div class="field">
            <label class="field-label">Tanggal Awal</label>
            <input v-model="filters.start_date" type="date" class="input-base" @change="onDateChange" />
          </div>
          <div class="field">
            <label class="field-label">Tanggal Akhir</label>
            <input v-model="filters.end_date" type="date" class="input-base" @change="onDateChange" />
          </div>
          <div class="field">
            <label class="field-label">Jenis Transaksi</label>
            <select v-model="filters.type" class="input-base">
              <option value="">Semua</option>
              <option value="masuk">Masuk</option>
              <option value="keluar">Keluar</option>
            </select>
          </div>
          <div class="field">
            <label class="field-label">Produk</label>
            <select v-model="filters.product_id" class="input-base">
              <option value="">Semua Produk</option>
              <option v-for="p in allProducts" :key="p.id" :value="p.id">{{ p.name }}</option>
            </select>
          </div>
        </div>
        <div v-if="dateError" class="text-xs text-red-600">{{ dateError }}</div>
        <div class="flex flex-wrap items-center gap-2 justify-end pt-1 border-t border-gray-100">
          <button class="btn-outline-sm" @click="resetFilters">Reset</button>
          <button class="btn-primary-sm" @click="applyFilters" :disabled="!!dateError">Terapkan Filter</button>
          <button class="btn-export" @click="doExport('transactions')" :disabled="exportingTx || !!dateError">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            {{ exportingTx ? 'Mengunduh...' : 'Ekspor CSV' }}
          </button>
        </div>
      </div>

      <!-- Tx summary chips -->
      <div v-if="!loadingTx && transactions.length" class="flex gap-3 flex-wrap">
        <div class="chip chip-blue">
          <span class="font-semibold">{{ transactions.length }}</span> transaksi ditampilkan
        </div>
        <div class="chip chip-green">
          <span class="font-semibold">{{ transactions.filter(t => t.type === 'masuk').length }}</span> masuk
        </div>
        <div class="chip chip-red">
          <span class="font-semibold">{{ transactions.filter(t => t.type === 'keluar').length }}</span> keluar
        </div>
      </div>

      <!-- Tx table -->
      <div class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">
        <div v-if="loadingTx" class="p-6 space-y-3">
          <div v-for="i in 6" :key="i" class="skeleton h-11 rounded-xl" />
        </div>
        <div v-else-if="txError" class="p-6 alert-error">{{ txError }}</div>
        <div v-else class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-gray-100 bg-gray-50/70">
                <th class="th">Tanggal</th>
                <th class="th">Produk</th>
                <th class="th text-center">Jenis</th>
                <th class="th text-right">Jumlah</th>
                <th class="th text-right">Harga/Unit</th>
                <th class="th text-right">Total</th>
                <th class="th">Supplier</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="transactions.length === 0">
                <td colspan="7" class="py-12 text-center text-gray-400 text-sm">Tidak ada transaksi ditemukan.</td>
              </tr>
              <tr
                v-for="tx in transactions"
                :key="tx.id"
                class="border-b border-gray-50 hover:bg-gray-50/60 transition-colors"
              >
                <td class="td text-gray-500 whitespace-nowrap text-xs">{{ fmtDate(tx.transaction_date) }}</td>
                <td class="td font-medium text-gray-900">{{ tx.product?.name ?? '—' }}</td>
                <td class="td text-center">
                  <span v-if="tx.type === 'masuk'" class="badge badge-green">↓ Masuk</span>
                  <span v-else class="badge badge-red">↑ Keluar</span>
                </td>
                <td class="td text-right font-semibold text-gray-900">{{ tx.quantity }}</td>
                <td class="td text-right text-gray-600">{{ fmt(tx.price_per_unit) }}</td>
                <td class="td text-right font-semibold text-gray-900">{{ fmt(tx.quantity * tx.price_per_unit) }}</td>
                <td class="td text-gray-500 text-xs">{{ tx.supplier_name ?? '—' }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="totalTxPages > 1" class="flex items-center justify-between px-4 py-3 border-t border-gray-100 bg-gray-50/50">
          <p class="text-xs text-gray-500">Halaman {{ txPage }} dari {{ totalTxPages }}</p>
          <div class="flex gap-1.5">
            <button class="btn-page" :disabled="txPage <= 1" @click="fetchTx(txPage - 1)">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </button>
            <button class="btn-page" :disabled="txPage >= totalTxPages" @click="fetchTx(txPage + 1)">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import axios from 'axios';
import { getProducts, type Product } from '@/services/productService';
import { getTransactions, type Transaction } from '@/services/transactionService';

const tabs = [
  { id: 'stock', label: 'Ringkasan Stok' },
  { id: 'transactions', label: 'Riwayat Transaksi' },
];
const activeTab = ref<'stock' | 'transactions'>('stock');

// ── Stock Summary ──
interface StockProduct {
  id: string; sku: string; name: string; category?: string;
  unit: string; current_stock: number; min_stock: number; buy_price: number;
}
const stockSummary = ref<StockProduct[]>([]);
const loadingStock = ref(false);
const stockError = ref('');
const exportingStock = ref(false);

const totalValue = computed(() =>
  (stockSummary.value ?? []).reduce((s, p) => s + p.current_stock * p.buy_price, 0)
);
const criticalCount = computed(() =>
  (stockSummary.value ?? []).filter((p) => p.current_stock <= p.min_stock).length
);

async function fetchStock() {
  loadingStock.value = true; stockError.value = '';
  try {
    const r = await axios.get<{ data: { items: StockProduct[] } }>('/api/reports/stock-summary');
    stockSummary.value = r.data.data.items ?? [];
  } catch { stockError.value = 'Gagal memuat ringkasan stok.'; }
  finally { loadingStock.value = false; }
}

// ── Transactions ──
const allProducts = ref<Product[]>([]);
const transactions = ref<Transaction[]>([]);
const loadingTx = ref(false);
const txError = ref('');
const txPage = ref(1);
const totalTxPages = ref(1);
const exportingTx = ref(false);
const dateError = ref('');
const filters = ref({ start_date: '', end_date: '', type: '' as '' | 'masuk' | 'keluar', product_id: '' });

function onDateChange() {
  dateError.value = '';
  if (filters.value.start_date && filters.value.end_date && filters.value.start_date > filters.value.end_date)
    dateError.value = 'Tanggal awal tidak boleh lebih besar dari tanggal akhir.';
}

async function fetchTx(page = 1) {
  if (dateError.value) return;
  loadingTx.value = true; txError.value = '';
  try {
    const params: Record<string, string | number> = { page };
    if (filters.value.start_date) params.start_date = filters.value.start_date;
    if (filters.value.end_date) params.end_date = filters.value.end_date;
    if (filters.value.type) params.type = filters.value.type;
    if (filters.value.product_id) params.product_id = filters.value.product_id;
    const r = await getTransactions(params);
    transactions.value = r.data;
    txPage.value = r.current_page;
    totalTxPages.value = r.last_page;
  } catch { txError.value = 'Gagal memuat transaksi.'; }
  finally { loadingTx.value = false; }
}

function applyFilters() { if (!dateError.value) fetchTx(1); }
function resetFilters() {
  filters.value = { start_date: '', end_date: '', type: '', product_id: '' };
  dateError.value = '';
  fetchTx(1);
}

async function doExport(type: 'stock' | 'transactions') {
  if (type === 'stock') exportingStock.value = true;
  else exportingTx.value = true;
  try {
    const params: Record<string, string> = { type };
    if (type === 'transactions') {
      if (filters.value.start_date) params.start_date = filters.value.start_date;
      if (filters.value.end_date) params.end_date = filters.value.end_date;
      if (filters.value.type) params.transaction_type = filters.value.type;
      if (filters.value.product_id) params.product_id = filters.value.product_id;
    }
    const r = await axios.get('/api/reports/export', { params, responseType: 'blob' });
    const date = new Date().toISOString().split('T')[0];
    let filename = `laporan-${type}-${date}.csv`;
    const disp = r.headers['content-disposition'] as string | undefined;
    if (disp) { const m = disp.match(/filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/); if (m?.[1]) filename = m[1].replace(/['"]/g, ''); }
    const url = URL.createObjectURL(new Blob([r.data]));
    const a = document.createElement('a'); a.href = url; a.download = filename;
    document.body.appendChild(a); a.click(); document.body.removeChild(a); URL.revokeObjectURL(url);
  } catch {
    // silent — user sees no change
  } finally {
    if (type === 'stock') exportingStock.value = false;
    else exportingTx.value = false;
  }
}

function fmt(v: number) {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(v);
}
function fmtDate(d: string) {
  return d ? new Date(d).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) : '—';
}

watch(activeTab, (tab) => {
  if (tab === 'stock' && !stockSummary.value.length) fetchStock();
  if (tab === 'transactions' && !transactions.value.length) fetchTx(1);
});

onMounted(() => {
  fetchStock();
  getProducts({ page: 1 }).then((r) => { allProducts.value = r.data; }).catch(() => {});
  fetchTx(1);
});
</script>

<style scoped>
@reference "../../css/app.css";
.kpi-card { @apply rounded-2xl border border-gray-200 bg-white p-5 shadow-sm; }
.th { @apply px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide whitespace-nowrap; }
.td { @apply px-4 py-3.5; }
.field { @apply space-y-1.5; }
.field-label { @apply text-xs font-medium text-gray-600; }
.input-base { @apply flex h-9 w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm text-gray-900 placeholder:text-gray-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/30 focus-visible:border-blue-400 transition-all; }
.btn-primary-sm { @apply inline-flex items-center justify-center rounded-xl bg-blue-600 text-white text-xs font-medium h-8 px-3 hover:bg-blue-700 disabled:opacity-50 transition-all; }
.btn-outline-sm { @apply inline-flex items-center justify-center rounded-xl border border-gray-200 text-xs font-medium h-8 px-3 text-gray-600 hover:bg-gray-50 disabled:opacity-50 transition-all; }
.btn-export { @apply inline-flex items-center gap-1.5 rounded-xl border border-gray-200 text-xs font-medium h-8 px-3 text-gray-600 hover:bg-gray-50 hover:border-gray-300 disabled:opacity-50 transition-all; }
.btn-page { @apply inline-flex items-center justify-center w-8 h-8 rounded-lg border border-gray-200 text-xs font-medium text-gray-600 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed transition-all; }
.badge { @apply inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold; }
.badge-green { @apply bg-green-50 text-green-700 ring-1 ring-green-200; }
.badge-yellow { @apply bg-yellow-50 text-yellow-700 ring-1 ring-yellow-200; }
.badge-red { @apply bg-red-50 text-red-700 ring-1 ring-red-200; }
.chip { @apply inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs; }
.chip-blue { @apply bg-blue-50 text-blue-700; }
.chip-green { @apply bg-green-50 text-green-700; }
.chip-red { @apply bg-red-50 text-red-700; }
.alert-error { @apply rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700; }
.skeleton { @apply bg-gray-100 animate-pulse; }
</style>
