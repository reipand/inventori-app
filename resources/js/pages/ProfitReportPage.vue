<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-start justify-between gap-4">
      <div>
        <h1 class="text-xl font-semibold tracking-tight text-gray-900">Laporan Profit</h1>
        <p class="mt-0.5 text-sm text-gray-500">Analisis pendapatan, COGS, dan profit berdasarkan rentang tanggal</p>
      </div>
    </div>

    <!-- Date Range Filter -->
    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm p-5 space-y-4">
      <p class="text-sm font-semibold text-gray-700">Filter Periode</p>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="field">
          <label class="field-label">Tanggal Awal</label>
          <input v-model="startDate" type="date" class="input-base" />
        </div>
        <div class="field">
          <label class="field-label">Tanggal Akhir</label>
          <input v-model="endDate" type="date" class="input-base" />
        </div>
      </div>
      <div v-if="dateError" class="text-xs text-red-600">{{ dateError }}</div>
      <div class="flex justify-end pt-1 border-t border-gray-100">
        <button
          class="btn-primary"
          @click="fetchReport"
          :disabled="loading || !!dateError"
        >
          <svg v-if="loading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
          </svg>
          {{ loading ? 'Memuat...' : 'Terapkan Filter' }}
        </button>
      </div>
    </div>

    <!-- Loading Skeleton -->
    <template v-if="loading">
      <!-- KPI Skeleton -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div v-for="i in 4" :key="i" class="kpi-card">
          <div class="skeleton h-4 w-24 rounded mb-3" />
          <div class="skeleton h-8 w-32 rounded mb-2" />
          <div class="skeleton h-3 w-20 rounded" />
        </div>
      </div>
      <!-- Table Skeleton -->
      <div class="rounded-2xl border border-gray-200 bg-white shadow-sm p-6 space-y-3">
        <div class="skeleton h-5 w-40 rounded mb-4" />
        <div v-for="i in 6" :key="i" class="skeleton h-11 rounded-xl" />
      </div>
    </template>

    <!-- Error State -->
    <div v-else-if="error" class="rounded-2xl border border-red-200 bg-red-50 p-6">
      <div class="flex items-start gap-3">
        <svg class="w-5 h-5 text-red-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <div>
          <p class="text-sm font-semibold text-red-700">Gagal memuat laporan</p>
          <p class="text-sm text-red-600 mt-1">{{ error }}</p>
          <button class="mt-3 text-xs font-medium text-red-700 underline hover:no-underline" @click="fetchReport">
            Coba lagi
          </button>
        </div>
      </div>
    </div>

    <!-- Data Loaded -->
    <template v-else-if="reportData">
      <!-- KPI Cards: blue=revenue, orange=COGS, green=profit, purple=margin -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Revenue — blue -->
        <div class="kpi-card">
          <div class="kpi-icon bg-blue-50">
            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </div>
          <p class="mt-3 text-sm font-medium text-gray-500">Total Pendapatan</p>
          <p class="mt-1 text-2xl font-bold text-blue-700 tabular-nums">{{ fmt(reportData.summary.total_revenue) }}</p>
          <p class="mt-1 text-xs text-gray-400">Revenue dari penjualan</p>
        </div>

        <!-- Total COGS — orange -->
        <div class="kpi-card border-orange-100">
          <div class="kpi-icon bg-orange-50">
            <svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
          </div>
          <p class="mt-3 text-sm font-medium text-orange-700">Total Harga Pokok</p>
          <p class="mt-1 text-2xl font-bold text-orange-700 tabular-nums">{{ fmt(reportData.summary.total_cogs) }}</p>
          <p class="mt-1 text-xs text-orange-400">COGS dari invoice pembelian</p>
        </div>

        <!-- Total Profit — green -->
        <div class="kpi-card border-green-100">
          <div class="kpi-icon bg-green-50">
            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
            </svg>
          </div>
          <p class="mt-3 text-sm font-medium text-green-700">Total Profit Kotor</p>
          <p
            class="mt-1 text-2xl font-bold tabular-nums"
            :class="reportData.summary.total_profit >= 0 ? 'text-green-700' : 'text-red-600'"
          >
            {{ fmt(reportData.summary.total_profit) }}
          </p>
          <p class="mt-1 text-xs text-green-400">Pendapatan dikurangi COGS</p>
        </div>

        <!-- Margin % — purple -->
        <div class="kpi-card border-purple-100">
          <div class="kpi-icon bg-purple-50">
            <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
          </div>
          <p class="mt-3 text-sm font-medium text-purple-700">Margin Profit</p>
          <p
            class="mt-1 text-2xl font-bold tabular-nums"
            :class="reportData.summary.margin >= 0 ? 'text-purple-700' : 'text-red-600'"
          >
            {{ fmtPercent(reportData.summary.margin) }}
          </p>
          <p class="mt-1 text-xs text-purple-400">Persentase profit dari revenue</p>
        </div>
      </div>

      <!-- Detail Table -->
      <div class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
          <p class="text-sm font-semibold text-gray-700">Detail per Produk</p>
          <p class="text-xs text-gray-400 mt-0.5">Periode: {{ formatDateRange }}</p>
        </div>

        <!-- Empty State -->
        <div v-if="reportData.products.length === 0" class="py-16 text-center">
          <div class="w-12 h-12 rounded-2xl bg-gray-100 flex items-center justify-center mx-auto mb-3">
            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
          </div>
          <p class="text-sm font-medium text-gray-500">Tidak ada data penjualan</p>
          <p class="text-xs text-gray-400 mt-1">Tidak ada transaksi dalam periode yang dipilih</p>
        </div>

        <!-- Table -->
        <div v-else class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-gray-100 bg-gray-50/70">
                <th class="th">Nama Produk</th>
                <th class="th text-right">Total Qty Terjual</th>
                <th class="th text-right">Total Revenue</th>
                <th class="th text-right">Total COGS</th>
                <th class="th text-right">Total Profit</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="product in reportData.products"
                :key="product.product_id"
                class="border-b border-gray-50 hover:bg-blue-50/20 transition-colors"
              >
                <td class="td font-medium text-gray-900">{{ product.product_name }}</td>
                <td class="td text-right text-gray-700 tabular-nums">{{ product.total_qty }}</td>
                <td class="td text-right text-blue-700 tabular-nums">{{ fmt(product.total_revenue) }}</td>
                <td class="td text-right text-orange-700 tabular-nums">{{ fmt(product.total_cogs) }}</td>
                <td
                  class="td text-right font-semibold tabular-nums"
                  :class="product.total_profit >= 0 ? 'text-green-700' : 'text-red-600'"
                >
                  {{ fmt(product.total_profit) }}
                </td>
              </tr>
            </tbody>
            <tfoot>
              <tr class="border-t border-gray-200 bg-gray-50/70">
                <td class="px-4 py-3 text-sm font-semibold text-gray-700">Total</td>
                <td class="px-4 py-3 text-right text-sm font-bold text-gray-900 tabular-nums">
                  {{ reportData.products.reduce((s, p) => s + p.total_qty, 0) }}
                </td>
                <td class="px-4 py-3 text-right text-sm font-bold text-blue-700 tabular-nums">
                  {{ fmt(reportData.summary.total_revenue) }}
                </td>
                <td class="px-4 py-3 text-right text-sm font-bold text-orange-700 tabular-nums">
                  {{ fmt(reportData.summary.total_cogs) }}
                </td>
                <td
                  class="px-4 py-3 text-right text-sm font-bold tabular-nums"
                  :class="reportData.summary.total_profit >= 0 ? 'text-green-700' : 'text-red-600'"
                >
                  {{ fmt(reportData.summary.total_profit) }}
                </td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </template>

    <!-- Initial state: no data yet and not loading (should not normally show since we auto-fetch) -->
    <div v-else class="rounded-2xl border border-gray-200 bg-white shadow-sm py-16 text-center">
      <div class="w-12 h-12 rounded-2xl bg-gray-100 flex items-center justify-center mx-auto mb-3">
        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 13v-1m4 1v-3m4 3V8M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/>
        </svg>
      </div>
      <p class="text-sm font-medium text-gray-500">Pilih rentang tanggal dan klik "Terapkan Filter"</p>
      <p class="text-xs text-gray-400 mt-1">Laporan profit akan ditampilkan di sini</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import { useToast } from '@/composables/useToast';

const toast = useToast();

// ── Helpers ──
function getMonthStart(): string {
  const now = new Date();
  return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-01`;
}

function getMonthEnd(): string {
  const now = new Date();
  const last = new Date(now.getFullYear(), now.getMonth() + 1, 0);
  return `${last.getFullYear()}-${String(last.getMonth() + 1).padStart(2, '0')}-${String(last.getDate()).padStart(2, '0')}`;
}

// ── State ──
const startDate = ref(getMonthStart());
const endDate = ref(getMonthEnd());
const loading = ref(false);
const error = ref('');

interface ProductProfit {
  product_id: string;
  product_name: string;
  total_qty: number;
  total_revenue: number;
  total_cogs: number;
  total_profit: number;
}

interface ProfitSummary {
  total_revenue: number;
  total_cogs: number;
  total_profit: number;
  margin: number;
}

interface ProfitReportData {
  summary: ProfitSummary;
  products: ProductProfit[];
}

const reportData = ref<ProfitReportData | null>(null);

// ── Validation ──
const dateError = computed(() => {
  if (startDate.value && endDate.value && startDate.value > endDate.value) {
    return 'Tanggal awal tidak boleh lebih besar dari tanggal akhir.';
  }
  return '';
});

// ── Computed ──
const formatDateRange = computed(() => {
  if (!startDate.value && !endDate.value) return 'Semua waktu';
  if (startDate.value && endDate.value) {
    return `${fmtDate(startDate.value)} – ${fmtDate(endDate.value)}`;
  }
  if (startDate.value) return `Mulai ${fmtDate(startDate.value)}`;
  return `Sampai ${fmtDate(endDate.value)}`;
});

// ── Actions ──
async function fetchReport() {
  if (dateError.value) return;

  loading.value = true;
  error.value = '';
  reportData.value = null;

  try {
    const params: Record<string, string> = {};
    if (startDate.value) params.start_date = startDate.value;
    if (endDate.value) params.end_date = endDate.value;

    const response = await axios.get<{ success: boolean; data: ProfitReportData }>(
      '/api/reports/profit',
      { params }
    );

    if (response.data.success) {
      reportData.value = response.data.data;
    } else {
      throw new Error('Respons tidak valid dari server');
    }
  } catch (err: unknown) {
    const message = getErrorMessage(err);
    error.value = message;
    toast.error(message);
  } finally {
    loading.value = false;
  }
}

function getErrorMessage(err: unknown): string {
  if (axios.isAxiosError(err)) {
    if (err.response?.status === 403) {
      return 'Anda tidak memiliki izin untuk mengakses laporan ini.';
    }
    const msg = err.response?.data?.message;
    if (typeof msg === 'string') return msg;
  }
  return 'Gagal memuat laporan profit. Silakan coba lagi.';
}

// ── Formatters ──
function fmt(v: number) {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(v ?? 0);
}

function fmtPercent(v: number) {
  if (v === null || v === undefined || isNaN(v)) return '0,0%';
  return `${Number(v).toFixed(1)}%`;
}

function fmtDate(d: string) {
  if (!d) return '—';
  return new Date(d + 'T00:00:00').toLocaleDateString('id-ID', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
  });
}

// ── Lifecycle ──
onMounted(() => {
  fetchReport();
});
</script>

<style scoped>
@reference "../../css/app.css";

.kpi-card { @apply rounded-2xl border border-gray-200 bg-white p-5 shadow-sm hover:shadow-md transition-shadow; }
.kpi-icon { @apply w-8 h-8 rounded-lg flex items-center justify-center; }
.th { @apply px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide whitespace-nowrap; }
.td { @apply px-4 py-3.5; }
.field { @apply space-y-1.5; }
.field-label { @apply text-xs font-medium text-gray-600; }
.input-base {
  @apply flex h-9 w-full rounded-xl border border-gray-200 bg-white px-3 py-2
  text-sm text-gray-900 placeholder:text-gray-400
  focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/30 focus-visible:border-blue-400
  transition-all;
}
.btn-primary {
  @apply inline-flex items-center gap-2 justify-center rounded-xl bg-blue-600 text-white
  text-sm font-medium h-9 px-4 hover:bg-blue-700
  disabled:opacity-50 disabled:cursor-not-allowed transition-all;
}
.skeleton { @apply bg-gray-100 animate-pulse; }
</style>
