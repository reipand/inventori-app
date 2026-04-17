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
          ref="dialogRef"
          role="dialog"
          aria-modal="true"
          :aria-labelledby="titleId"
          class="modal-scale-in relative z-10 w-full bg-white shadow-xl
                 fixed inset-x-0 bottom-0 rounded-t-2xl
                 max-h-[90vh] overflow-y-auto
                 md:static md:rounded-2xl md:max-w-lg md:max-h-[85vh]"
          @keydown="handleKeydown"
        >
          <!-- Header -->
          <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 sticky top-0 bg-white z-10">
            <div>
              <h2 :id="titleId" class="text-base font-semibold text-gray-900">Edit Produk</h2>
              <p class="text-xs text-gray-400 mt-0.5">{{ product?.name }}</p>
            </div>
            <button
              ref="closeButtonRef"
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

          <!-- Body -->
          <div class="px-6 py-5 space-y-4">
            <!-- Server error -->
            <div v-if="serverError" class="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
              {{ serverError }}
            </div>

            <!-- 2-col grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <!-- Nama Produk -->
              <div class="field sm:col-span-2">
                <label class="field-label">Nama Produk <span class="req">*</span></label>
                <input
                  ref="firstFocusableRef"
                  v-model="form.name"
                  type="text"
                  placeholder="Nama produk"
                  class="field-input"
                  :class="fe.name ? 'border-red-400 bg-red-50' : ''"
                  :disabled="submitting"
                  @input="fe.name = ''"
                />
                <p v-if="fe.name" class="field-error">{{ fe.name }}</p>
              </div>

              <!-- SKU -->
              <div class="field">
                <label class="field-label">Kode SKU <span class="req">*</span></label>
                <input
                  v-model="form.sku"
                  type="text"
                  placeholder="SKU-001"
                  class="field-input"
                  :class="fe.sku ? 'border-red-400 bg-red-50' : ''"
                  :disabled="submitting"
                  @input="fe.sku = ''"
                />
                <p v-if="fe.sku" class="field-error">{{ fe.sku }}</p>
              </div>

              <!-- Satuan -->
              <div class="field">
                <label class="field-label">Satuan <span class="req">*</span></label>
                <input
                  v-model="form.unit"
                  type="text"
                  placeholder="botol, karton, dus"
                  class="field-input"
                  :class="fe.unit ? 'border-red-400 bg-red-50' : ''"
                  :disabled="submitting"
                  @input="fe.unit = ''"
                />
                <p v-if="fe.unit" class="field-error">{{ fe.unit }}</p>
              </div>

              <!-- Kategori -->
              <div class="field sm:col-span-2">
                <label class="field-label">Kategori <span class="req">*</span></label>
                <select
                  v-model="form.category_id"
                  class="field-input"
                  :class="fe.category_id ? 'border-red-400 bg-red-50' : ''"
                  :disabled="submitting"
                  @change="fe.category_id = ''"
                >
                  <option value="">Pilih kategori</option>
                  <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                </select>
                <p v-if="fe.category_id" class="field-error">{{ fe.category_id }}</p>
              </div>

              <!-- Harga Beli -->
              <div class="field">
                <label class="field-label">Harga Beli (Rp) <span class="req">*</span></label>
                <input
                  v-model.number="form.buy_price"
                  type="number"
                  min="0"
                  placeholder="0"
                  class="field-input"
                  :class="fe.buy_price ? 'border-red-400 bg-red-50' : ''"
                  :disabled="submitting"
                  @input="fe.buy_price = ''"
                />
                <p v-if="fe.buy_price" class="field-error">{{ fe.buy_price }}</p>
              </div>

              <!-- Harga Jual -->
              <div class="field">
                <label class="field-label">Harga Jual (Rp) <span class="req">*</span></label>
                <input
                  v-model.number="form.sell_price"
                  type="number"
                  min="0"
                  placeholder="0"
                  class="field-input"
                  :class="fe.sell_price ? 'border-red-400 bg-red-50' : ''"
                  :disabled="submitting"
                  @input="fe.sell_price = ''"
                />
                <p v-if="fe.sell_price" class="field-error">{{ fe.sell_price }}</p>
              </div>

              <!-- Stok Minimum -->
              <div class="field">
                <label class="field-label">Stok Minimum <span class="req">*</span></label>
                <input
                  v-model.number="form.min_stock"
                  type="number"
                  min="0"
                  placeholder="0"
                  class="field-input"
                  :class="fe.min_stock ? 'border-red-400 bg-red-50' : ''"
                  :disabled="submitting"
                  @input="fe.min_stock = ''"
                />
                <p v-if="fe.min_stock" class="field-error">{{ fe.min_stock }}</p>
              </div>
            </div>
          </div>

          <!-- Footer -->
          <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-100 sticky bottom-0 bg-white">
            <button
              type="button"
              class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white text-gray-700 text-sm font-medium h-10 px-4 hover:bg-gray-50 transition-colors duration-150 min-h-[44px]"
              :disabled="submitting"
              @click="close"
            >
              Batal
            </button>
            <button
              ref="submitButtonRef"
              type="button"
              class="inline-flex items-center justify-center rounded-md bg-blue-600 text-white text-sm font-medium h-10 px-4 hover:bg-blue-700 disabled:opacity-50 transition-colors duration-150 min-h-[44px]"
              :disabled="submitting"
              @click="submit"
            >
              <span v-if="submitting" class="inline-flex items-center gap-2">
                <svg class="w-4 h-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z" />
                </svg>
                Menyimpan...
              </span>
              <span v-else>Simpan Perubahan</span>
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, reactive, watch, nextTick } from 'vue';
import { updateProduct, type Product } from '@/services/productService';
import { getCategories, type Category } from '@/services/categoryService';
import { useToast } from '@/composables/useToast';

// ── Props & Emits ──────────────────────────────────────────────────────────────

const props = defineProps<{
  modelValue: boolean;
  product: Product | null;
}>();

const emit = defineEmits<{
  'update:modelValue': [value: boolean];
  'saved': [updatedProduct: Product];
}>();

// ── Accessibility ──────────────────────────────────────────────────────────────

const titleId = 'product-edit-modal-title';

// ── Refs ───────────────────────────────────────────────────────────────────────

const dialogRef = ref<HTMLElement | null>(null);
const firstFocusableRef = ref<HTMLInputElement | null>(null);
const submitButtonRef = ref<HTMLButtonElement | null>(null);
const closeButtonRef = ref<HTMLButtonElement | null>(null);

// ── Data ───────────────────────────────────────────────────────────────────────

const categories = ref<Category[]>([]);
const toast = useToast();
const submitting = ref(false);
const serverError = ref('');
const fe = ref<Record<string, string>>({});

const form = reactive({
  name: '',
  sku: '',
  category_id: '' as string | number,
  unit: '',
  buy_price: 0,
  sell_price: 0,
  min_stock: 0,
});

// ── Populate form when modal opens ─────────────────────────────────────────────

watch(
  () => props.modelValue,
  async (open) => {
    if (open && props.product) {
      const p = props.product;
      form.name = p.name;
      form.sku = p.sku;
      form.category_id = p.category_id;
      form.unit = p.unit;
      form.buy_price = p.buy_price;
      form.sell_price = p.sell_price;
      form.min_stock = p.min_stock;
      serverError.value = '';
      fe.value = {};
      submitting.value = false;

      // Load categories if not yet loaded
      if (categories.value.length === 0) {
        getCategories().then((c) => (categories.value = c)).catch(() => {});
      }

      await nextTick();
      firstFocusableRef.value?.focus();
    }
  }
);

// ── Close ──────────────────────────────────────────────────────────────────────

function close() {
  if (submitting.value) return;
  emit('update:modelValue', false);
}

// ── Validation ─────────────────────────────────────────────────────────────────

function validate(): boolean {
  const e: Record<string, string> = {};
  if (!form.name.trim()) e.name = 'Nama tidak boleh kosong.';
  if (!form.sku.trim()) e.sku = 'SKU tidak boleh kosong.';
  if (!form.category_id) e.category_id = 'Kategori harus dipilih.';
  if (!form.unit.trim()) e.unit = 'Satuan tidak boleh kosong.';
  if (form.buy_price < 0) e.buy_price = 'Harga beli tidak boleh negatif.';
  if (form.sell_price < 0) e.sell_price = 'Harga jual tidak boleh negatif.';
  if (form.min_stock < 0) e.min_stock = 'Stok minimum tidak boleh negatif.';
  fe.value = e;
  return Object.keys(e).length === 0;
}

// ── Submit ─────────────────────────────────────────────────────────────────────

async function submit() {
  serverError.value = '';
  if (!validate() || !props.product) return;

  submitting.value = true;
  try {
    const payload = {
      name: form.name.trim(),
      sku: form.sku.trim(),
      category_id: form.category_id,
      unit: form.unit.trim(),
      buy_price: form.buy_price,
      sell_price: form.sell_price,
      min_stock: form.min_stock,
    };

    await updateProduct(String(props.product.id), payload);

    const updated: Product = {
      ...props.product,
      ...payload,
      category: props.product.category,
    };

    toast.success('Produk berhasil diperbarui.');
    emit('saved', updated);
    close();
  } catch (err: unknown) {
    const e = err as { response?: { status?: number; data?: { error?: { code?: string; message?: string } } } };
    const status = e?.response?.status;
    const code = e?.response?.data?.error?.code;
    const msg = e?.response?.data?.error?.message;
    if (status === 409 || code === 'CONFLICT') {
      fe.value.sku = 'Kode SKU sudah digunakan.';
    } else {
      serverError.value = msg ?? 'Gagal menyimpan produk.';
    }
  } finally {
    submitting.value = false;
  }
}

// ── Focus trap & keyboard navigation ──────────────────────────────────────────

function getFocusableElements(): HTMLElement[] {
  if (!dialogRef.value) return [];
  return Array.from(
    dialogRef.value.querySelectorAll<HTMLElement>(
      'a[href], button:not([disabled]), input:not([disabled]):not([tabindex="-1"]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
    )
  ).filter((el) => !el.hasAttribute('disabled') && el.offsetParent !== null);
}

function handleKeydown(event: KeyboardEvent) {
  if (event.key === 'Escape') {
    event.preventDefault();
    close();
    return;
  }
  if (event.key === 'Tab') {
    const focusable = getFocusableElements();
    if (focusable.length === 0) return;
    const first = focusable[0];
    const last = focusable[focusable.length - 1];
    if (event.shiftKey) {
      if (document.activeElement === first) { event.preventDefault(); last.focus(); }
    } else {
      if (document.activeElement === last) { event.preventDefault(); first.focus(); }
    }
  }
}
</script>

<style scoped>
@reference "../../css/app.css";

.field       { @apply space-y-1.5; }
.field-label { @apply block text-sm font-medium text-gray-700; }
.field-error { @apply text-xs text-red-600; }
.req         { @apply text-red-500; }

.field-input {
  @apply w-full h-10 rounded-md border border-gray-300 bg-white px-3 py-2
  text-sm placeholder:text-gray-400
  focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500
  disabled:opacity-50 disabled:cursor-not-allowed
  transition-colors duration-150;
}

/* Overlay fade */
.modal-fade-enter-active,
.modal-fade-leave-active { transition: opacity 0.15s ease; }
.modal-fade-enter-from,
.modal-fade-leave-to { opacity: 0; }
</style>
