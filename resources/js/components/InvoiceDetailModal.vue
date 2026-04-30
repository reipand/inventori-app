<template>
  <Teleport to="body">
    <Transition name="modal-fade">
      <div
        v-if="modelValue"
        class="fixed inset-0 z-50 flex items-end justify-center md:items-center md:p-4"
        @click.self="close"
      >
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black/50" aria-hidden="true" @click="close" />

        <!-- Dialog — mobile: bottom sheet, desktop: centered card -->
        <div
          role="dialog"
          aria-modal="true"
          aria-labelledby="invoice-detail-modal-title"
          class="relative z-10 w-full bg-white shadow-xl
                 fixed inset-x-0 bottom-0 rounded-t-2xl
                 max-h-[92vh] overflow-y-auto
                 md:static md:rounded-2xl md:max-w-5xl md:max-h-[90vh]"
        >
          <!-- Header -->
          <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 sticky top-0 bg-white z-10">
            <div>
              <p class="text-xs font-semibold uppercase tracking-widest text-gray-400">Detail Invoice</p>
              <h2 id="invoice-detail-modal-title" class="text-base font-semibold text-gray-900 mt-0.5">
                {{ invoice?.invoice_number ?? '—' }}
              </h2>
            </div>
            <button
              type="button"
              class="rounded-lg p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors duration-150 min-h-[44px] min-w-[44px] flex items-center justify-center"
              aria-label="Tutup modal"
              @click="close"
            >
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <line x1="18" y1="6" x2="6" y2="18" /><line x1="6" y1="6" x2="18" y2="18" />
              </svg>
            </button>
          </div>

          <!-- Loading state -->
          <div v-if="loading" class="flex items-center justify-center py-20">
            <svg class="w-8 h-8 animate-spin text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z" />
            </svg>
            <span class="ml-3 text-sm text-gray-500">Memuat data invoice...</span>
          </div>

          <!-- Error state -->
          <div v-else-if="error" class="px-6 py-10 text-center">
            <p class="text-sm text-red-600">{{ error }}</p>
            <button
              type="button"
              class="mt-4 text-sm text-blue-600 hover:underline"
              @click="loadInvoice"
            >
              Coba lagi
            </button>
          </div>

          <!-- Content -->
          <div v-else-if="invoice" class="px-6 py-5 space-y-6">

            <!-- Invoice Header Info -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
              <div class="info-card">
                <span class="info-label">Nomor Invoice</span>
                <span class="info-value font-semibold">{{ invoice.invoice_number }}</span>
              </div>
              <div class="info-card">
                <span class="info-label">Nama Supplier</span>
                <span class="info-value">{{ invoice.supplier_name }}</span>
              </div>
              <div class="info-card">
                <span class="info-label">Tanggal Invoice</span>
                <span class="info-value">{{ formatDate(invoice.invoice_date) }}</span>
              </div>
              <div v-if="invoice.discount_global_type" class="info-card">
                <span class="info-label">Diskon Global</span>
                <span class="info-value">
                  <span class="inline-flex items-center gap-1">
                    <span class="badge-type">{{ invoice.discount_global_type === 'percent' ? '%' : 'Rp' }}</span>
                    {{ invoice.discount_global_type === 'percent'
                      ? `${formatNumber(invoice.discount_global_value)}%`
                      : formatCurrency(invoice.discount_global_value) }}
                  </span>
                </span>
              </div>
              <div class="info-card">
                <span class="info-label">Total Sebelum Diskon</span>
                <span class="info-value">{{ formatCurrency(invoice.total_before_discount) }}</span>
              </div>
              <div class="info-card">
                <span class="info-label">Total Diskon</span>
                <span class="info-value text-red-600">{{ formatCurrency(invoice.total_discount) }}</span>
              </div>
              <div class="info-card bg-blue-50 border-blue-200">
                <span class="info-label text-blue-700">Total Akhir</span>
                <span class="info-value font-bold text-blue-700 text-base">{{ formatCurrency(invoice.total_final) }}</span>
              </div>
              <div class="info-card">
                <span class="info-label">Dicatat Oleh</span>
                <span class="info-value">{{ invoice.recorded_by_user?.email ?? invoice.recorded_by }}</span>
              </div>
              <div class="info-card">
                <span class="info-label">Tanggal Dibuat</span>
                <span class="info-value">{{ formatDateTime(invoice.created_at) }}</span>
              </div>
            </div>

            <!-- Items Table -->
            <div>
              <h3 class="text-sm font-semibold text-gray-700 mb-3">Daftar Item</h3>
              <div class="overflow-x-auto rounded-xl border border-gray-200">
                <table class="w-full text-sm">
                  <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                      <th class="th-cell text-left">Nama Produk</th>
                      <th class="th-cell text-right">Qty</th>
                      <th class="th-cell text-right">Harga Input</th>
                      <th class="th-cell text-center">Price Mode</th>
                      <th class="th-cell text-center">Diskon Per Item</th>
                      <th class="th-cell text-right">Harga Satuan Final</th>
                      <th class="th-cell text-right">COGS Per Unit</th>
                      <th class="th-cell text-right">Subtotal</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr
                      v-for="item in invoice.items"
                      :key="item.id"
                      class="border-b border-gray-100 last:border-0 hover:bg-gray-50 transition-colors"
                    >
                      <td class="td-cell font-medium text-gray-900">
                        {{ item.product?.name ?? item.product_id }}
                      </td>
                      <td class="td-cell text-right text-gray-700">{{ item.qty }}</td>
                      <td class="td-cell text-right text-gray-700">{{ formatCurrency(item.price_input) }}</td>
                      <td class="td-cell text-center">
                        <span :class="item.price_mode === 'final' ? 'badge-final' : 'badge-before'">
                          {{ item.price_mode === 'final' ? 'Final' : 'Sebelum Diskon' }}
                        </span>
                      </td>
                      <td class="td-cell text-center text-gray-600">
                        <span v-if="item.discount_item_type">
                          <span class="badge-type">{{ item.discount_item_type === 'percent' ? '%' : 'Rp' }}</span>
                          {{ item.discount_item_type === 'percent'
                            ? `${formatNumber(item.discount_item_value)}%`
                            : formatCurrency(item.discount_item_value) }}
                        </span>
                        <span v-else class="text-gray-400">—</span>
                      </td>
                      <td class="td-cell text-right text-gray-700">{{ formatCurrency(item.price_per_unit_final) }}</td>
                      <td class="td-cell text-right font-medium text-emerald-700">{{ formatCurrency(item.cogs_per_unit) }}</td>
                      <td class="td-cell text-right font-semibold text-gray-900">{{ formatCurrency(item.subtotal_final) }}</td>
                    </tr>
                    <tr v-if="!invoice.items || invoice.items.length === 0">
                      <td colspan="8" class="td-cell text-center text-gray-400 py-8">Tidak ada item.</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Summary Footer -->
            <div class="flex justify-end">
              <div class="w-full sm:w-80 rounded-xl border border-gray-200 overflow-hidden">
                <div class="summary-row">
                  <span class="text-gray-600">Total Sebelum Diskon Global</span>
                  <span class="font-medium text-gray-900">{{ formatCurrency(invoice.total_before_discount) }}</span>
                </div>
                <div class="summary-row">
                  <span class="text-gray-600">Total Diskon Global</span>
                  <span class="font-medium text-red-600">− {{ formatCurrency(invoice.total_discount) }}</span>
                </div>
                <div class="summary-row bg-blue-50 border-t-2 border-blue-200">
                  <span class="font-semibold text-blue-800">Total Akhir</span>
                  <span class="font-bold text-blue-800 text-base">{{ formatCurrency(invoice.total_final) }}</span>
                </div>
              </div>
            </div>

          </div>

          <!-- Footer -->
          <div class="flex justify-end px-6 py-4 border-t border-gray-100 sticky bottom-0 bg-white">
            <button
              type="button"
              class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white text-gray-700 text-sm font-medium h-10 px-5 hover:bg-gray-50 transition-colors duration-150 min-h-[44px]"
              @click="close"
            >
              Tutup
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import { getInvoice, type Invoice } from '@/services/invoiceService';

// ── Props & Emits ──────────────────────────────────────────────────────────────

const props = defineProps<{
  modelValue: boolean;
  invoiceId: string | null;
}>();

const emit = defineEmits<{
  'update:modelValue': [value: boolean];
}>();

// ── Types ──────────────────────────────────────────────────────────────────────

type InvoiceDetail = Invoice & {
  recorded_by_user?: { id: string; email: string } | null;
};

// ── State ──────────────────────────────────────────────────────────────────────

const invoice = ref<InvoiceDetail | null>(null);
const loading = ref(false);
const error = ref('');

// ── Watch invoiceId ────────────────────────────────────────────────────────────

watch(
  () => props.invoiceId,
  (id) => {
    if (id) {
      loadInvoice();
    } else {
      invoice.value = null;
      error.value = '';
    }
  }
);

watch(
  () => props.modelValue,
  (open) => {
    if (!open) {
      // Reset state when modal closes
      invoice.value = null;
      error.value = '';
    } else if (props.invoiceId) {
      loadInvoice();
    }
  }
);

// ── Load Invoice ───────────────────────────────────────────────────────────────

async function loadInvoice() {
  if (!props.invoiceId) return;
  loading.value = true;
  error.value = '';
  invoice.value = null;
  try {
    const data = await getInvoice(props.invoiceId);
    invoice.value = data as InvoiceDetail;
  } catch (err: unknown) {
    const e = err as { response?: { data?: { error?: { message?: string }; message?: string } } };
    error.value = e?.response?.data?.error?.message ?? e?.response?.data?.message ?? 'Gagal memuat data invoice.';
  } finally {
    loading.value = false;
  }
}

// ── Close ──────────────────────────────────────────────────────────────────────

function close() {
  emit('update:modelValue', false);
}

// ── Formatters ─────────────────────────────────────────────────────────────────

function formatCurrency(value: number | string | null | undefined): string {
  const num = Number(value ?? 0);
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(num);
}

function formatNumber(value: number | string | null | undefined): string {
  const num = Number(value ?? 0);
  return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 }).format(num);
}

function formatDate(dateStr: string | null | undefined): string {
  if (!dateStr) return '—';
  const date = new Date(dateStr);
  return new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'long', year: 'numeric' }).format(date);
}

function formatDateTime(dateStr: string | null | undefined): string {
  if (!dateStr) return '—';
  const date = new Date(dateStr);
  return new Intl.DateTimeFormat('id-ID', {
    day: '2-digit', month: 'long', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  }).format(date);
}
</script>

<style scoped>
@reference "../../css/app.css";

.info-card {
  @apply flex flex-col gap-1 rounded-lg border border-gray-200 bg-gray-50 px-4 py-3;
}
.info-label {
  @apply text-xs font-medium text-gray-500 uppercase tracking-wide;
}
.info-value {
  @apply text-sm text-gray-900;
}

.th-cell {
  @apply px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide whitespace-nowrap;
}
.td-cell {
  @apply px-4 py-3 text-sm whitespace-nowrap;
}

.badge-final {
  @apply inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700;
}
.badge-before {
  @apply inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700;
}
.badge-type {
  @apply inline-flex items-center rounded bg-gray-200 px-1.5 py-0.5 text-xs font-semibold text-gray-600 mr-1;
}

.summary-row {
  @apply flex items-center justify-between px-4 py-3 border-b border-gray-100 last:border-0;
}

/* Overlay fade */
.modal-fade-enter-active,
.modal-fade-leave-active { transition: opacity 0.15s ease; }
.modal-fade-enter-from,
.modal-fade-leave-to { opacity: 0; }
</style>
