<template>
  <div class="max-w-lg space-y-5">
    <div>
      <h2 class="text-lg font-semibold">Transaksi Keluar</h2>
      <p class="text-sm text-muted-foreground">Catat penjualan atau pengeluaran barang</p>
    </div>

    <Transition name="fade">
      <div v-if="successMsg" class="flex items-center gap-3 rounded-xl bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
        <span>✓</span><span>{{ successMsg }}</span>
        <button @click="successMsg = ''" class="ml-auto opacity-60 hover:opacity-100">✕</button>
      </div>
    </Transition>

    <div class="rounded-xl border bg-card p-6 space-y-5">
      <div v-if="formError" class="alert-error">{{ formError }}</div>

      <div class="field">
        <label class="field-label">Produk <span class="req">*</span></label>
        <select v-model="form.product_id" class="input-base" :disabled="submitting || loadingProducts">
          <option value="">{{ loadingProducts ? 'Memuat...' : '— Pilih Produk —' }}</option>
          <option v-for="p in products" :key="p.id" :value="p.id">
            {{ p.name }} ({{ p.sku }}) — Stok: {{ p.current_stock }} {{ p.unit }}
          </option>
        </select>
      </div>

      <div v-if="selectedProduct" class="rounded-lg bg-muted/50 px-3 py-2 text-sm">
        Stok tersedia: <strong>{{ selectedProduct.current_stock }}</strong> {{ selectedProduct.unit }}
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div class="field">
          <label class="field-label">Jumlah <span class="req">*</span></label>
          <input v-model="form.quantity" type="number" min="1" step="1" class="input-base" placeholder="0" :disabled="submitting" />
        </div>
        <div class="field">
          <label class="field-label">Tanggal <span class="req">*</span></label>
          <input v-model="form.transaction_date" type="date" class="input-base" :disabled="submitting" />
        </div>
      </div>

      <div class="field">
        <label class="field-label">Harga Jual per Unit (Rp) <span class="req">*</span></label>
        <input v-model="form.price_per_unit" type="number" min="0" class="input-base" placeholder="0" :disabled="submitting" />
      </div>

      <div v-if="currentStock !== null" class="rounded-lg bg-primary/5 border border-primary/20 px-4 py-3 text-sm">
        Stok terbaru: <strong class="text-primary">{{ currentStock }}</strong> {{ lastUnit }}
      </div>

      <div class="flex justify-end pt-2 border-t">
        <button class="btn-primary" @click="submitForm" :disabled="submitting || loadingProducts">
          <span v-if="submitting" class="inline-flex items-center gap-2"><span class="spinner" />Menyimpan...</span>
          <span v-else>Simpan Transaksi</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { getProducts, type Product } from '@/services/productService';
import { createTransactionOut } from '@/services/transactionService';
import { useToast } from '@/composables/useToast';

const toast = useToast();
const products = ref<Product[]>([]);
const loadingProducts = ref(false);
const submitting = ref(false);
const formError = ref('');
const successMsg = ref('');
const currentStock = ref<number | null>(null);
const lastUnit = ref('');

const form = ref({ product_id: '', quantity: '', transaction_date: today(), price_per_unit: '' });
const selectedProduct = computed(() => products.value.find((p) => p.id === form.value.product_id) ?? null);

function today() { return new Date().toISOString().split('T')[0]; }

async function loadAllProducts() {
  loadingProducts.value = true;
  try {
    const r = await getProducts({ page: 1 });
    products.value = r.data;
    if (r.last_page > 1) {
      const rest = await Promise.all(Array.from({ length: r.last_page - 1 }, (_, i) => getProducts({ page: i + 2 })));
      rest.forEach((p) => products.value.push(...p.data));
    }
  } catch {} finally { loadingProducts.value = false; }
}
onMounted(loadAllProducts);

function validate() {
  if (!form.value.product_id) return 'Produk wajib dipilih.';
  const qty = Number(form.value.quantity);
  if (!form.value.quantity || isNaN(qty) || !Number.isInteger(qty)) return 'Jumlah harus berupa bilangan bulat.';
  if (qty <= 0) return 'Jumlah harus lebih dari 0.';
  const product = selectedProduct.value;
  if (product && qty > product.current_stock) return 'Jumlah melebihi stok yang tersedia.';
  if (!form.value.transaction_date) return 'Tanggal wajib diisi.';
  const price = Number(form.value.price_per_unit);
  if (form.value.price_per_unit === '' || isNaN(price) || price < 0) return 'Harga jual tidak valid.';
  return null;
}

async function submitForm() {
  formError.value = ''; successMsg.value = ''; currentStock.value = null;
  const err = validate();
  if (err) { formError.value = err; return; }
  submitting.value = true;
  try {
    const product = selectedProduct.value;
    const result = await createTransactionOut({
      product_id: form.value.product_id,
      quantity: Number(form.value.quantity),
      transaction_date: form.value.transaction_date,
      price_per_unit: Number(form.value.price_per_unit),
    });
    currentStock.value = result.current_stock;
    lastUnit.value = product?.unit ?? '';
    const idx = products.value.findIndex((p) => p.id === form.value.product_id);
    if (idx !== -1) products.value[idx] = { ...products.value[idx], current_stock: result.current_stock };
    successMsg.value = `Transaksi berhasil. Stok terbaru: ${result.current_stock} ${product?.unit ?? ''}.`;
    toast.success('Transaksi keluar berhasil disimpan.');
    form.value = { product_id: '', quantity: '', transaction_date: today(), price_per_unit: '' };
  } catch (err: unknown) {
    const e = err as { response?: { status?: number; data?: { error?: { code?: string; message?: string } } } };
    const status = e?.response?.status;
    const code = e?.response?.data?.error?.code;
    const msg = e?.response?.data?.error?.message;
    if (status === 401) formError.value = 'Sesi habis, silakan login ulang.';
    else if (status === 403) formError.value = 'Anda tidak memiliki akses untuk operasi ini.';
    else if (code === 'BUSINESS_RULE_VIOLATION') formError.value = 'Jumlah melebihi stok yang tersedia.';
    else formError.value = msg ?? 'Gagal menyimpan transaksi.';
  } finally { submitting.value = false; }
}
</script>

<style scoped>
@reference "../../css/app.css";
.field { @apply space-y-1.5; }
.field-label { @apply text-sm font-medium; }
.req { @apply text-destructive; }
.input-base { @apply flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:opacity-50 transition-colors; }
.btn-primary { @apply inline-flex items-center justify-center rounded-md bg-primary text-primary-foreground text-sm font-medium h-9 px-4 hover:bg-primary/90 disabled:opacity-50 transition-colors; }
.alert-error { @apply rounded-lg bg-destructive/10 border border-destructive/20 px-3 py-2.5 text-sm text-destructive; }
.spinner { @apply inline-block w-4 h-4 border-2 border-primary-foreground/30 border-t-primary-foreground rounded-full animate-spin; }
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
