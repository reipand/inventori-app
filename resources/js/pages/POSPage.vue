<template>
  <!-- POS / Kasir Page -->
  <div class="pos-root">

    <!--
      Layout:
      - Desktop/tablet (lg+): two-column — cart left 2/3, summary right 1/3
      - Mobile: single column — search → cart → summary → checkout
    -->
    <div class="pos-layout">

      <!-- ══ LEFT: Search + Cart ══ -->
      <div class="pos-left">

        <!-- Search bar -->
        <div class="pos-card">
          <div class="relative">
            <svg class="search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
            </svg>
            <input
              ref="searchInputRef"
              v-model="searchQuery"
              class="search-input"
              placeholder="Cari produk berdasarkan nama atau SKU..."
              autocomplete="off"
              @input="onSearchInput"
              @keydown.enter="onSearchEnter"
              @keydown.escape="clearSearch"
            />
            <button
              v-if="searchQuery"
              class="search-clear"
              aria-label="Hapus pencarian"
              @click="clearSearch"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>

          <!-- Search results -->
          <Transition name="dropdown">
            <div v-if="showSearchResults" class="search-results">
              <div v-if="searchLoading" class="search-loading">
                <svg class="animate-spin w-4 h-4 text-blue-500" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                <span class="text-sm text-gray-500">Mencari...</span>
              </div>
              <template v-else-if="searchResults.length > 0">
                <button
                  v-for="product in searchResults"
                  :key="product.id"
                  class="search-result-item"
                  :disabled="product.current_stock === 0"
                  @click="addToCart(product)"
                >
                  <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 truncate">{{ product.name }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">SKU: {{ product.sku }} &middot; Stok: {{ product.current_stock }} {{ product.unit }}</p>
                  </div>
                  <div class="text-right shrink-0 ml-3">
                    <p class="text-sm font-semibold text-gray-900">{{ fmt(product.sell_price) }}</p>
                    <span v-if="product.current_stock === 0" class="text-xs text-red-500 font-medium">Habis</span>
                    <span v-else class="text-xs text-green-600 font-medium">+ Tambah</span>
                  </div>
                </button>
              </template>
              <div v-else class="px-4 py-6 text-center text-sm text-gray-500">
                Produk "{{ searchQuery }}" tidak ditemukan
              </div>
            </div>
          </Transition>
        </div>

        <!-- Cart items -->
        <div class="pos-card flex-1">
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">
              Keranjang
              <span
                v-if="cartStore.items.length > 0"
                class="ml-1.5 inline-flex items-center justify-center w-5 h-5 rounded-full bg-blue-600 text-white text-xs font-bold"
              >{{ cartStore.items.length }}</span>
            </h2>
            <button
              v-if="cartStore.items.length > 0"
              class="text-xs text-red-500 hover:text-red-700 font-medium transition-colors"
              @click="showClearConfirm = true"
            >
              Kosongkan
            </button>
          </div>

          <!-- Empty state -->
          <div v-if="cartStore.items.length === 0" class="cart-empty">
            <div class="w-14 h-14 rounded-2xl bg-gray-100 flex items-center justify-center mx-auto mb-3">
              <svg class="w-7 h-7 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
              </svg>
            </div>
            <p class="text-sm font-medium text-gray-600">Keranjang kosong</p>
            <p class="text-xs text-gray-400 mt-1">Cari produk di atas untuk menambahkan ke keranjang</p>
          </div>

          <!-- Cart items list -->
          <div v-else class="space-y-2">
            <TransitionGroup name="cart-item">
              <div
                v-for="item in cartStore.items"
                :key="item.product_id"
                class="cart-item"
              >
                <!-- Name + delete -->
                <div class="flex items-start justify-between gap-2 mb-2">
                  <p class="text-sm font-medium text-gray-900 leading-tight">{{ item.product_name }}</p>
                  <button
                    class="shrink-0 w-6 h-6 flex items-center justify-center rounded-md text-gray-400 hover:text-red-500 hover:bg-red-50 transition-colors"
                    aria-label="Hapus item"
                    @click="cartStore.removeItem(item.product_id)"
                  >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                  </button>
                </div>

                <!-- Qty + price + discount + subtotal -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 items-end">
                  <!-- Qty -->
                  <div>
                    <p class="text-xs text-gray-400 mb-1">Qty</p>
                    <div class="flex items-center gap-1">
                      <button class="qty-btn" aria-label="Kurangi" @click="cartStore.updateQty(item.product_id, item.qty - 1)">&#8722;</button>
                      <input
                        :value="item.qty"
                        type="number"
                        min="0"
                        class="qty-input"
                        aria-label="Jumlah"
                        @change="onQtyChange(item.product_id, ($event.target as HTMLInputElement).value)"
                      />
                      <button class="qty-btn" aria-label="Tambah" @click="cartStore.updateQty(item.product_id, item.qty + 1)">+</button>
                    </div>
                  </div>

                  <!-- Sell price -->
                  <div>
                    <p class="text-xs text-gray-400 mb-1">Harga</p>
                    <p class="text-sm font-medium text-gray-700 h-7 flex items-center">{{ fmt(item.sell_price) }}</p>
                  </div>

                  <!-- Discount per item -->
                  <div>
                    <p class="text-xs text-gray-400 mb-1">Diskon/item</p>
                    <div class="relative">
                      <span class="absolute left-2 top-1/2 -translate-y-1/2 text-xs text-gray-400 pointer-events-none">Rp</span>
                      <input
                        :value="item.discount_per_item || ''"
                        type="number"
                        min="0"
                        :max="item.sell_price"
                        placeholder="0"
                        class="discount-input"
                        aria-label="Diskon per item"
                        @change="onDiscountChange(item.product_id, item.sell_price, ($event.target as HTMLInputElement).value)"
                      />
                    </div>
                  </div>

                  <!-- Subtotal -->
                  <div class="text-right">
                    <p class="text-xs text-gray-400 mb-1">Subtotal</p>
                    <p class="text-sm font-semibold text-gray-900 h-7 flex items-center justify-end">
                      {{ fmt((item.sell_price - item.discount_per_item) * item.qty) }}
                    </p>
                  </div>
                </div>
              </div>
            </TransitionGroup>
          </div>
        </div>

        <!-- Summary panel — mobile only (shown below cart) -->
        <div class="pos-card lg:hidden">
          <div class="summary-inner">
            <SummaryContent
              :cart-store="cartStore"
              :checkout-loading="checkoutLoading"
              @checkout="handleCheckout"
            />
          </div>
        </div>
      </div>

      <!-- ══ RIGHT: Summary (desktop/tablet) ══ -->
      <div class="pos-right hidden lg:block">
        <div class="pos-card pos-sticky">
          <SummaryContent
            :cart-store="cartStore"
            :checkout-loading="checkoutLoading"
            @checkout="handleCheckout"
          />
        </div>
      </div>
    </div>

    <!-- Receipt Modal -->
    <ReceiptModal
      v-model="showReceipt"
      :sale="completedSale"
      @new-transaction="onReceiptClose"
    />

    <!-- Clear cart confirmation dialog -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="showClearConfirm" class="modal-overlay" @click.self="showClearConfirm = false">
          <div class="modal-box max-w-sm">
            <div class="w-10 h-10 rounded-xl bg-orange-50 flex items-center justify-center mb-4">
              <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
              </svg>
            </div>
            <h3 class="text-base font-semibold text-gray-900">Kosongkan Keranjang?</h3>
            <p class="mt-1.5 text-sm text-gray-500">Semua item di keranjang akan dihapus. Tindakan ini tidak dapat dibatalkan.</p>
            <div class="flex justify-end gap-2 mt-5">
              <button class="btn-outline" @click="showClearConfirm = false">Batal</button>
              <button class="btn-danger" @click="doClearCart">Ya, Kosongkan</button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { useCartStore } from '@/stores/cartStore';
import { createSale, type Sale } from '@/services/posService';
import { getProducts, type Product } from '@/services/productService';
import { useToast } from '@/composables/useToast';
import ReceiptModal from '@/components/ReceiptModal.vue';
import SummaryContent from '@/components/SummaryContent.vue';

// ── Store & composables ──────────────────────────────────────────────────────
const cartStore = useCartStore();
const toast = useToast();

// ── Search state ─────────────────────────────────────────────────────────────
const searchQuery = ref('');
const searchResults = ref<Product[]>([]);
const searchLoading = ref(false);
const hasSearched = ref(false);
const searchInputRef = ref<HTMLInputElement | null>(null);
let searchTimer: ReturnType<typeof setTimeout> | null = null;

const showSearchResults = computed(
  () => searchQuery.value.length >= 2 && (searchLoading.value || hasSearched.value)
);

// ── Checkout / receipt state ──────────────────────────────────────────────────
const checkoutLoading = ref(false);
const showReceipt = ref(false);
const completedSale = ref<Sale | null>(null);

// ── UI state ──────────────────────────────────────────────────────────────────
const showClearConfirm = ref(false);

// ── Formatting ────────────────────────────────────────────────────────────────
function fmt(value: number): string {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(value);
}

// ── Search ────────────────────────────────────────────────────────────────────
function onSearchInput() {
  if (searchTimer) clearTimeout(searchTimer);
  const query = searchQuery.value.trim();
  if (query.length < 2) {
    searchResults.value = [];
    hasSearched.value = false;
    return;
  }
  searchLoading.value = true;
  hasSearched.value = false;
  searchTimer = setTimeout(() => doSearch(query), 300);
}

async function doSearch(query: string) {
  try {
    const result = await getProducts({ search: query, page: 1 });
    searchResults.value = result.data;
    hasSearched.value = true;
  } catch {
    searchResults.value = [];
    hasSearched.value = true;
  } finally {
    searchLoading.value = false;
  }
}

function onSearchEnter() {
  // Barcode scanner support: if exactly one result, add it directly
  if (searchResults.value.length === 1 && searchResults.value[0].current_stock > 0) {
    addToCart(searchResults.value[0]);
  }
}

function clearSearch() {
  searchQuery.value = '';
  searchResults.value = [];
  hasSearched.value = false;
  if (searchTimer) clearTimeout(searchTimer);
}

// ── Cart operations ───────────────────────────────────────────────────────────
function addToCart(product: Product) {
  if (product.current_stock === 0) return;
  cartStore.addItem(product);
  clearSearch();
  searchInputRef.value?.focus();
}

function onQtyChange(productId: string, rawValue: string) {
  const qty = parseInt(rawValue, 10);
  cartStore.updateQty(productId, isNaN(qty) ? 0 : qty);
}

function onDiscountChange(productId: string, sellPrice: number, rawValue: string) {
  const discount = parseFloat(rawValue) || 0;
  const capped = Math.min(Math.max(0, discount), sellPrice);
  cartStore.updateDiscount(productId, capped);
}

function doClearCart() {
  cartStore.clearCart();
  showClearConfirm.value = false;
}

// ── Checkout ──────────────────────────────────────────────────────────────────
async function handleCheckout() {
  if (!cartStore.isValid || checkoutLoading.value) return;

  checkoutLoading.value = true;
  try {
    const payload = {
      items: cartStore.items.map((item) => ({
        product_id: item.product_id,
        qty: item.qty,
        sell_price: item.sell_price,
        cogs: item.cogs,
        discount_per_item: item.discount_per_item,
      })),
      payment_method: cartStore.paymentMethod,
      amount_paid: cartStore.paymentMethod === 'qr' ? cartStore.total : cartStore.amountPaid,
      subtotal: cartStore.subtotal,
      total_discount: cartStore.totalDiscount,
      total: cartStore.total,
    };

    const sale = await createSale(payload);
    completedSale.value = sale;
    cartStore.clearCart();
    showReceipt.value = true;
    toast.success('Transaksi berhasil');
  } catch (err: unknown) {
    const e = err as { response?: { data?: { error?: { message?: string } } } };
    const msg = e?.response?.data?.error?.message ?? 'Checkout gagal. Silakan coba lagi.';
    toast.error(msg);
  } finally {
    checkoutLoading.value = false;
  }
}

function onReceiptClose() {
  // Called when user clicks "Transaksi Baru" — cart is already cleared by handleCheckout
  searchInputRef.value?.focus();
}
</script>

<style scoped>
@reference "../../css/app.css";

/* ── Root ── */
.pos-root {
  @apply relative;
}

/* ── Two-column layout ── */
.pos-layout {
  @apply flex flex-col gap-4 lg:flex-row lg:items-start;
}

.pos-left {
  @apply flex flex-col gap-4 w-full lg:w-2/3;
}

.pos-right {
  @apply w-full lg:w-1/3;
}

.pos-sticky {
  @apply lg:sticky lg:top-4;
}

/* ── Card ── */
.pos-card {
  @apply rounded-2xl border border-gray-200 bg-white p-4 shadow-sm;
}

/* ── Search ── */
.search-icon {
  @apply absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none;
}

.search-input {
  @apply w-full h-11 rounded-xl border border-gray-200 bg-gray-50/60 pl-10 pr-10
    text-sm text-gray-900 placeholder:text-gray-400
    focus:outline-none focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400
    transition-all;
}

.search-clear {
  @apply absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors;
}

.search-results {
  @apply mt-2 rounded-xl border border-gray-200 bg-white shadow-lg overflow-hidden max-h-72 overflow-y-auto;
}

.search-loading {
  @apply flex items-center gap-2 px-4 py-3;
}

.search-result-item {
  @apply w-full flex items-center px-4 py-3 text-left hover:bg-blue-50 transition-colors
    border-b border-gray-50 last:border-0 disabled:opacity-50 disabled:cursor-not-allowed;
}

/* ── Cart ── */
.cart-empty {
  @apply py-10 text-center;
}

.cart-item {
  @apply rounded-xl border border-gray-100 bg-gray-50/50 p-3 hover:border-gray-200 transition-colors;
}

/* ── Qty controls ── */
.qty-btn {
  @apply w-7 h-7 flex items-center justify-center rounded-lg border border-gray-200
    text-gray-600 hover:bg-gray-100 hover:border-gray-300 font-medium text-base
    transition-all active:scale-95 shrink-0;
}

.qty-input {
  @apply w-12 h-7 text-center text-sm font-medium border border-gray-200 rounded-lg
    focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400
    [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none;
}

.discount-input {
  @apply w-full h-7 pl-7 pr-2 text-sm border border-gray-200 rounded-lg
    focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400
    [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none;
}

/* ── Buttons ── */
.btn-outline {
  @apply inline-flex items-center justify-center rounded-lg border border-gray-200
    text-sm font-medium h-9 px-4 text-gray-700 hover:bg-gray-50 disabled:opacity-50 transition-all;
}

.btn-danger {
  @apply inline-flex items-center justify-center rounded-lg bg-red-600 text-white
    text-sm font-medium h-9 px-4 hover:bg-red-700 disabled:opacity-50 transition-all;
}

/* ── Modal ── */
.modal-overlay {
  @apply fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4;
}

.modal-box {
  @apply w-full max-w-md rounded-2xl bg-white border border-gray-200 shadow-2xl p-6;
}

/* ── Transitions ── */
.dropdown-enter-active,
.dropdown-leave-active {
  transition: opacity 0.15s ease, transform 0.15s ease;
}
.dropdown-enter-from,
.dropdown-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}

.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.15s ease;
}
.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.cart-item-enter-active,
.cart-item-leave-active {
  transition: all 0.2s ease;
}
.cart-item-enter-from {
  opacity: 0;
  transform: translateX(-8px);
}
.cart-item-leave-to {
  opacity: 0;
  transform: translateX(8px);
}
</style>
