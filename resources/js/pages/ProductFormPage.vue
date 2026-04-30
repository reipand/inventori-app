<template>
  <div class="max-w-2xl space-y-5">
    <div class="flex items-center gap-3">
      <RouterLink to="/products" class="btn-ghost-sm">← Kembali</RouterLink>
      <div>
        <h2 class="text-lg font-semibold">{{ isEdit ? 'Edit Produk' : 'Tambah Produk' }}</h2>
        <p class="text-sm text-muted-foreground">{{ isEdit ? 'Perbarui data produk' : 'Daftarkan produk baru' }}</p>
      </div>
    </div>

    <div v-if="loadingProduct" class="rounded-xl border bg-card p-6 space-y-4">
      <div v-for="i in 5" :key="i" class="skeleton h-10 rounded-md" />
    </div>

    <div v-else class="rounded-xl border bg-card p-6 space-y-5">
      <div v-if="formError" class="alert-error">{{ formError }}</div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="field">
          <label class="field-label">Nama Produk <span class="req">*</span></label>
          <input v-model="form.name" class="input-base" :class="{ 'border-destructive': fe.name }" placeholder="Nama produk" :disabled="submitting" />
          <p v-if="fe.name" class="field-error">{{ fe.name }}</p>
        </div>
        <div class="field">
          <label class="field-label">Kode SKU <span class="req">*</span></label>
          <input v-model="form.sku" class="input-base" :class="{ 'border-destructive': fe.sku }" placeholder="SKU-001" :disabled="submitting" />
          <p v-if="fe.sku" class="field-error">{{ fe.sku }}</p>
        </div>
        <div class="field">
          <label class="field-label">Kategori <span class="req">*</span></label>
          <select v-model="form.category_id" class="input-base" :class="{ 'border-destructive': fe.category_id }" :disabled="submitting">
            <option value="">Pilih kategori</option>
            <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
          </select>
          <p v-if="fe.category_id" class="field-error">{{ fe.category_id }}</p>
        </div>
        <div class="field">
          <label class="field-label">Satuan <span class="req">*</span></label>
          <input v-model="form.unit" class="input-base" :class="{ 'border-destructive': fe.unit }" placeholder="botol, karton, dus" :disabled="submitting" />
          <p v-if="fe.unit" class="field-error">{{ fe.unit }}</p>
        </div>
        <div class="field">
          <label class="field-label">Harga Beli (Rp) <span class="req">*</span></label>
          <input v-model.number="form.buy_price" type="number" min="0" class="input-base" :class="{ 'border-destructive': fe.buy_price }" placeholder="0" :disabled="submitting" />
          <p v-if="fe.buy_price" class="field-error">{{ fe.buy_price }}</p>
        </div>
        <div class="field">
          <label class="field-label">Harga Jual (Rp) <span class="req">*</span></label>
          <input v-model.number="form.sell_price" type="number" min="0" class="input-base" :class="{ 'border-destructive': fe.sell_price }" placeholder="0" :disabled="submitting" />
          <p v-if="fe.sell_price" class="field-error">{{ fe.sell_price }}</p>
        </div>
        <div class="field">
          <label class="field-label">Stok Minimum <span class="req">*</span></label>
          <input v-model.number="form.min_stock" type="number" min="0" class="input-base" :class="{ 'border-destructive': fe.min_stock }" placeholder="0" :disabled="submitting" />
          <p v-if="fe.min_stock" class="field-error">{{ fe.min_stock }}</p>
        </div>
        <div v-if="isEdit" class="field">
          <label class="field-label">COGS (Harga Pokok)</label>
          <div class="input-base bg-muted/40 text-muted-foreground cursor-default select-none flex items-center">
            {{ cogsDisplay }}
          </div>
          <p class="text-xs text-muted-foreground mt-1">Diperbarui otomatis dari invoice pembelian.</p>
        </div>
      </div>

      <div class="flex justify-end gap-3 pt-2 border-t">
        <RouterLink to="/products" class="btn-outline">Batal</RouterLink>
        <button class="btn-primary" @click="submitForm" :disabled="submitting">
          {{ submitting ? 'Menyimpan...' : 'Simpan' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import { getProduct, createProduct, updateProduct, type ProductPayload } from '@/services/productService';
import { getCategories, type Category } from '@/services/categoryService';
import { useToast } from '@/composables/useToast';

const route = useRoute();
const router = useRouter();
const toast = useToast();

const productId = computed(() => route.params.id as string | undefined);
const isEdit = computed(() => !!productId.value);

const categories = ref<Category[]>([]);
const loadingProduct = ref(false);
const submitting = ref(false);
const formError = ref('');
const fe = ref<Record<string, string>>({});

const form = ref<ProductPayload>({ name: '', sku: '', category_id: '', unit: '', buy_price: 0, sell_price: 0, min_stock: 0 });

const cogs = ref(0);

const cogsDisplay = computed(() =>
  new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(cogs.value)
);

onMounted(async () => {
  getCategories().then((c) => (categories.value = c)).catch(() => {});
  if (isEdit.value && productId.value) {
    loadingProduct.value = true;
    try {
      const p = await getProduct(productId.value);
      form.value = { name: p.name, sku: p.sku, category_id: p.category_id, unit: p.unit, buy_price: p.buy_price, sell_price: p.sell_price, min_stock: p.min_stock };
      cogs.value = p.cogs;
    } catch { formError.value = 'Gagal memuat data produk.'; }
    finally { loadingProduct.value = false; }
  }
});

function validate() {
  const e: Record<string, string> = {};
  if (!form.value.name.trim()) e.name = 'Nama tidak boleh kosong.';
  if (!form.value.sku.trim()) e.sku = 'SKU tidak boleh kosong.';
  if (!form.value.category_id) e.category_id = 'Kategori harus dipilih.';
  if (!form.value.unit.trim()) e.unit = 'Satuan tidak boleh kosong.';
  if (form.value.buy_price < 0) e.buy_price = 'Harga beli tidak boleh negatif.';
  if (form.value.sell_price < 0) e.sell_price = 'Harga jual tidak boleh negatif.';
  if (form.value.min_stock < 0) e.min_stock = 'Stok minimum tidak boleh kurang dari 0.';
  fe.value = e;
  return Object.keys(e).length === 0;
}

async function submitForm() {
  formError.value = ''; fe.value = {};
  if (!validate()) return;
  submitting.value = true;
  try {
    const payload: ProductPayload = { ...form.value, name: form.value.name.trim(), sku: form.value.sku.trim(), unit: form.value.unit.trim() };
    if (isEdit.value && productId.value) { await updateProduct(productId.value, payload); toast.success('Produk berhasil diperbarui.'); }
    else { await createProduct(payload); toast.success('Produk berhasil ditambahkan.'); }
    router.push('/products');
  } catch (err: unknown) {
    const e = err as { response?: { status?: number; data?: { error?: { code?: string; message?: string; fields?: string[] } } } };
    const status = e?.response?.status;
    const code = e?.response?.data?.error?.code;
    const msg = e?.response?.data?.error?.message;
    if (status === 409 || code === 'CONFLICT') { fe.value.sku = 'Kode SKU sudah digunakan.'; return; }
    formError.value = msg ?? 'Gagal menyimpan produk.';
  } finally { submitting.value = false; }
}
</script>

<style scoped>
@reference "../../css/app.css";
.field { @apply space-y-1.5; }
.field-label { @apply text-sm font-medium; }
.field-error { @apply text-xs text-destructive; }
.req { @apply text-destructive; }
.input-base { @apply flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:opacity-50 transition-colors; }
.btn-primary { @apply inline-flex items-center justify-center rounded-md bg-primary text-primary-foreground text-sm font-medium h-9 px-4 hover:bg-primary/90 disabled:opacity-50 transition-colors; }
.btn-outline { @apply inline-flex items-center justify-center rounded-md border text-sm font-medium h-9 px-4 hover:bg-accent disabled:opacity-50 transition-colors; }
.btn-ghost-sm { @apply inline-flex items-center justify-center rounded-md text-sm text-muted-foreground h-8 px-2 hover:bg-accent hover:text-foreground transition-colors; }
.alert-error { @apply rounded-lg bg-destructive/10 border border-destructive/20 px-3 py-2.5 text-sm text-destructive; }
.skeleton { @apply bg-muted animate-pulse; }
</style>
