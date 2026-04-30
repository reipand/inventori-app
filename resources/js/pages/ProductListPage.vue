<template>
  <div class="space-y-6">

    <!-- ── Page Header ── -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-xl font-semibold tracking-tight text-gray-900">Inventaris Barang</h1>
        <p class="mt-0.5 text-sm text-gray-500">
          {{ loading ? '...' : `${total} produk terdaftar • transaksi masuk dan keluar dikelola langsung dari sini` }}
        </p>
      </div>
      <div class="flex items-center gap-2 shrink-0 flex-wrap">
        <button class="btn-tx-header-in" @click="openTransactionModal('masuk', null)">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
          <span class="hidden xs:inline sm:inline">Barang</span> Masuk
        </button>
        <button class="btn-tx-header-out" @click="openTransactionModal('keluar', null)">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
          <span class="hidden xs:inline sm:inline">Barang</span> Keluar
        </button>
        <RouterLink to="/products/new" class="btn-add">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
          Tambah
        </RouterLink>
      </div>
    </div>

    <!-- ── KPI Cards ── -->
    <div class="grid grid-cols-3 gap-3 sm:gap-4">
      <div class="kpi-card">
        <div class="kpi-icon bg-blue-50">
          <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10"/></svg>
        </div>
        <p class="mt-2 sm:mt-3 text-2xl sm:text-3xl font-bold text-gray-900 tabular-nums">{{ loading ? '—' : total }}</p>
        <p class="mt-1 text-xs text-gray-500 font-medium">Total Produk</p>
      </div>
      <div class="kpi-card">
        <div class="kpi-icon bg-yellow-50">
          <svg class="w-4 h-4 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
        </div>
        <p class="mt-2 sm:mt-3 text-2xl sm:text-3xl font-bold text-yellow-700 tabular-nums">{{ loading ? '—' : kpi.lowStock }}</p>
        <p class="mt-1 text-xs text-yellow-600 font-medium">Stok Rendah</p>
      </div>
      <div class="kpi-card">
        <div class="kpi-icon bg-red-50">
          <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
        </div>
        <p class="mt-2 sm:mt-3 text-2xl sm:text-3xl font-bold text-red-600 tabular-nums">{{ loading ? '—' : kpi.outOfStock }}</p>
        <p class="mt-1 text-xs text-red-500 font-medium">Stok Habis</p>
      </div>
    </div>

    <!-- ── Filter Toolbar ── -->
    <div class="filter-toolbar">
      <!-- Row: Search + Filters -->
      <div class="flex flex-col sm:flex-row sm:items-center gap-2">

        <!-- Search — dominant -->
        <div class="relative flex-1 min-w-0 max-w-lg">
          <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
          </svg>
          <input
            v-model="search"
            class="filter-input pl-9"
            placeholder="Cari nama atau SKU..."
            @input="onSearch"
          />
        </div>

        <!-- Vertical divider — desktop only -->
        <div class="hidden sm:block w-px h-6 bg-gray-200 shrink-0" />

        <!-- Filter group -->
        <div class="flex items-center gap-2 shrink-0">
          <!-- Category -->
          <div class="filter-select-wrap">
            <svg class="filter-select-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h8m-8 6h16"/>
            </svg>
            <select v-model="selectedCategory" class="filter-select w-full sm:w-44" @change="loadProducts(1)">
              <option value="">Semua Kategori</option>
              <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
            </select>
            <svg class="filter-chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
          </div>

          <!-- Status -->
          <div class="filter-select-wrap">
            <svg class="filter-select-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0"/>
            </svg>
            <select v-model="statusFilter" class="filter-select w-full sm:w-36" @change="loadProducts(1)">
              <option value="">Semua Status</option>
              <option value="normal">Normal</option>
              <option value="rendah">Rendah</option>
              <option value="habis">Habis</option>
            </select>
            <svg class="filter-chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
          </div>

          <!-- Clear filters — only when active -->
          <Transition name="fade-filter">
            <button
              v-if="hasActiveFilters"
              class="filter-clear"
              @click="clearFilters"
            >
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
              Reset
            </button>
          </Transition>
        </div>
      </div>

      <!-- Active filter chips — optional info row -->
      <div v-if="hasActiveFilters" class="flex items-center gap-1.5 pt-2 border-t border-gray-100 mt-2">
        <span class="text-xs text-gray-400 mr-1">Filter aktif:</span>
        <span v-if="search" class="filter-chip">
          "{{ search }}"
          <button class="ml-1 opacity-60 hover:opacity-100" @click="search = ''; loadProducts(1)">×</button>
        </span>
        <span v-if="selectedCategory" class="filter-chip">
          {{ categories.find(c => c.id === Number(selectedCategory))?.name ?? 'Kategori' }}
          <button class="ml-1 opacity-60 hover:opacity-100" @click="selectedCategory = ''; loadProducts(1)">×</button>
        </span>
        <span v-if="statusFilter" class="filter-chip">
          {{ { normal: 'Normal', rendah: 'Stok Rendah', habis: 'Stok Habis' }[statusFilter] }}
          <button class="ml-1 opacity-60 hover:opacity-100" @click="statusFilter = ''; loadProducts(1)">×</button>
        </span>
      </div>
    </div>

    <div v-if="pageError" class="alert-error">{{ pageError }}</div>

    <!-- ── Table ── -->
    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">
      <div class="overflow-x-auto min-w-0">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-gray-100 bg-gray-50/70">
              <th class="th">SKU</th>
              <th class="th">Nama Produk</th>
              <th class="th">Kategori</th>
              <th class="th">Satuan</th>
              <th class="th text-right">Harga Jual</th>
              <th class="th text-right">COGS</th>
              <th class="th text-right">Stok</th>
              <th class="th text-center">Status</th>
              <th class="th text-right">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <!-- Loading skeleton -->
            <template v-if="loading">
              <tr v-for="i in 8" :key="i" class="border-b border-gray-50">
                <td class="td"><div class="skeleton h-4 w-20 rounded" /></td>
                <td class="td"><div class="skeleton h-4 w-36 rounded" /></td>
                <td class="td"><div class="skeleton h-4 w-24 rounded" /></td>
                <td class="td"><div class="skeleton h-4 w-16 rounded" /></td>
                <td class="td"><div class="skeleton h-4 w-20 rounded ml-auto" /></td>
                <td class="td"><div class="skeleton h-4 w-20 rounded ml-auto" /></td>
                <td class="td"><div class="skeleton h-4 w-12 rounded ml-auto" /></td>
                <td class="td"><div class="skeleton h-5 w-16 rounded-full mx-auto" /></td>
                <td class="td"><div class="skeleton h-7 w-36 rounded ml-auto" /></td>
              </tr>
            </template>

            <!-- Empty state -->
            <tr v-else-if="filteredProducts.length === 0">
              <td colspan="9" class="py-16 text-center">
                <div class="flex flex-col items-center gap-3">
                  <div class="w-12 h-12 rounded-2xl bg-gray-100 flex items-center justify-center">
                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10"/></svg>
                  </div>
                  <div>
                    <p class="font-medium text-gray-700">Tidak ada produk ditemukan</p>
                    <p class="text-xs text-gray-400 mt-0.5">Coba ubah filter atau tambah produk baru</p>
                  </div>
                  <RouterLink to="/products/new" class="btn-add mt-1">+ Tambah Barang</RouterLink>
                </div>
              </td>
            </tr>

            <!-- Data rows -->
            <tr
              v-for="p in filteredProducts"
              :key="p.id"
              class="border-b border-gray-50 hover:bg-blue-50/30 transition-colors duration-100 group"
            >
              <td class="td font-mono text-xs text-gray-400">{{ p.sku }}</td>
              <td class="td">
                <span class="font-medium text-gray-900 group-hover:text-blue-700 transition-colors">{{ p.name }}</span>
              </td>
              <td class="td text-gray-500">{{ p.category?.name ?? '—' }}</td>
              <td class="td text-gray-500">{{ p.unit }}</td>
              <td class="td text-right font-medium text-gray-700">{{ fmt(p.sell_price) }}</td>
              <td class="td text-right font-medium text-gray-500">{{ fmt(p.cogs) }}</td>
              <td class="td text-right">
                <span :class="p.current_stock === 0 ? 'text-red-600 font-bold' : p.current_stock <= p.min_stock ? 'text-yellow-700 font-semibold' : 'text-gray-900 font-medium'">
                  {{ p.current_stock }}
                </span>
                <span class="text-gray-400 text-xs ml-1">/ {{ p.min_stock }}</span>
              </td>
              <td class="td text-center">
                <span :class="stockBadge(p).cls">{{ stockBadge(p).label }}</span>
              </td>
              <td class="td text-right">
                <div class="flex items-center justify-end gap-1.5">
                  <button class="btn-tx-in" :title="`Barang Masuk: ${p.name}`" @click="openTransactionModal('masuk', p)">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                    Masuk
                  </button>
                  <button class="btn-tx-out" :title="`Barang Keluar: ${p.name}`" @click="openTransactionModal('keluar', p)">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                    Keluar
                  </button>
                  <button class="btn-ghost-xs" :title="`Edit: ${p.name}`" @click="openEditModal(p)">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    Edit
                  </button>
                  <button class="btn-danger-xs" @click="openDelete(p)">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    Hapus
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="lastPage > 1" class="flex items-center justify-between px-4 py-3 border-t border-gray-100 bg-gray-50/50">
        <p class="text-xs text-gray-500">Halaman {{ currentPage }} dari {{ lastPage }} · {{ total }} produk</p>
        <div class="flex gap-1.5">
          <button class="btn-page" :disabled="currentPage <= 1" @click="loadProducts(currentPage - 1)">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
          </button>
          <button
            v-for="p in pageNumbers"
            :key="p"
            class="btn-page"
            :class="p === currentPage ? 'bg-blue-600 text-white border-blue-600' : ''"
            @click="loadProducts(p)"
          >{{ p }}</button>
          <button class="btn-page" :disabled="currentPage >= lastPage" @click="loadProducts(currentPage + 1)">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
          </button>
        </div>
      </div>
    </div>

    <!-- ── Delete Dialog ── -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="showDelete" class="modal-overlay" @click.self="showDelete = false">
          <div class="modal-box max-w-sm">
            <div class="w-10 h-10 rounded-xl bg-red-50 flex items-center justify-center mb-4">
              <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            </div>
            <h3 class="text-base font-semibold text-gray-900">Hapus Produk</h3>
            <p class="mt-1.5 text-sm text-gray-500">
              Yakin ingin menghapus <span class="font-semibold text-gray-800">{{ deleteTarget?.name }}</span>? Tindakan ini tidak dapat dibatalkan.
            </p>
            <div v-if="deleteError" class="alert-error mt-3">{{ deleteError }}</div>
            <div class="flex justify-end gap-2 mt-5">
              <button class="btn-outline" :disabled="deleting" @click="showDelete = false">Batal</button>
              <button class="btn-danger" :disabled="deleting" @click="confirmDelete">
                {{ deleting ? 'Menghapus...' : 'Ya, Hapus' }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- ── Transaction Modal ── -->
    <TransactionModal
      v-model="showTransactionModal"
      :mode="transactionMode"
      :product="selectedProduct"
      @saved="onSaved"
    />

    <!-- ── Product Edit Modal ── -->
    <ProductEditModal
      v-model="showEditModal"
      :product="editProduct"
      @saved="onSaved"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { RouterLink } from 'vue-router';
import { getProducts, deleteProduct, getStockStatus, type Product } from '@/services/productService';
import { getCategories, type Category } from '@/services/categoryService';
import { useToast } from '@/composables/useToast';
import TransactionModal from '@/components/TransactionModal.vue';
import ProductEditModal from '@/components/ProductEditModal.vue';

const toast = useToast();
const products = ref<Product[]>([]);
const categories = ref<Category[]>([]);
const loading = ref(false);
const pageError = ref('');
const search = ref('');
const selectedCategory = ref('');
const statusFilter = ref('');
const currentPage = ref(1);
const lastPage = ref(1);
const total = ref(0);
const kpi = ref({ lowStock: 0, outOfStock: 0 });
let searchTimer: ReturnType<typeof setTimeout> | null = null;

const showTransactionModal = ref(false);
const selectedProduct = ref<Product | null>(null);
const transactionMode = ref<'masuk' | 'keluar'>('masuk');
const showEditModal = ref(false);
const editProduct = ref<Product | null>(null);
const showDelete = ref(false);
const deleteTarget = ref<Product | null>(null);
const deleteError = ref('');
const deleting = ref(false);

function openTransactionModal(mode: 'masuk' | 'keluar', product: Product | null) {
  transactionMode.value = mode;
  selectedProduct.value = product;
  showTransactionModal.value = true;
}

function openEditModal(product: Product) {
  editProduct.value = product;
  showEditModal.value = true;
}

function onSaved(updatedProduct: Product) {
  const idx = products.value.findIndex((p) => p.id === updatedProduct.id);
  if (idx !== -1) {
    products.value[idx] = updatedProduct;
    kpi.value = {
      lowStock: products.value.filter((p) => p.current_stock > 0 && p.current_stock <= p.min_stock).length,
      outOfStock: products.value.filter((p) => p.current_stock === 0).length,
    };
  }
}

const filteredProducts = computed(() => {
  if (!statusFilter.value) return products.value;
  return products.value.filter((p) => getStockStatus(p) === statusFilter.value);
});

const hasActiveFilters = computed(() =>
  !!search.value || !!selectedCategory.value || !!statusFilter.value
);

function clearFilters() {
  search.value = '';
  selectedCategory.value = '';
  statusFilter.value = '';
  loadProducts(1);
}

const pageNumbers = computed(() => {
  const pages: number[] = [];
  const start = Math.max(1, currentPage.value - 2);
  const end = Math.min(lastPage.value, start + 4);
  for (let i = start; i <= end; i++) pages.push(i);
  return pages;
});

async function loadProducts(page = 1) {
  loading.value = true; pageError.value = '';
  try {
    const res = await getProducts({ search: search.value || undefined, category_id: selectedCategory.value || undefined, page });
    products.value = res.data;
    currentPage.value = res.current_page;
    lastPage.value = res.last_page;
    total.value = res.total;
    kpi.value = {
      lowStock: res.data.filter((p) => p.current_stock > 0 && p.current_stock <= p.min_stock).length,
      outOfStock: res.data.filter((p) => p.current_stock === 0).length,
    };
  } catch { pageError.value = 'Gagal memuat produk.'; }
  finally { loading.value = false; }
}

onMounted(() => {
  loadProducts();
  getCategories().then((c) => (categories.value = c)).catch(() => {});
});

function onSearch() {
  if (searchTimer) clearTimeout(searchTimer);
  searchTimer = setTimeout(() => loadProducts(1), 350);
}

function stockBadge(p: Product) {
  const s = getStockStatus(p);
  if (s === 'habis') return { label: 'Habis', cls: 'badge badge-red' };
  if (s === 'rendah') return { label: 'Rendah', cls: 'badge badge-yellow' };
  return { label: 'Normal', cls: 'badge badge-green' };
}

function fmt(v: number) {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(v);
}

function openDelete(p: Product) { deleteTarget.value = p; deleteError.value = ''; showDelete.value = true; }

async function confirmDelete() {
  if (!deleteTarget.value) return;
  deleting.value = true; deleteError.value = '';
  try {
    await deleteProduct(deleteTarget.value.id);
    products.value = products.value.filter((p) => p.id !== deleteTarget.value!.id);
    total.value = Math.max(0, total.value - 1);
    showDelete.value = false;
    toast.success('Produk berhasil dihapus.');
  } catch (err: unknown) {
    const e = err as { response?: { status?: number; data?: { error?: { code?: string; message?: string } } } };
    const status = e?.response?.status;
    const code = e?.response?.data?.error?.code;
    if (status === 422 || code === 'BUSINESS_RULE_VIOLATION') deleteError.value = 'Produk tidak dapat dihapus karena memiliki riwayat transaksi.';
    else deleteError.value = e?.response?.data?.error?.message ?? 'Gagal menghapus.';
  } finally { deleting.value = false; }
}
</script>

<style scoped>
@reference "../../css/app.css";

/* ── Layout ── */
.kpi-card  { @apply rounded-xl sm:rounded-2xl border border-gray-200 bg-white p-3 sm:p-5 shadow-sm hover:shadow-md transition-shadow; }
.kpi-icon  { @apply w-8 h-8 rounded-lg flex items-center justify-center; }

/* ── Filter toolbar ── */
.filter-toolbar {
  @apply bg-white border border-gray-200 rounded-xl px-3 py-2.5 shadow-sm;
}

/* Search input */
.filter-input {
  @apply h-10 w-full rounded-lg border border-gray-200 bg-gray-50/60 pl-9 pr-3
  text-sm text-gray-900 placeholder:text-gray-400
  focus:outline-none focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400
  transition-all;
}

/* Select wrapper — positions icon + chevron */
.filter-select-wrap {
  @apply relative flex items-center;
}
.filter-select-icon {
  @apply absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400 pointer-events-none z-10;
}
.filter-chevron {
  @apply absolute right-2 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400 pointer-events-none z-10;
}

/* Native select — hidden default arrow, custom icons */
.filter-select {
  @apply h-10 appearance-none rounded-lg border border-gray-200 bg-gray-50/60
  pl-8 pr-8 text-sm text-gray-700
  focus:outline-none focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400
  transition-all cursor-pointer;
}

/* Clear button */
.filter-clear {
  @apply inline-flex items-center gap-1.5 h-10 px-3 rounded-lg
  text-xs font-medium text-gray-500
  border border-gray-200 hover:border-gray-300
  hover:bg-gray-50 hover:text-gray-700
  transition-all shrink-0;
}

/* Active filter chips */
.filter-chip {
  @apply inline-flex items-center rounded-full bg-blue-50 text-blue-700
  ring-1 ring-blue-200 px-2.5 py-0.5 text-xs font-medium;
}

/* Chip fade animation */
.fade-filter-enter-active,
.fade-filter-leave-active { transition: opacity 0.15s ease, transform 0.15s ease; }
.fade-filter-enter-from,
.fade-filter-leave-to { opacity: 0; transform: scale(0.92); }

/* ── Table ── */
.th { @apply px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide whitespace-nowrap; }
.td { @apply px-4 py-3.5; }

/* ── Buttons — header ── */
.btn-tx-header-in  { @apply inline-flex items-center gap-1.5 rounded-lg bg-green-600 text-white text-sm font-medium h-9 px-3.5 hover:bg-green-700 active:scale-95 transition-all shadow-sm; }
.btn-tx-header-out { @apply inline-flex items-center gap-1.5 rounded-lg bg-orange-500 text-white text-sm font-medium h-9 px-3.5 hover:bg-orange-600 active:scale-95 transition-all shadow-sm; }
.btn-add           { @apply inline-flex items-center gap-1.5 rounded-lg bg-blue-600 text-white text-sm font-medium h-9 px-3.5 hover:bg-blue-700 active:scale-95 transition-all shadow-sm; }

/* ── Buttons — dialog ── */
.btn-outline { @apply inline-flex items-center justify-center rounded-lg border border-gray-200 text-sm font-medium h-9 px-4 text-gray-700 hover:bg-gray-50 disabled:opacity-50 transition-all; }
.btn-danger  { @apply inline-flex items-center justify-center rounded-lg bg-red-600 text-white text-sm font-medium h-9 px-4 hover:bg-red-700 disabled:opacity-50 transition-all; }

/* ── Buttons — table row ── */
.btn-tx-in    { @apply inline-flex items-center gap-1 rounded-md text-xs font-medium h-7 px-2 text-green-700 hover:bg-green-50 border border-green-200 hover:border-green-300 transition-all; }
.btn-tx-out   { @apply inline-flex items-center gap-1 rounded-md text-xs font-medium h-7 px-2 text-orange-700 hover:bg-orange-50 border border-orange-200 hover:border-orange-300 transition-all; }
.btn-ghost-xs { @apply inline-flex items-center gap-1 rounded-md border border-gray-200 text-xs font-medium h-7 px-2 text-gray-600 hover:bg-gray-50 hover:border-gray-300 transition-all; }
.btn-danger-xs{ @apply inline-flex items-center gap-1 rounded-md text-xs font-medium h-7 px-2 text-red-600 hover:bg-red-50 transition-all; }
.btn-page     { @apply inline-flex items-center justify-center w-8 h-8 rounded-lg border border-gray-200 text-xs font-medium text-gray-600 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed transition-all; }

/* ── Badges ── */
.badge        { @apply inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold; }
.badge-green  { @apply bg-green-50 text-green-700 ring-1 ring-green-200; }
.badge-yellow { @apply bg-yellow-50 text-yellow-700 ring-1 ring-yellow-200; }
.badge-red    { @apply bg-red-50 text-red-700 ring-1 ring-red-200; }

/* ── Alerts ── */
.alert-error { @apply rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700; }

/* ── Modal ── */
.modal-overlay { @apply fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4; }
.modal-box     { @apply w-full max-w-md rounded-2xl bg-white border border-gray-200 shadow-2xl p-6; }
.modal-enter-active, .modal-leave-active { transition: opacity 0.15s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
.modal-enter-active .modal-box, .modal-leave-active .modal-box { transition: transform 0.15s ease; }
.modal-enter-from .modal-box { transform: scale(0.95) translateY(8px); }
.modal-leave-to .modal-box   { transform: scale(0.95) translateY(8px); }

/* ── Skeleton ── */
.skeleton { @apply bg-gray-100 animate-pulse; }
</style>
