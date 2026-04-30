<template>
  <Teleport to="body">
    <Transition name="modal-fade">
      <div
        v-if="modelValue && sale"
        class="fixed inset-0 z-50 flex items-end justify-center bg-black/50 md:items-center md:p-4"
        @click.self="handleClose"
      >
        <!-- Dialog — mobile: bottom sheet, desktop: centered card -->
        <div
          role="dialog"
          aria-modal="true"
          aria-labelledby="receipt-modal-title"
          class="relative z-10 w-full bg-white shadow-xl
                 fixed inset-x-0 bottom-0 rounded-t-2xl
                 max-h-[92vh] overflow-y-auto
                 md:static md:rounded-2xl md:max-w-sm md:max-h-[90vh]"
        >
          <!-- Screen-only header -->
          <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4 sticky top-0 bg-white z-10 print:hidden">
            <h2 id="receipt-modal-title" class="text-base font-semibold text-gray-900">Struk Transaksi</h2>
            <button
              type="button"
              class="rounded-lg p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors min-h-[44px] min-w-[44px] flex items-center justify-center"
              aria-label="Tutup modal"
              @click="handleClose"
            >
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <line x1="18" y1="6" x2="6" y2="18" /><line x1="6" y1="6" x2="18" y2="18" />
              </svg>
            </button>
          </div>

          <!-- Receipt content — visible on screen and in print -->
          <div id="receipt-content" class="px-5 py-4 font-mono text-xs text-gray-900 print:px-0 print:py-0">
            <!-- Store header -->
            <div class="text-center mb-3">
              <p class="text-sm font-bold tracking-wide uppercase">Cahaya Prima</p>
              <p class="text-gray-500 mt-0.5">Struk Pembelian</p>
            </div>

            <div class="border-t border-dashed border-gray-400 my-2" />

            <!-- Transaction info -->
            <div class="space-y-0.5 mb-2">
              <div class="flex justify-between gap-2">
                <span class="text-gray-500">No. Transaksi</span>
                <span class="font-medium text-right">{{ shortId }}</span>
              </div>
              <div class="flex justify-between gap-2">
                <span class="text-gray-500">Tanggal</span>
                <span class="text-right">{{ formattedDate }}</span>
              </div>
              <div class="flex justify-between gap-2">
                <span class="text-gray-500">Waktu</span>
                <span class="text-right">{{ formattedTime }}</span>
              </div>
            </div>

            <div class="border-t border-dashed border-gray-400 my-2" />

            <!-- Item list header -->
            <div class="flex text-gray-500 mb-1">
              <span class="flex-1">Item</span>
              <span class="w-16 text-right">Harga</span>
              <span class="w-8 text-center">Qty</span>
              <span class="w-20 text-right">Subtotal</span>
            </div>

            <!-- Items -->
            <div class="space-y-1 mb-2">
              <div v-for="item in sale.items" :key="item.id" class="flex items-start">
                <span class="flex-1 leading-tight break-words pr-1">{{ item.product?.name ?? 'Produk' }}</span>
                <span class="w-16 text-right shrink-0">{{ formatCurrency(item.sell_price) }}</span>
                <span class="w-8 text-center shrink-0">{{ item.qty }}</span>
                <span class="w-20 text-right shrink-0">{{ formatCurrency(item.subtotal) }}</span>
              </div>
            </div>

            <div class="border-t border-dashed border-gray-400 my-2" />

            <!-- Totals -->
            <div class="space-y-0.5 mb-2">
              <div class="flex justify-between gap-2">
                <span class="text-gray-500">Subtotal</span>
                <span>{{ formatCurrency(sale.subtotal) }}</span>
              </div>
              <div v-if="sale.total_discount > 0" class="flex justify-between gap-2">
                <span class="text-gray-500">Diskon</span>
                <span class="text-red-600">-{{ formatCurrency(sale.total_discount) }}</span>
              </div>
              <div class="flex justify-between gap-2 font-bold text-sm">
                <span>TOTAL</span>
                <span>{{ formatCurrency(sale.total) }}</span>
              </div>
            </div>

            <div class="border-t border-dashed border-gray-400 my-2" />

            <!-- Payment info -->
            <div class="space-y-0.5 mb-2">
              <div class="flex justify-between gap-2">
                <span class="text-gray-500">Metode</span>
                <span>{{ sale.payment_method === 'cash' ? 'Tunai' : 'QR / Transfer' }}</span>
              </div>
              <div class="flex justify-between gap-2">
                <span class="text-gray-500">Bayar</span>
                <span>{{ formatCurrency(sale.amount_paid) }}</span>
              </div>
              <div class="flex justify-between gap-2">
                <span class="text-gray-500">Kembali</span>
                <span class="font-semibold">{{ formatCurrency(sale.change_amount) }}</span>
              </div>
            </div>

            <div class="border-t border-dashed border-gray-400 my-2" />

            <!-- Footer -->
            <div class="text-center text-gray-500 mt-2 space-y-0.5">
              <p>Terima kasih atas kunjungan Anda</p>
              <p>Barang yang sudah dibeli</p>
              <p>tidak dapat dikembalikan</p>
            </div>
          </div>

          <!-- Action buttons — screen only -->
          <div class="flex gap-3 border-t border-gray-100 px-5 py-4 sticky bottom-0 bg-white print:hidden">
            <button
              type="button"
              class="flex-1 inline-flex items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white text-gray-700 text-sm font-medium h-11 px-4 hover:bg-gray-50 transition-colors min-h-[44px]"
              @click="printReceipt"
            >
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <polyline points="6 9 6 2 18 2 18 9" />
                <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2" />
                <rect x="6" y="14" width="12" height="8" />
              </svg>
              Cetak Struk
            </button>
            <button
              type="button"
              class="flex-1 inline-flex items-center justify-center rounded-xl bg-slate-900 text-white text-sm font-medium h-11 px-4 hover:bg-slate-800 transition-colors min-h-[44px]"
              @click="handleNewTransaction"
            >
              Transaksi Baru
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import type { Sale } from '@/services/posService';

// ── Props & Emits ──────────────────────────────────────────────────────────────

const props = defineProps<{
  sale: Sale | null;
  modelValue: boolean;
}>();

const emit = defineEmits<{
  'update:modelValue': [value: boolean];
  'new-transaction': [];
}>();

// ── Computed ───────────────────────────────────────────────────────────────────

/** Show only the last 8 chars of the UUID as the transaction number */
const shortId = computed(() => {
  if (!props.sale?.id) return '-';
  return props.sale.id.replace(/-/g, '').slice(-8).toUpperCase();
});

const formattedDate = computed(() => {
  if (!props.sale?.created_at) return '-';
  return new Date(props.sale.created_at).toLocaleDateString('id-ID', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  });
});

const formattedTime = computed(() => {
  if (!props.sale?.created_at) return '-';
  return new Date(props.sale.created_at).toLocaleTimeString('id-ID', {
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
    hour12: false,
  });
});

// ── Helpers ────────────────────────────────────────────────────────────────────

function formatCurrency(value: number): string {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(value);
}

// ── Actions ────────────────────────────────────────────────────────────────────

function printReceipt() {
  window.print();
}

function handleClose() {
  emit('update:modelValue', false);
}

function handleNewTransaction() {
  emit('new-transaction');
  emit('update:modelValue', false);
}
</script>

<style scoped>
/* Modal transition */
.modal-fade-enter-active,
.modal-fade-leave-active {
  transition: opacity 0.15s ease;
}
.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
}
</style>

<style>
/* ── Print styles ─────────────────────────────────────────────────────────────
   These are intentionally NOT scoped so they apply globally during window.print().
   They hide everything except the receipt content and reset layout for thermal printers.
   ─────────────────────────────────────────────────────────────────────────── */
@media print {
  /* Hide everything on the page */
  body > * {
    display: none !important;
  }

  /* Show only the Teleport portal root that contains our receipt */
  body > div[data-v-app],
  body > #app {
    display: block !important;
  }

  /* Hide all modal chrome — backdrop, header, buttons */
  .fixed.inset-0.z-50 {
    position: static !important;
    background: none !important;
    display: block !important;
  }

  /* The dialog card */
  [role="dialog"] {
    position: static !important;
    max-height: none !important;
    overflow: visible !important;
    box-shadow: none !important;
    border-radius: 0 !important;
    width: 80mm !important; /* 80mm thermal printer; change to 58mm if needed */
    margin: 0 auto !important;
    font-family: 'Courier New', Courier, monospace !important;
    font-size: 10pt !important;
    color: #000 !important;
    background: #fff !important;
  }

  /* Hide non-receipt elements */
  .print\:hidden {
    display: none !important;
  }

  /* Receipt content area */
  #receipt-content {
    padding: 0 !important;
    font-family: 'Courier New', Courier, monospace !important;
    font-size: 10pt !important;
    color: #000 !important;
  }

  /* Ensure dashed borders print */
  .border-dashed {
    border-style: dashed !important;
    border-color: #000 !important;
  }

  /* Remove any page margins */
  @page {
    margin: 4mm;
    size: 80mm auto;
  }
}
</style>
