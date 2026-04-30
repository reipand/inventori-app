<template>
  <div class="summary-root">
    <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-4">Ringkasan</h2>

    <!-- Subtotal, discount, total -->
    <div class="space-y-2 mb-4">
      <div class="flex justify-between text-sm">
        <span class="text-gray-500">Subtotal</span>
        <span class="font-medium text-gray-800">{{ fmt(cartStore.subtotal) }}</span>
      </div>
      <div v-if="cartStore.totalDiscount > 0" class="flex justify-between text-sm">
        <span class="text-gray-500">Total Diskon</span>
        <span class="font-medium text-red-600">-{{ fmt(cartStore.totalDiscount) }}</span>
      </div>
      <div class="flex justify-between text-base font-bold border-t border-gray-100 pt-2 mt-2">
        <span class="text-gray-900">Total</span>
        <span class="text-gray-900">{{ fmt(cartStore.total) }}</span>
      </div>
    </div>

    <!-- Payment method selector -->
    <div class="mb-4">
      <p class="text-xs font-medium text-gray-500 mb-2">Metode Pembayaran</p>
      <div class="grid grid-cols-2 gap-2">
        <button
          class="payment-method-btn"
          :class="cartStore.paymentMethod === 'cash' ? 'payment-method-active' : 'payment-method-inactive'"
          @click="cartStore.paymentMethod = 'cash'"
        >
          <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
          </svg>
          Tunai
        </button>
        <button
          class="payment-method-btn"
          :class="cartStore.paymentMethod === 'qr' ? 'payment-method-active' : 'payment-method-inactive'"
          @click="cartStore.paymentMethod = 'qr'"
        >
          <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
          </svg>
          QR / Transfer
        </button>
      </div>
    </div>

    <!-- Amount paid input (Tunai only) -->
    <Transition name="slide-down">
      <div v-if="cartStore.paymentMethod === 'cash'" class="mb-4">
        <label class="text-xs font-medium text-gray-500 mb-1.5 block" for="amount-paid">
          Nominal Bayar
        </label>
        <div class="relative">
          <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-500 font-medium pointer-events-none">Rp</span>
          <input
            id="amount-paid"
            v-model.number="cartStore.amountPaid"
            type="number"
            min="0"
            :placeholder="fmt(cartStore.total)"
            class="amount-input"
            @focus="($event.target as HTMLInputElement).select()"
          />
        </div>

        <!-- Change display -->
        <div class="mt-2 rounded-xl p-3" :class="changeStatus.bg">
          <div class="flex justify-between items-center">
            <span class="text-sm font-medium" :class="changeStatus.text">
              {{ changeStatus.label }}
            </span>
            <span class="text-sm font-bold" :class="changeStatus.text">
              {{ fmt(changeStatus.amount) }}
            </span>
          </div>
          <p v-if="isUnderpaid" class="text-xs mt-1" :class="changeStatus.text">
            Kurang {{ fmt(cartStore.total - cartStore.amountPaid) }}
          </p>
        </div>
      </div>
    </Transition>

    <!-- QR info -->
    <Transition name="slide-down">
      <div v-if="cartStore.paymentMethod === 'qr'" class="mb-4 rounded-xl bg-blue-50 border border-blue-100 p-3">
        <div class="flex items-center gap-2">
          <svg class="w-4 h-4 text-blue-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          <p class="text-sm text-blue-700">
            Tagih <span class="font-bold">{{ fmt(cartStore.total) }}</span> via QR / Transfer
          </p>
        </div>
      </div>
    </Transition>

    <!-- Checkout button -->
    <button
      class="checkout-btn"
      :disabled="!cartStore.isValid || checkoutLoading"
      @click="$emit('checkout')"
    >
      <template v-if="checkoutLoading">
        <svg class="animate-spin w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
        </svg>
        Memproses...
      </template>
      <template v-else>
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        Checkout
      </template>
    </button>

    <!-- Disabled reason hint -->
    <p v-if="!cartStore.isValid && !checkoutLoading" class="mt-2 text-xs text-center text-gray-400">
      <template v-if="cartStore.items.length === 0">Keranjang masih kosong</template>
      <template v-else-if="cartStore.paymentMethod === 'cash' && cartStore.amountPaid < cartStore.total">
        Nominal bayar kurang dari total
      </template>
    </p>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

// Props
const props = defineProps<{
  cartStore: {
    items: Array<unknown>;
    paymentMethod: 'cash' | 'qr';
    amountPaid: number;
    subtotal: number;
    totalDiscount: number;
    total: number;
    change: number;
    isValid: boolean;
  };
  checkoutLoading: boolean;
}>();

// Emits
const emit = defineEmits<{
  checkout: [];
}>();

// Formatting
function fmt(value: number): string {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(value);
}

// Change / underpaid status
const isUnderpaid = computed(
  () =>
    props.cartStore.paymentMethod === 'cash' &&
    props.cartStore.amountPaid > 0 &&
    props.cartStore.amountPaid < props.cartStore.total
);

const changeStatus = computed(() => {
  if (props.cartStore.paymentMethod === 'qr') {
    return { label: 'Kembalian', amount: 0, bg: 'bg-gray-50', text: 'text-gray-600' };
  }
  if (props.cartStore.amountPaid === 0) {
    return { label: 'Kembalian', amount: 0, bg: 'bg-gray-50', text: 'text-gray-500' };
  }
  if (isUnderpaid.value) {
    return {
      label: 'Pembayaran kurang',
      amount: props.cartStore.total - props.cartStore.amountPaid,
      bg: 'bg-red-50 border border-red-100',
      text: 'text-red-600',
    };
  }
  return {
    label: 'Kembalian',
    amount: props.cartStore.change,
    bg: 'bg-green-50 border border-green-100',
    text: 'text-green-700',
  };
});
</script>

<style scoped>
@reference "../../css/app.css";

.summary-root {
  @apply flex flex-col;
}

/* Payment method buttons */
.payment-method-btn {
  @apply inline-flex items-center justify-center gap-2 rounded-xl border text-sm font-medium h-10 px-3
    transition-all active:scale-95;
}

.payment-method-active {
  @apply bg-blue-600 border-blue-600 text-white shadow-sm;
}

.payment-method-inactive {
  @apply bg-white border-gray-200 text-gray-600 hover:bg-gray-50 hover:border-gray-300;
}

/* Amount paid input */
.amount-input {
  @apply w-full h-11 rounded-xl border border-gray-200 bg-gray-50/60 pl-10 pr-3
    text-sm font-medium text-gray-900
    focus:outline-none focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400
    transition-all
    [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none;
}

/* Checkout button */
.checkout-btn {
  @apply w-full inline-flex items-center justify-center gap-2 rounded-xl
    bg-blue-600 text-white text-sm font-semibold h-12
    hover:bg-blue-700 active:scale-[0.98]
    disabled:opacity-50 disabled:cursor-not-allowed disabled:active:scale-100
    transition-all shadow-sm;
}

/* Transitions */
.slide-down-enter-active,
.slide-down-leave-active {
  transition: all 0.2s ease;
  overflow: hidden;
}
.slide-down-enter-from,
.slide-down-leave-to {
  opacity: 0;
  max-height: 0;
}
.slide-down-enter-to,
.slide-down-leave-from {
  opacity: 1;
  max-height: 200px;
}
</style>
