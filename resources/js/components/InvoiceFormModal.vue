<template>
  <Teleport to="body">
    <Transition name="modal">
      <div
        v-if="modelValue"
        class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-slate-950/50 p-4 md:p-6"
        @click.self="close"
      >
        <div class="relative my-4 w-full max-w-5xl rounded-2xl bg-white shadow-2xl">
          <!-- Header -->
          <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5">
            <div>
              <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">Purchase Module</p>
              <h3 class="mt-1 text-xl font-semibold text-slate-900">Buat Invoice Pembelian</h3>
            </div>
            <button
              class="rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-600 transition hover:bg-slate-50"
              @click="close"
            >
              Tutup
            </button>
          </div>

          <div class="px-6 py-6 space-y-6">
            <!-- Header Fields -->
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
              <!-- Invoice Number -->
              <div class="field">
                <label class="field-label">Nomor Invoice <span class="req">*</span></label>
                <input
                  v-model="form.invoice_number"
                  type="text"
                  class="input-base"
                  :class="{ 'border-rose-400 focus:border-rose-500': errors.invoice_number }"
                  placeholder="INV-001"
                />
                <p v-if="errors.invoice_number" class="mt-1 text-xs text-rose-600">{{ errors.invoice_number }}</p>
              </div>

              <!-- Supplier Name -->
              <div class="field">
                <label class="field-label">Nama Supplier <span class="req">*</span></label>
                <input
                  v-model="form.supplier_name"
                  type="text"
                  class="input-base"
                  :class="{ 'border-rose-400 focus:border-rose-500': errors.supplier_name }"
                  placeholder="PT Supplier Indonesia"
                />
                <p v-if="errors.supplier_name" class="mt-1 text-xs text-rose-600">{{ errors.supplier_name }}</p>
              </div>

              <!-- Invoice Date -->
              <div class="field">
                <label class="field-label">Tanggal Invoice <span class="req">*</span></label>
                <input
                  v-model="form.invoice_date"
                  type="date"
                  class="input-base"
                  :class="{ 'border-rose-400 focus:border-rose-500': errors.invoice_date }"
                />
                <p v-if="errors.invoice_date" class="mt-1 text-xs text-rose-600">{{ errors.invoice_date }}</p>
              </div>

              <!-- Global Discount -->
              <div class="field">
                <label class="field-label">Diskon Global (opsional)</label>
                <div class="flex gap-2">
                  <select v-model="form.discount_global_type" class="input-base w-40 shrink-0">
                    <option value="">Tidak ada</option>
                    <option value="percent">Persen (%)</option>
                    <option value="nominal">Nominal (Rp)</option>
                  </select>
                  <input
                    v-if="form.discount_global_type"
                    v-model.number="form.discount_global_value"
                    type="number"
                    min="0"
                    :max="form.discount_global_type === 'percent' ? 100 : undefined"
                    class="input-base flex-1"
                    :class="{ 'border-rose-400': errors.discount_global_value }"
                    placeholder="0"
                  />
                </div>
                <p v-if="errors.discount_global_value" class="mt-1 text-xs text-rose-600">{{ errors.discount_global_value }}</p>
              </div>
            </div>

            <!-- Items Table -->
            <div>
              <div class="mb-3 flex items-center justify-between">
                <h4 class="text-sm font-semibold text-slate-700">Item Invoice <span class="req">*</span></h4>
                <button
                  type="button"
                  class="inline-flex items-center gap-1.5 rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-slate-700"
                  @click="addItem"
                >
                  <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                  </svg>
                  Tambah Item
                </button>
              </div>

              <p v-if="errors.items" class="mb-2 text-xs text-rose-600">{{ errors.items }}</p>

              <div class="overflow-x-auto rounded-xl border border-slate-200">
                <table class="w-full text-sm">
                  <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                      <th class="px-3 py-3 text-left w-48">Produk</th>
                      <th class="px-3 py-3 text-left w-20">Qty</th>
                      <th class="px-3 py-3 text-left w-32">Harga Input</th>
                      <th class="px-3 py-3 text-left w-36">Mode Harga</th>
                      <th class="px-3 py-3 text-left w-48">Diskon Item</th>
                      <th class="px-3 py-3 text-right w-28">Harga Final/Unit</th>
                      <th class="px-3 py-3 text-right w-28">COGS/Unit</th>
                      <th class="px-3 py-3 text-right w-28">Subtotal</th>
                      <th class="px-3 py-3 w-10"></th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-slate-100">
                    <tr v-if="form.items.length === 0">
                      <td colspan="9" class="px-4 py-8 text-center text-sm text-slate-400">
                        Belum ada item. Klik "Tambah Item" untuk menambahkan produk.
                      </td>
                    </tr>
                    <tr
                      v-for="(item, index) in form.items"
                      :key="item._key"
                      class="align-top"
                    >
                      <!-- Product Selector -->
                      <td class="px-3 py-2">
                        <div class="relative">
                          <input
                            v-model="item.productSearch"
                            type="text"
                            class="input-base text-xs"
                            :class="{ 'border-rose-400': itemErrors[index]?.product_id }"
                            placeholder="Cari produk..."
                            @input="onProductSearch(index)"
                            @focus="item.showDropdown = true"
                            @blur="onProductBlur(index)"
                          />
                          <!-- Dropdown -->
                          <div
                            v-if="item.showDropdown && item.searchResults.length > 0"
                            class="absolute left-0 top-full z-10 mt-1 w-64 rounded-xl border border-slate-200 bg-white shadow-lg"
                          >
                            <ul class="max-h-48 overflow-y-auto py-1">
                              <li
                                v-for="product in item.searchResults"
                                :key="product.id"
                                class="cursor-pointer px-3 py-2 text-xs hover:bg-slate-50"
                                @mousedown.prevent="selectProduct(index, product)"
                              >
                                <span class="font-medium text-slate-800">{{ product.name }}</span>
                                <span class="ml-1 text-slate-400">({{ product.sku }})</span>
                                <span class="ml-1 text-slate-400">— Stok: {{ product.current_stock }}</span>
                              </li>
                            </ul>
                          </div>
                          <div
                            v-if="item.showDropdown && item.searching"
                            class="absolute left-0 top-full z-10 mt-1 w-64 rounded-xl border border-slate-200 bg-white px-3 py-2 shadow-lg text-xs text-slate-400"
                          >
                            Mencari...
                          </div>
                        </div>
                        <p v-if="itemErrors[index]?.product_id" class="mt-1 text-xs text-rose-600">{{ itemErrors[index].product_id }}</p>
                      </td>

                      <!-- Qty -->
                      <td class="px-3 py-2">
                        <input
                          v-model.number="item.qty"
                          type="number"
                          min="1"
                          class="input-base text-xs"
                          :class="{ 'border-rose-400': itemErrors[index]?.qty }"
                          placeholder="1"
                        />
                        <p v-if="itemErrors[index]?.qty" class="mt-1 text-xs text-rose-600">{{ itemErrors[index].qty }}</p>
                      </td>

                      <!-- Price Input -->
                      <td class="px-3 py-2">
                        <input
                          v-model.number="item.price_input"
                          type="number"
                          min="0"
                          step="0.01"
                          class="input-base text-xs"
                          :class="{ 'border-rose-400': itemErrors[index]?.price_input }"
                          placeholder="0"
                        />
                        <p v-if="itemErrors[index]?.price_input" class="mt-1 text-xs text-rose-600">{{ itemErrors[index].price_input }}</p>
                      </td>

                      <!-- Price Mode -->
                      <td class="px-3 py-2">
                        <select v-model="item.price_mode" class="input-base text-xs">
                          <option value="final">Harga Final</option>
                          <option value="before_discount">Sebelum Diskon</option>
                        </select>
                      </td>

                      <!-- Discount Item -->
                      <td class="px-3 py-2">
                        <div class="space-y-1">
                          <select v-model="item.discount_item_type" class="input-base text-xs">
                            <option value="">Tidak ada</option>
                            <option value="percent">Persen (%)</option>
                            <option value="nominal">Nominal (Rp)</option>
                          </select>
                          <input
                            v-if="item.discount_item_type"
                            v-model.number="item.discount_item_value"
                            type="number"
                            min="0"
                            :max="item.discount_item_type === 'percent' ? 100 : undefined"
                            class="input-base text-xs"
                            :class="{ 'border-rose-400': itemErrors[index]?.discount_item_value }"
                            placeholder="0"
                          />
                          <p v-if="itemErrors[index]?.discount_item_value" class="mt-1 text-xs text-rose-600">{{ itemErrors[index].discount_item_value }}</p>
                        </div>
                      </td>

                      <!-- Price Per Unit Final (read-only) -->
                      <td class="px-3 py-2 text-right">
                        <span class="text-xs font-medium text-slate-700">
                          {{ formatCurrency(computedItems[index]?.price_per_unit_final ?? 0) }}
                        </span>
                      </td>

                      <!-- COGS Per Unit (read-only) -->
                      <td class="px-3 py-2 text-right">
                        <span class="text-xs font-medium text-emerald-700">
                          {{ formatCurrency(computedItems[index]?.cogs_per_unit ?? 0) }}
                        </span>
                      </td>

                      <!-- Subtotal (read-only) -->
                      <td class="px-3 py-2 text-right">
                        <span class="text-xs font-medium text-slate-700">
                          {{ formatCurrency(computedItems[index]?.subtotal_final ?? 0) }}
                        </span>
                      </td>

                      <!-- Delete -->
                      <td class="px-3 py-2 text-center">
                        <button
                          type="button"
                          class="rounded-lg p-1.5 text-slate-400 transition hover:bg-rose-50 hover:text-rose-600"
                          @click="removeItem(index)"
                        >
                          <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                          </svg>
                        </button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Real-time Preview Summary -->
            <div v-if="form.items.length > 0" class="rounded-xl border border-slate-200 bg-slate-50 p-4">
              <h4 class="mb-3 text-sm font-semibold text-slate-700">Ringkasan Invoice</h4>
              <div class="space-y-2 text-sm">
                <div class="flex justify-between text-slate-600">
                  <span>Total Sebelum Diskon Global</span>
                  <span class="font-medium">{{ formatCurrency(invoiceSummary.total_before_discount) }}</span>
                </div>
                <div v-if="form.discount_global_type" class="flex justify-between text-rose-600">
                  <span>
                    Diskon Global
                    <span v-if="form.discount_global_type === 'percent'">({{ form.discount_global_value }}%)</span>
                  </span>
                  <span class="font-medium">- {{ formatCurrency(invoiceSummary.total_discount) }}</span>
                </div>
                <div class="flex justify-between border-t border-slate-200 pt-2 text-base font-semibold text-slate-900">
                  <span>Total Akhir</span>
                  <span>{{ formatCurrency(invoiceSummary.total_final) }}</span>
                </div>
              </div>
            </div>

            <!-- Global Error -->
            <div v-if="submitError" class="rounded-xl bg-rose-50 border border-rose-200 px-4 py-3 text-sm text-rose-700">
              {{ submitError }}
            </div>
          </div>

          <!-- Footer -->
          <div class="flex gap-3 border-t border-slate-100 px-6 py-5">
            <button
              type="button"
              class="flex-1 rounded-2xl border border-slate-200 px-4 py-3 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
              @click="close"
            >
              Batal
            </button>
            <button
              type="button"
              class="flex-1 rounded-2xl bg-slate-900 px-4 py-3 text-sm font-medium text-white transition hover:bg-slate-700 disabled:opacity-50"
              :disabled="submitting"
              @click="submit"
            >
              <span v-if="submitting" class="inline-flex items-center justify-center gap-2">
                <span class="inline-block h-4 w-4 animate-spin rounded-full border-2 border-white/30 border-t-white"></span>
                Menyimpan...
              </span>
              <span v-else>Simpan Invoice</span>
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, reactive, computed, watch } from 'vue'
import { calculateInvoiceSummary } from '@/lib/cogsCalculation'
import type { DiscountType } from '@/lib/cogsCalculation'
import { usePurchaseStore } from '@/stores/purchaseStore'
import { getProducts } from '@/services/productService'
import type { Product } from '@/services/productService'
import { useToast } from '@/composables/useToast'

// ─── Props & Emits ────────────────────────────────────────────────────────────

const props = defineProps<{
  modelValue: boolean
}>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  'saved': []
}>()

// ─── Store & Toast ────────────────────────────────────────────────────────────

const purchaseStore = usePurchaseStore()
const toast = useToast()

// ─── Types ────────────────────────────────────────────────────────────────────

interface FormItem {
  _key: number
  product_id: string
  productSearch: string
  searchResults: Product[]
  searching: boolean
  showDropdown: boolean
  searchTimer: ReturnType<typeof setTimeout> | null
  qty: number
  price_input: number
  price_mode: 'final' | 'before_discount'
  discount_item_type: '' | 'percent' | 'nominal'
  discount_item_value: number
}

interface ItemErrors {
  product_id?: string
  qty?: string
  price_input?: string
  discount_item_value?: string
}

interface FormErrors {
  invoice_number?: string
  supplier_name?: string
  invoice_date?: string
  discount_global_value?: string
  items?: string
}

// ─── State ────────────────────────────────────────────────────────────────────

let keyCounter = 0

function today(): string {
  return new Date().toISOString().split('T')[0]
}

function makeItem(): FormItem {
  return {
    _key: ++keyCounter,
    product_id: '',
    productSearch: '',
    searchResults: [],
    searching: false,
    showDropdown: false,
    searchTimer: null,
    qty: 1,
    price_input: 0,
    price_mode: 'final',
    discount_item_type: '',
    discount_item_value: 0,
  }
}

const form = reactive({
  invoice_number: '',
  supplier_name: '',
  invoice_date: today(),
  discount_global_type: '' as '' | 'percent' | 'nominal',
  discount_global_value: 0,
  items: [] as FormItem[],
})

const errors = reactive<FormErrors>({})
const itemErrors = ref<ItemErrors[]>([])
const submitting = ref(false)
const submitError = ref('')

// ─── Reset on open ────────────────────────────────────────────────────────────

watch(
  () => props.modelValue,
  (open) => {
    if (!open) return
    form.invoice_number = ''
    form.supplier_name = ''
    form.invoice_date = today()
    form.discount_global_type = ''
    form.discount_global_value = 0
    form.items = []
    Object.keys(errors).forEach((k) => delete (errors as Record<string, unknown>)[k])
    itemErrors.value = []
    submitError.value = ''
  },
)

// ─── Computed: invoice summary (real-time, < 100ms, no server request) ────────

const invoiceSummary = computed(() => {
  const items = form.items.map((item) => ({
    product_id: item.product_id,
    qty: item.qty || 0,
    price_input: item.price_input || 0,
    price_mode: item.price_mode,
    discount_item_type: (item.discount_item_type || null) as DiscountType,
    discount_item_value: item.discount_item_value || 0,
  }))

  const globalDiscountType = (form.discount_global_type || null) as DiscountType
  const globalDiscountValue = form.discount_global_value || 0

  return calculateInvoiceSummary(items, globalDiscountType, globalDiscountValue)
})

const computedItems = computed(() => invoiceSummary.value.items_with_cogs)

// ─── Item management ──────────────────────────────────────────────────────────

function addItem() {
  form.items.push(makeItem())
  itemErrors.value.push({})
}

function removeItem(index: number) {
  const item = form.items[index]
  if (item.searchTimer) clearTimeout(item.searchTimer)
  form.items.splice(index, 1)
  itemErrors.value.splice(index, 1)
}

// ─── Product search ───────────────────────────────────────────────────────────

function onProductSearch(index: number) {
  const item = form.items[index]
  item.product_id = ''
  item.showDropdown = true

  if (item.searchTimer) clearTimeout(item.searchTimer)

  const query = item.productSearch.trim()
  if (!query) {
    item.searchResults = []
    item.searching = false
    return
  }

  item.searching = true
  item.searchTimer = setTimeout(async () => {
    try {
      const result = await getProducts({ search: query })
      item.searchResults = result.data
    } catch {
      item.searchResults = []
    } finally {
      item.searching = false
    }
  }, 300)
}

function selectProduct(index: number, product: Product) {
  const item = form.items[index]
  item.product_id = product.id
  item.productSearch = product.name
  item.showDropdown = false
  item.searchResults = []
  // Clear product error
  if (itemErrors.value[index]) {
    itemErrors.value[index].product_id = undefined
  }
}

function onProductBlur(index: number) {
  // Delay to allow mousedown on dropdown items to fire first
  setTimeout(() => {
    const item = form.items[index]
    if (item) item.showDropdown = false
  }, 150)
}

// ─── Validation ───────────────────────────────────────────────────────────────

function validate(): boolean {
  // Clear previous errors
  Object.keys(errors).forEach((k) => delete (errors as Record<string, unknown>)[k])
  itemErrors.value = form.items.map(() => ({}))

  let valid = true

  if (!form.invoice_number.trim()) {
    errors.invoice_number = 'Nomor invoice wajib diisi.'
    valid = false
  }

  if (!form.supplier_name.trim()) {
    errors.supplier_name = 'Nama supplier wajib diisi.'
    valid = false
  }

  if (!form.invoice_date) {
    errors.invoice_date = 'Tanggal invoice wajib diisi.'
    valid = false
  }

  if (form.discount_global_type === 'percent') {
    const v = form.discount_global_value
    if (v < 0 || v > 100) {
      errors.discount_global_value = 'Diskon persen harus antara 0 dan 100.'
      valid = false
    }
  } else if (form.discount_global_type === 'nominal') {
    if (form.discount_global_value < 0) {
      errors.discount_global_value = 'Diskon nominal tidak boleh negatif.'
      valid = false
    }
  }

  if (form.items.length === 0) {
    errors.items = 'Minimal 1 item harus ditambahkan.'
    valid = false
  }

  form.items.forEach((item, i) => {
    const errs: ItemErrors = {}

    if (!item.product_id) {
      errs.product_id = 'Pilih produk.'
      valid = false
    }

    if (!item.qty || item.qty <= 0) {
      errs.qty = 'Qty harus lebih dari 0.'
      valid = false
    }

    if (!item.price_input || item.price_input <= 0) {
      errs.price_input = 'Harga harus lebih dari 0.'
      valid = false
    }

    if (item.discount_item_type === 'percent') {
      const v = item.discount_item_value
      if (v < 0 || v > 100) {
        errs.discount_item_value = 'Diskon persen harus antara 0 dan 100.'
        valid = false
      }
    } else if (item.discount_item_type === 'nominal') {
      if (item.discount_item_value < 0) {
        errs.discount_item_value = 'Diskon nominal tidak boleh negatif.'
        valid = false
      }
    }

    itemErrors.value[i] = errs
  })

  return valid
}

// ─── Submit ───────────────────────────────────────────────────────────────────

async function submit() {
  submitError.value = ''

  if (!validate()) return

  submitting.value = true
  try {
    await purchaseStore.createInvoice({
      invoice_number: form.invoice_number.trim(),
      supplier_name: form.supplier_name.trim(),
      invoice_date: form.invoice_date,
      discount_global_type: form.discount_global_type || undefined,
      discount_global_value: form.discount_global_type ? form.discount_global_value : undefined,
      items: form.items.map((item) => ({
        product_id: item.product_id,
        qty: item.qty,
        price_input: item.price_input,
        price_mode: item.price_mode,
        discount_item_type: item.discount_item_type || undefined,
        discount_item_value: item.discount_item_type ? item.discount_item_value : undefined,
      })),
    })

    toast.success('Invoice berhasil disimpan.')
    emit('saved')
    close()
  } catch (err: unknown) {
    const e = err as { response?: { status?: number; data?: { error?: { message?: string } } } }
    const status = e?.response?.status
    const msg = e?.response?.data?.error?.message

    if (status === 409) {
      errors.invoice_number = 'Nomor invoice sudah digunakan.'
      submitError.value = 'Nomor invoice sudah digunakan.'
    } else {
      submitError.value = msg ?? 'Gagal menyimpan invoice. Silakan coba lagi.'
    }

    toast.error(submitError.value)
  } finally {
    submitting.value = false
  }
}

// ─── Close ────────────────────────────────────────────────────────────────────

function close() {
  if (submitting.value) return
  emit('update:modelValue', false)
}

// ─── Helpers ──────────────────────────────────────────────────────────────────

function formatCurrency(value: number): string {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
  }).format(value)
}
</script>

<style scoped>
@reference "../../css/app.css";

.field {
  @apply space-y-1.5;
}

.field-label {
  @apply block text-sm font-medium text-slate-700;
}

.req {
  @apply text-rose-500;
}

.input-base {
  @apply flex h-9 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-200 disabled:opacity-50;
}

.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
  transform: scale(0.98);
}
</style>
