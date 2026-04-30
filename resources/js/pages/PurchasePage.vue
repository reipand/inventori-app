<template>
  <div class="space-y-6">

    <!-- ── Page Header ── -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-xl font-semibold tracking-tight text-gray-900">Pembelian</h1>
        <p class="mt-0.5 text-sm text-gray-500">
          {{ purchaseStore.loading ? '...' : `${purchaseStore.pagination?.total ?? 0} invoice tercatat` }}
        </p>
      </div>
      <button class="btn-add shrink-0" @click="openCreateModal">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Buat Invoice Baru
      </button>
    </div>

    <!-- ── Filter Toolbar ── -->
    <div class="filter-toolbar">
      <div class="flex flex-col sm:flex-row sm:items-center gap-2">

        <!-- Search by invoice number -->
        <div class="relative flex-1 min-w-0 max-w-lg">
          <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
          </svg>
          <input
            v-model="filters.search"
            class="filter-input pl-9"
            placeholder="Cari nomor invoice..."
            @input="onSearch"
          />
        </div>

        <div class="hidden sm:block w-px h-6 bg-gray-200 shrink-0" />

        <!-- Filter group -->
        <div class="flex flex-wrap items-center gap-2 shrink-0">

          <!-- Supplier name -->
          <input
            v-model="filters.supplier_name"
            class="filter-input w-44"
            placeholder="Nama supplier..."
            @input="onSearch"
          />

          <!-- Start date -->
          <input
            v-model="filters.start_date"
            type="date"
            class="filter-input w-40"
            @change="loadInvoices(1)"
          />

          <!-- End date -->
          <input
            v-model="filters.end_date"
            type="date"
            class="filter-input w-40"
            @change="loadInvoices(1)"
          />

          <!-- Clear filters -->
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

      <!-- Active filter chips -->
      <div v-if="hasActiveFilters" class="flex flex-wrap items-center gap-1.5 pt-2 border-t border-gray-100 mt-2">
        <span class="text-xs text-gray-400 mr-1">Filter aktif:</span>
        <span v-if="filters.search" class="filter-chip">
          "{{ filters.search }}"
          <button class="ml-1 opacity-60 hover:opacity-100" @click="filters.search = ''; loadInvoices(1)">×</button>
        </span>
        <span v-if="filters.supplier_name" class="filter-chip">
          Supplier: {{ filters.supplier_name }}
          <button class="ml-1 opacity-60 hover:opacity-100" @click="filters.supplier_name = ''; loadInvoices(1)">×</button>
        </span>
        <span v-if="filters.start_date" class="filter-chip">
          Dari: {{ filters.start_date }}
          <button class="ml-1 opacity-60 hover:opacity-100" @click="filters.start_date = ''; loadInvoices(1)">×</button>
        </span>
        <span v-if="filters.end_date" class="filter-chip">
          Sampai: {{ filters.end_date }}
          <button class="ml-1 opacity-60 hover:opacity-100" @click="filters.end_date = ''; loadInvoices(1)">×</button>
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
              <th class="th">Nomor Invoice</th>
              <th class="th">Nama Supplier</th>
              <th class="th">Tanggal</th>
              <th class="th text-right">Jumlah Item</th>
              <th class="th text-right">Total Nilai</th>
              <th class="th text-right">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <!-- Loading skeleton -->
            <template v-if="purchaseStore.loading">
              <tr v-for="i in 8" :key="i" class="border-b border-gray-50">
                <td class="td"><div class="skeleton h-4 w-32 rounded" /></td>
                <td class="td"><div class="skeleton h-4 w-40 rounded" /></td>
                <td class="td"><div class="skeleton h-4 w-24 rounded" /></td>
                <td class="td"><div class="skeleton h-4 w-12 rounded ml-auto" /></td>
                <td class="td"><div class="skeleton h-4 w-28 rounded ml-auto" /></td>
                <td class="td"><div class="skeleton h-7 w-20 rounded ml-auto" /></td>
              </tr>
            </template>

            <!-- Empty state -->
            <tr v-else-if="purchaseStore.invoices.length === 0">
              <td colspan="6" class="py-16 text-center">
                <div class="flex flex-col items-center gap-3">
                  <div class="w-12 h-12 rounded-2xl bg-gray-100 flex items-center justify-center">
                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                  </div>
                  <div>
                    <p class="font-medium text-gray-700">Belum ada invoice</p>
                    <p class="text-xs text-gray-400 mt-0.5">Buat invoice baru untuk mencatat pembelian dari supplier</p>
                  </div>
                  <button class="btn-add mt-1" @click="openCreateModal">+ Buat Invoice Baru</button>
                </div>
              </td>
            </tr>

            <!-- Data rows -->
            <tr
              v-for="invoice in purchaseStore.invoices"
              :key="invoice.id"
              class="border-b border-gray-50 hover:bg-blue-50/30 transition-colors duration-100 group cursor-pointer"
              @click="openDetailModal(invoice)"
            >
              <td class="td font-mono text-xs text-gray-700 font-medium">{{ invoice.invoice_number }}</td>
              <td class="td">
                <span class="text-gray-900 group-hover:text-blue-700 transition-colors">{{ invoice.supplier_name }}</span>
              </td>
              <td class="td text-gray-500">{{ formatDate(invoice.invoice_date) }}</td>
              <td class="td text-right text-gray-700">{{ invoice.items_count ?? '—' }}</td>
              <td class="td text-right font-medium text-gray-700">{{ fmt(invoice.total_final) }}</td>
              <td class="td text-right" @click.stop>
                <button
                  class="btn-danger-xs"
                  :disabled="purchaseStore.loading"
                  @click="openDeleteDialog(invoice)"
                >
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                  </svg>
                  Hapus
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div
        v-if="(purchaseStore.pagination?.last_page ?? 0) > 1"
        class="flex items-center justify-between px-4 py-3 border-t border-gray-100 bg-gray-50/50"
      >
        <p class="text-xs text-gray-500">
          Halaman {{ purchaseStore.pagination?.current_page }} dari {{ purchaseStore.pagination?.last_page }}
          · {{ purchaseStore.pagination?.total }} invoice
        </p>
        <div class="flex gap-1.5">
          <button
            class="btn-page"
            :disabled="(purchaseStore.pagination?.current_page ?? 1) <= 1"
            @click="loadInvoices((purchaseStore.pagination?.current_page ?? 1) - 1)"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
          </button>
          <button
            v-for="p in pageNumbers"
            :key="p"
            class="btn-page"
            :class="p === purchaseStore.pagination?.current_page ? 'bg-blue-600 text-white border-blue-600' : ''"
            @click="loadInvoices(p)"
          >{{ p }}</button>
          <button
            class="btn-page"
            :disabled="(purchaseStore.pagination?.current_page ?? 1) >= (purchaseStore.pagination?.last_page ?? 1)"
            @click="loadInvoices((purchaseStore.pagination?.current_page ?? 1) + 1)"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
          </button>
        </div>
      </div>
    </div>

    <!-- ── Delete Dialog ── -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="showDeleteDialog" class="modal-overlay" @click.self="showDeleteDialog = false">
          <div class="modal-box max-w-sm">
            <div class="w-10 h-10 rounded-xl bg-red-50 flex items-center justify-center mb-4">
              <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
              </svg>
            </div>
            <h3 class="text-base font-semibold text-gray-900">Hapus Invoice</h3>
            <p class="mt-1.5 text-sm text-gray-500">
              Yakin ingin menghapus invoice
              <span class="font-semibold text-gray-800">{{ deleteTarget?.invoice_number }}</span>?
              Stok produk terkait akan dikembalikan. Tindakan ini tidak dapat dibatalkan.
            </p>

            <!-- Warning: has sold products -->
            <div v-if="deleteHasSoldProducts" class="mt-3 rounded-lg bg-yellow-50 border border-yellow-200 px-3 py-2.5 flex gap-2">
              <svg class="w-4 h-4 text-yellow-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
              </svg>
              <p class="text-xs text-yellow-800">
                Beberapa produk dalam invoice ini sudah terjual melalui POS.
                Penghapusan akan mempengaruhi akurasi laporan profit.
              </p>
            </div>

            <div v-if="deleteError" class="alert-error mt-3">{{ deleteError }}</div>

            <div class="flex justify-end gap-2 mt-5">
              <button class="btn-outline" :disabled="deleting" @click="showDeleteDialog = false">Batal</button>
              <button class="btn-danger" :disabled="deleting" @click="confirmDelete">
                {{ deleting ? 'Menghapus...' : 'Ya, Hapus' }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- ── Invoice Form Modal ── -->
    <InvoiceFormModal
      v-model="showFormModal"
      @saved="onInvoiceSaved"
    />

    <!-- ── Invoice Detail Modal ── -->
    <InvoiceDetailModal
      v-model="showDetailModal"
      :invoice-id="selectedInvoiceId"
    />

  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { usePurchaseStore } from '@/stores/purchaseStore';
import { useToast } from '@/composables/useToast';
import type { Invoice } from '@/services/invoiceService';
import InvoiceFormModal from '@/components/InvoiceFormModal.vue';
import InvoiceDetailModal from '@/components/InvoiceDetailModal.vue';

const purchaseStore = usePurchaseStore();
const toast = useToast();

// ── Filters ──
const filters = ref({
  search: '',
  supplier_name: '',
  start_date: '',
  end_date: '',
});

let searchTimer: ReturnType<typeof setTimeout> | null = null;
const pageError = ref('');

// ── Modals ──
const showFormModal = ref(false);
const showDetailModal = ref(false);
const selectedInvoiceId = ref<string | null>(null);

// ── Delete dialog ──
const showDeleteDialog = ref(false);
const deleteTarget = ref<Invoice | null>(null);
const deleteHasSoldProducts = ref(false);
const deleteError = ref('');
const deleting = ref(false);

// ── Computed ──
const hasActiveFilters = computed(() =>
  !!filters.value.search ||
  !!filters.value.supplier_name ||
  !!filters.value.start_date ||
  !!filters.value.end_date
);

const pageNumbers = computed(() => {
  const pagination = purchaseStore.pagination;
  if (!pagination) return [];
  const pages: number[] = [];
  const start = Math.max(1, pagination.current_page - 2);
  const end = Math.min(pagination.last_page, start + 4);
  for (let i = start; i <= end; i++) pages.push(i);
  return pages;
});

// ── Actions ──
async function loadInvoices(page = 1) {
  pageError.value = '';
  try {
    await purchaseStore.fetchInvoices({
      search: filters.value.search || undefined,
      supplier_name: filters.value.supplier_name || undefined,
      start_date: filters.value.start_date || undefined,
      end_date: filters.value.end_date || undefined,
      page,
    });
  } catch {
    pageError.value = 'Gagal memuat daftar invoice.';
  }
}

function onSearch() {
  if (searchTimer) clearTimeout(searchTimer);
  searchTimer = setTimeout(() => loadInvoices(1), 350);
}

function clearFilters() {
  filters.value = { search: '', supplier_name: '', start_date: '', end_date: '' };
  loadInvoices(1);
}

function openCreateModal() {
  showFormModal.value = true;
}

function openDetailModal(invoice: Invoice) {
  selectedInvoiceId.value = invoice.id;
  showDetailModal.value = true;
}

function openDeleteDialog(invoice: Invoice) {
  deleteTarget.value = invoice;
  deleteHasSoldProducts.value = false;
  deleteError.value = '';
  showDeleteDialog.value = true;
}

async function confirmDelete() {
  if (!deleteTarget.value) return;
  deleting.value = true;
  deleteError.value = '';
  try {
    await purchaseStore.deleteInvoice(deleteTarget.value.id);
    showDeleteDialog.value = false;
    toast.success('Invoice berhasil dihapus.');
  } catch (err: unknown) {
    const e = err as {
      response?: {
        status?: number;
        data?: {
          has_sold_products?: boolean;
          error?: { message?: string };
          message?: string;
        };
      };
    };
    const data = e?.response?.data;
    if (data?.has_sold_products) {
      deleteHasSoldProducts.value = true;
      deleteError.value = data?.error?.message ?? data?.message ?? 'Gagal menghapus invoice.';
    } else {
      deleteError.value = data?.error?.message ?? data?.message ?? 'Gagal menghapus invoice.';
    }
  } finally {
    deleting.value = false;
  }
}

function onInvoiceSaved() {
  loadInvoices(purchaseStore.pagination?.current_page ?? 1);
  toast.success('Invoice berhasil disimpan.');
}

// ── Formatters ──
function fmt(v: number) {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(v);
}

function formatDate(dateStr: string) {
  if (!dateStr) return '—';
  const d = new Date(dateStr);
  return new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }).format(d);
}

// ── Lifecycle ──
onMounted(() => {
  loadInvoices();
});
</script>

<style scoped>
@reference "../../css/app.css";

/* ── Filter toolbar ── */
.filter-toolbar {
  @apply bg-white border border-gray-200 rounded-xl px-3 py-2.5 shadow-sm;
}

.filter-input {
  @apply h-10 w-full rounded-lg border border-gray-200 bg-gray-50/60 px-3
  text-sm text-gray-900 placeholder:text-gray-400
  focus:outline-none focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400
  transition-all;
}

.filter-clear {
  @apply inline-flex items-center gap-1.5 h-10 px-3 rounded-lg
  text-xs font-medium text-gray-500
  border border-gray-200 hover:border-gray-300
  hover:bg-gray-50 hover:text-gray-700
  transition-all shrink-0;
}

.filter-chip {
  @apply inline-flex items-center rounded-full bg-blue-50 text-blue-700
  ring-1 ring-blue-200 px-2.5 py-0.5 text-xs font-medium;
}

.fade-filter-enter-active,
.fade-filter-leave-active { transition: opacity 0.15s ease, transform 0.15s ease; }
.fade-filter-enter-from,
.fade-filter-leave-to { opacity: 0; transform: scale(0.92); }

/* ── Table ── */
.th { @apply px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide whitespace-nowrap; }
.td { @apply px-4 py-3.5; }

/* ── Buttons ── */
.btn-add {
  @apply inline-flex items-center gap-1.5 rounded-lg bg-blue-600 text-white text-sm font-medium h-9 px-3.5
  hover:bg-blue-700 active:scale-95 transition-all shadow-sm;
}

.btn-outline {
  @apply inline-flex items-center justify-center rounded-lg border border-gray-200 text-sm font-medium h-9 px-4
  text-gray-700 hover:bg-gray-50 disabled:opacity-50 transition-all;
}

.btn-danger {
  @apply inline-flex items-center justify-center rounded-lg bg-red-600 text-white text-sm font-medium h-9 px-4
  hover:bg-red-700 disabled:opacity-50 transition-all;
}

.btn-danger-xs {
  @apply inline-flex items-center gap-1 rounded-md text-xs font-medium h-7 px-2
  text-red-600 hover:bg-red-50 transition-all disabled:opacity-40 disabled:cursor-not-allowed;
}

.btn-page {
  @apply inline-flex items-center justify-center w-8 h-8 rounded-lg border border-gray-200
  text-xs font-medium text-gray-600 hover:bg-gray-50
  disabled:opacity-40 disabled:cursor-not-allowed transition-all;
}

/* ── Alerts ── */
.alert-error {
  @apply rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700;
}

/* ── Modal ── */
.modal-overlay {
  @apply fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4;
}
.modal-box {
  @apply w-full max-w-md rounded-2xl bg-white border border-gray-200 shadow-2xl p-6;
}
.modal-enter-active, .modal-leave-active { transition: opacity 0.15s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
.modal-enter-active .modal-box, .modal-leave-active .modal-box { transition: transform 0.15s ease; }
.modal-enter-from .modal-box { transform: scale(0.95) translateY(8px); }
.modal-leave-to .modal-box   { transform: scale(0.95) translateY(8px); }

/* ── Skeleton ── */
.skeleton { @apply bg-gray-100 animate-pulse; }
</style>
