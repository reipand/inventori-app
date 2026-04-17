<template>
  <Teleport to="body">
    <Transition name="modal">
      <div v-if="modelValue" class="fixed inset-0 z-50 flex items-end justify-center bg-slate-950/45 p-0 md:items-center md:p-6" @click.self="close">
        <div class="w-full max-w-md rounded-t-[28px] bg-white shadow-2xl md:rounded-[28px]">
          <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5">
            <div>
              <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">{{ mode === 'masuk' ? 'Barang Masuk' : 'Penjualan' }}</p>
              <h3 class="mt-1 text-xl font-semibold text-slate-900">{{ product?.name ?? 'Pilih produk dari daftar' }}</h3>
            </div>
            <button class="rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-600 transition hover:bg-slate-50" @click="close">Tutup</button>
          </div>

          <div class="space-y-4 px-6 py-6">
            <div v-if="!product" class="rounded-2xl bg-amber-50 px-4 py-3 text-sm text-amber-800">
              Pilih produk terlebih dahulu dari tabel produk.
            </div>
            <div>
              <label class="mb-2 block text-sm font-medium text-slate-700">Qty</label>
              <input v-model.number="form.quantity" type="number" min="1" class="h-12 w-full rounded-2xl border border-slate-200 px-4 text-sm outline-none transition focus:border-slate-900">
            </div>
            <div>
              <label class="mb-2 block text-sm font-medium text-slate-700">Tanggal</label>
              <input v-model="form.transaction_date" type="date" class="h-12 w-full rounded-2xl border border-slate-200 px-4 text-sm outline-none transition focus:border-slate-900">
            </div>
            <div v-if="mode === 'masuk'">
              <label class="mb-2 block text-sm font-medium text-slate-700">Supplier</label>
              <input v-model="form.supplier_name" type="text" class="h-12 w-full rounded-2xl border border-slate-200 px-4 text-sm outline-none transition focus:border-slate-900" placeholder="Nama supplier">
            </div>
            <div>
              <label class="mb-2 block text-sm font-medium text-slate-700">{{ mode === 'masuk' ? 'Harga beli' : 'Harga jual' }}</label>
              <input v-model.number="form.price_per_unit" type="number" min="0" class="h-12 w-full rounded-2xl border border-slate-200 px-4 text-sm outline-none transition focus:border-slate-900">
            </div>
            <div v-if="errorMessage" class="rounded-2xl bg-rose-50 px-4 py-3 text-sm text-rose-700">
              {{ errorMessage }}
            </div>
          </div>

          <div class="flex gap-3 border-t border-slate-100 px-6 py-5">
            <button class="flex-1 rounded-2xl border border-slate-200 px-4 py-3 text-sm font-medium text-slate-700" @click="close">Batal</button>
            <button class="flex-1 rounded-2xl bg-slate-900 px-4 py-3 text-sm font-medium text-white disabled:opacity-50" :disabled="submitting || !product" @click="submit">
              {{ submitting ? 'Menyimpan...' : 'Simpan' }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { reactive, ref, watch } from 'vue';
import { createTransactionIn, createTransactionOut } from '@/services/transactionService';
import type { Product } from '@/services/productService';

const props = defineProps<{
  modelValue: boolean;
  mode: 'masuk' | 'keluar';
  product: Product | null;
}>();

const emit = defineEmits<{
  'update:modelValue': [value: boolean];
  'saved': [updatedProduct: Product];
}>();

function today(): string {
  return new Date().toISOString().split('T')[0];
}

const form = reactive({
  quantity: 1,
  transaction_date: today(),
  supplier_name: '',
  price_per_unit: 0,
});

const submitting = ref(false);
const errorMessage = ref('');

watch(
  () => props.modelValue,
  (open) => {
    if (!open) return;
    form.quantity = 1;
    form.transaction_date = today();
    form.supplier_name = props.product?.supplier?.name ?? '';
    form.price_per_unit = props.mode === 'masuk'
      ? Number(props.product?.buy_price ?? 0)
      : Number(props.product?.sell_price ?? 0);
    errorMessage.value = '';
  }
);

function close() {
  emit('update:modelValue', false);
}

async function submit() {
  if (!props.product) return;
  submitting.value = true;
  errorMessage.value = '';

  try {
    if (props.mode === 'masuk') {
      await createTransactionIn({
        product_id: props.product.id,
        quantity: form.quantity,
        transaction_date: form.transaction_date,
        supplier_name: form.supplier_name,
        price_per_unit: form.price_per_unit,
      });

      emit('saved', {
        ...props.product,
        current_stock: props.product.current_stock + form.quantity,
      });
    } else {
      const result = await createTransactionOut({
        items: [{
          product_id: props.product.id,
          qty: form.quantity,
          price: form.price_per_unit || props.product.sell_price,
        }],
        transaction_date: form.transaction_date,
      });

      const updated = result.updated_products?.find((item) => item.id === props.product?.id);
      emit('saved', updated ?? {
        ...props.product,
        current_stock: Math.max(0, props.product.current_stock - form.quantity),
      });
    }

    close();
  } catch (error: any) {
    errorMessage.value = error?.response?.data?.error?.message ?? 'Transaksi gagal diproses.';
  } finally {
    submitting.value = false;
  }
}
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.18s ease, transform 0.18s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
  transform: translateY(10px) scale(0.98);
}
</style>
