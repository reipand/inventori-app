<template>
  <div class="space-y-6">
    <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
      <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
        <div class="max-w-2xl">
          <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">Overview</p>
          <h2 class="mt-2 text-3xl font-semibold tracking-tight text-gray-900">
            Dashboard yang lebih tenang untuk operasional harian.
          </h2>
          <p class="mt-3 max-w-xl text-sm leading-6 text-gray-500">
            Fokus utama ada di ringkasan stok, penjualan, dan aksi cepat supaya admin maupun kasir tidak perlu lompat terlalu banyak halaman.
          </p>
        </div>

        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:w-[520px]">
          <RouterLink
            v-for="action in quickActions"
            :key="action.title"
            :to="action.to"
            class="rounded-xl border border-gray-200 bg-gray-50 p-4 transition hover:border-primary/30 hover:bg-primary/5"
          >
            <component :is="action.icon" class="h-4 w-4 text-primary" />
            <p class="mt-4 text-sm font-medium text-gray-900">{{ action.title }}</p>
            <p class="mt-1 text-xs text-gray-500">{{ action.caption }}</p>
          </RouterLink>
        </div>
      </div>
    </section>

    <section class="grid grid-cols-2 gap-4 md:grid-cols-4">
      <article v-for="card in cards" :key="card.label" class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
        <div class="flex items-start justify-between">
          <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary/10 text-primary">
            <component :is="card.icon" class="h-5 w-5" />
          </div>
          <span v-if="card.badge" class="rounded-full bg-gray-100 px-2.5 py-1 text-[11px] font-medium text-gray-500">
            {{ card.badge }}
          </span>
        </div>
        <p class="mt-5 text-2xl font-semibold tracking-tight text-gray-900">{{ card.value }}</p>
        <p class="mt-1 text-sm font-medium text-gray-700">{{ card.label }}</p>
        <p class="mt-2 text-xs leading-5 text-gray-500">{{ card.caption }}</p>
      </article>
    </section>

    <section class="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
      <article class="rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="flex flex-col gap-4 border-b border-gray-200 p-6 md:flex-row md:items-center md:justify-between">
          <div>
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">Inventory Pulse</p>
            <h3 class="mt-2 text-lg font-semibold text-gray-900">Stok realtime</h3>
          </div>
          <RouterLink
            to="/products"
            class="inline-flex items-center rounded-xl border border-gray-200 px-3 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-50"
          >
            Lihat semua produk
          </RouterLink>
        </div>

        <div v-if="store.loading" class="space-y-3 p-6">
          <div v-for="i in 6" :key="i" class="skeleton h-16 rounded-xl" />
        </div>

        <div v-else class="overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-[0.14em] text-gray-400">
              <tr>
                <th class="px-6 py-4">Produk</th>
                <th class="px-6 py-4">SKU</th>
                <th class="px-6 py-4">Stok</th>
                <th class="px-6 py-4">Status</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="product in spotlightProducts" :key="product.id" class="hover:bg-gray-50/80">
                <td class="px-6 py-4">
                  <p class="font-medium text-gray-900">{{ product.name }}</p>
                  <p class="mt-1 text-xs text-gray-500">{{ product.unit }}</p>
                </td>
                <td class="px-6 py-4 text-gray-500">{{ product.sku }}</td>
                <td class="px-6 py-4 font-medium text-gray-900">{{ product.current_stock }}</td>
                <td class="px-6 py-4">
                  <span class="rounded-full px-2.5 py-1 text-xs font-medium" :class="statusClass(product.current_stock, product.min_stock)">
                    {{ statusLabel(product.current_stock, product.min_stock) }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </article>

      <div class="space-y-6">
        <article class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">Low Stock</p>
              <h3 class="mt-2 text-lg font-semibold text-gray-900">Perlu perhatian</h3>
            </div>
            <RouterLink to="/low-stock" class="text-sm font-medium text-primary">Detail</RouterLink>
          </div>

          <div class="mt-5 space-y-3">
            <div
              v-for="product in store.lowStockProducts.slice(0, 4)"
              :key="product.id"
              class="rounded-xl border border-gray-200 bg-gray-50 p-4"
            >
              <div class="flex items-center justify-between gap-3">
                <div class="min-w-0">
                  <p class="truncate text-sm font-medium text-gray-900">{{ product.name }}</p>
                  <p class="mt-1 text-xs text-gray-500">Min {{ product.min_stock }} • SKU {{ product.sku }}</p>
                </div>
                <span class="rounded-full bg-red-50 px-2.5 py-1 text-xs font-medium text-red-600">
                  {{ product.current_stock }}
                </span>
              </div>
            </div>

            <div v-if="!store.lowStockProducts.length" class="rounded-xl border border-dashed border-gray-200 p-5 text-center text-sm text-gray-500">
              Belum ada stok kritis.
            </div>
          </div>
        </article>

        <article class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">Aktivitas</p>
              <h3 class="mt-2 text-lg font-semibold text-gray-900">Transaksi terbaru</h3>
            </div>
            <RouterLink to="/products" class="text-sm font-medium text-primary">Buka inventory</RouterLink>
          </div>

          <div class="mt-5 space-y-3">
            <div
              v-for="tx in store.recentTransactions.slice(0, 5)"
              :key="tx.id"
              class="rounded-xl border border-gray-200 p-4"
            >
              <div class="flex items-center justify-between gap-3">
                <div class="min-w-0">
                  <p class="truncate text-sm font-medium text-gray-900">{{ tx.product?.name ?? 'Produk' }}</p>
                  <p class="mt-1 text-xs text-gray-500">{{ tx.transaction_date }}</p>
                </div>
                <span class="rounded-full px-2.5 py-1 text-xs font-medium" :class="tx.type === 'keluar' ? 'bg-primary/10 text-primary' : 'bg-emerald-50 text-emerald-600'">
                  {{ tx.type === 'keluar' ? 'Penjualan' : 'Barang Masuk' }}
                </span>
              </div>
              <div class="mt-3 flex items-center justify-between text-sm">
                <span class="text-gray-500">{{ tx.quantity }} item</span>
                <span class="font-medium text-gray-900">{{ formatCurrency(tx.price_per_unit * tx.quantity) }}</span>
              </div>
            </div>

            <div v-if="!store.recentTransactions.length" class="rounded-xl border border-dashed border-gray-200 p-5 text-center text-sm text-gray-500">
              Belum ada transaksi.
            </div>
          </div>
        </article>
      </div>
    </section>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted } from 'vue';
import { RouterLink } from 'vue-router';
import { AlertTriangle, ArrowDownToLine, ArrowUpFromLine, Boxes, ReceiptText } from 'lucide-vue-next';
import { useDashboardStore } from '@/stores/dashboard';
import type { Product } from '@/services/productService';

const store = useDashboardStore();

const cards = computed(() => [
  {
    label: 'Total Produk',
    value: store.stats.totalProducts.toLocaleString('id-ID'),
    caption: 'Jumlah item aktif di inventory.',
    badge: 'Inventory',
    icon: Boxes,
  },
  {
    label: 'Stok Menipis',
    value: store.stats.lowStockCount.toLocaleString('id-ID'),
    caption: 'Produk yang perlu restock segera.',
    badge: store.stats.outOfStockCount > 0 ? `${store.stats.outOfStockCount} habis` : '',
    icon: AlertTriangle,
  },
  {
    label: 'Barang Masuk',
    value: store.chartData.reduce((sum, item) => sum + item.masuk, 0).toLocaleString('id-ID'),
    caption: 'Akumulasi barang masuk 7 hari terakhir.',
    badge: '7 hari',
    icon: ArrowDownToLine,
  },
  {
    label: 'Penjualan',
    value: store.stats.todayTransactions.toLocaleString('id-ID'),
    caption: 'Transaksi keluar yang tercatat hari ini.',
    badge: 'Hari ini',
    icon: ReceiptText,
  },
]);

const quickActions = [
  {
    title: 'Input Produk',
    caption: 'Tambah katalog baru',
    to: '/products/new',
    icon: Boxes,
  },
  {
    title: 'Barang Masuk',
    caption: 'Catat restock supplier',
    to: '/products',
    icon: ArrowDownToLine,
  },
  {
    title: 'Penjualan',
    caption: 'Proses langsung dari inventory',
    to: '/products',
    icon: ArrowUpFromLine,
  },
  {
    title: 'Laporan',
    caption: 'Ringkasan dan export',
    to: '/reports',
    icon: ReceiptText,
  },
];

const spotlightProducts = computed<Product[]>(() => store.spotlightProducts);

function statusLabel(currentStock: number, minStock: number): string {
  if (currentStock === 0) return 'Habis';
  if (currentStock <= minStock) return 'Warning';
  return 'Aman';
}

function statusClass(currentStock: number, minStock: number): string {
  if (currentStock === 0) return 'bg-red-50 text-red-600';
  if (currentStock <= minStock) return 'bg-amber-50 text-amber-600';
  return 'bg-emerald-50 text-emerald-600';
}

function formatCurrency(value: number): string {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(value);
}

onMounted(() => {
  store.fetchAll();
});
</script>
