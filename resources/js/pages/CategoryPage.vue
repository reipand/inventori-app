<template>
  <div class="space-y-5">
    <div class="flex items-start justify-between gap-3">
      <div>
        <h2 class="text-lg font-semibold">Kategori</h2>
        <p class="text-sm text-muted-foreground">Kelola kategori produk</p>
      </div>
      <button class="btn-primary shrink-0" @click="openAdd">
        <span class="sm:hidden">+ Tambah</span>
        <span class="hidden sm:inline">+ Tambah Kategori</span>
      </button>
    </div>

    <div v-if="pageError" class="alert-error">{{ pageError }}</div>

    <!-- Table -->
    <div class="rounded-xl border bg-card overflow-hidden">
      <div class="overflow-x-auto">
      <table class="w-full text-sm min-w-[400px]">
        <thead class="border-b bg-muted/40">
          <tr>
            <th class="th">Nama</th>
            <th class="th">Deskripsi</th>
            <th class="th text-right">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading"><td colspan="3" class="td text-center text-muted-foreground py-10"><span class="spinner-dark" /></td></tr>
          <tr v-else-if="categories.length === 0"><td colspan="3" class="td text-center text-muted-foreground py-10">Belum ada kategori.</td></tr>
          <tr v-for="cat in categories" :key="cat.id" class="border-b last:border-0 hover:bg-muted/30 transition-colors">
            <td class="td font-medium">{{ cat.name }}</td>
            <td class="td text-muted-foreground">{{ cat.description ?? '—' }}</td>
            <td class="td text-right space-x-2">
              <button class="btn-outline-sm" @click="openEdit(cat)">Edit</button>
              <button class="btn-danger-sm" @click="openDelete(cat)">Hapus</button>
            </td>
          </tr>
        </tbody>
      </table>
      </div>
    </div>

    <!-- Form Dialog -->
    <Teleport to="body">
      <div v-if="showForm" class="dialog-overlay" @click.self="closeForm">
        <div class="dialog-box">
          <h3 class="text-base font-semibold mb-4">{{ editTarget ? 'Edit Kategori' : 'Tambah Kategori' }}</h3>
          <div v-if="formError" class="alert-error mb-3">{{ formError }}</div>
          <div class="space-y-3">
            <div class="field">
              <label class="field-label">Nama <span class="text-destructive">*</span></label>
              <input v-model="form.name" class="input-base" placeholder="Nama kategori" :disabled="submitting" />
            </div>
            <div class="field">
              <label class="field-label">Deskripsi</label>
              <input v-model="form.description" class="input-base" placeholder="Opsional" :disabled="submitting" />
            </div>
          </div>
          <div class="flex justify-end gap-2 mt-5">
            <button class="btn-outline" @click="closeForm" :disabled="submitting">Batal</button>
            <button class="btn-primary" @click="submitForm" :disabled="submitting">
              {{ submitting ? 'Menyimpan...' : 'Simpan' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Delete Dialog -->
    <Teleport to="body">
      <div v-if="showDelete" class="dialog-overlay" @click.self="showDelete = false">
        <div class="dialog-box max-w-sm">
          <h3 class="text-base font-semibold mb-2">Hapus Kategori</h3>
          <p class="text-sm text-muted-foreground mb-4">
            Yakin hapus <span class="font-medium text-foreground">{{ deleteTarget?.name }}</span>?
          </p>
          <div v-if="deleteError" class="alert-error mb-3">{{ deleteError }}</div>
          <div class="flex justify-end gap-2">
            <button class="btn-outline" @click="showDelete = false" :disabled="submitting">Batal</button>
            <button class="btn-danger" @click="confirmDelete" :disabled="submitting">
              {{ submitting ? 'Menghapus...' : 'Hapus' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { getCategories, createCategory, updateCategory, deleteCategory, type Category } from '@/services/categoryService';
import { useToast } from '@/composables/useToast';

const toast = useToast();
const categories = ref<Category[]>([]);
const loading = ref(false);
const pageError = ref('');

const showForm = ref(false);
const editTarget = ref<Category | null>(null);
const form = ref({ name: '', description: '' });
const formError = ref('');
const submitting = ref(false);

const showDelete = ref(false);
const deleteTarget = ref<Category | null>(null);
const deleteError = ref('');

async function load() {
  loading.value = true;
  pageError.value = '';
  try { categories.value = await getCategories(); }
  catch { pageError.value = 'Gagal memuat kategori.'; }
  finally { loading.value = false; }
}
onMounted(load);

function openAdd() { editTarget.value = null; form.value = { name: '', description: '' }; formError.value = ''; showForm.value = true; }
function openEdit(cat: Category) { editTarget.value = cat; form.value = { name: cat.name, description: cat.description ?? '' }; formError.value = ''; showForm.value = true; }
function closeForm() { if (!submitting.value) showForm.value = false; }
function openDelete(cat: Category) { deleteTarget.value = cat; deleteError.value = ''; showDelete.value = true; }

async function submitForm() {
  if (!form.value.name.trim()) { formError.value = 'Nama tidak boleh kosong.'; return; }
  submitting.value = true; formError.value = '';
  try {
    const payload = { name: form.value.name.trim(), description: form.value.description.trim() || undefined };
    if (editTarget.value) {
      const updated = await updateCategory(editTarget.value.id, payload);
      const idx = categories.value.findIndex((c) => c.id === updated.id);
      if (idx !== -1) categories.value[idx] = updated;
      toast.success('Kategori berhasil diperbarui.');
    } else {
      categories.value.push(await createCategory(payload));
      toast.success('Kategori berhasil ditambahkan.');
    }
    showForm.value = false;
  } catch (err: unknown) { formError.value = extractMsg(err, 'Gagal menyimpan.'); }
  finally { submitting.value = false; }
}

async function confirmDelete() {
  if (!deleteTarget.value) return;
  submitting.value = true; deleteError.value = '';
  try {
    await deleteCategory(deleteTarget.value.id);
    categories.value = categories.value.filter((c) => c.id !== deleteTarget.value!.id);
    showDelete.value = false;
    toast.success('Kategori berhasil dihapus.');
  } catch (err: unknown) { deleteError.value = extractMsg(err, 'Gagal menghapus.'); }
  finally { submitting.value = false; }
}

function extractMsg(err: unknown, fallback: string) {
  const e = err as { response?: { status?: number; data?: { error?: { code?: string; message?: string } } } };
  const status = e?.response?.status;
  const code = e?.response?.data?.error?.code;
  const msg = e?.response?.data?.error?.message;
  if (status === 409 || code === 'CONFLICT') return 'Nama kategori sudah digunakan.';
  if (status === 422 || code === 'BUSINESS_RULE_VIOLATION') return 'Kategori tidak dapat dihapus karena masih memiliki produk.';
  return msg ?? fallback;
}
</script>

<style scoped>
@reference "../../css/app.css";
.th { @apply px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wide; }
.td { @apply px-4 py-3; }
.field { @apply space-y-1.5; }
.field-label { @apply text-sm font-medium; }
.input-base { @apply flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:opacity-50 transition-colors; }
.btn-primary { @apply inline-flex items-center justify-center rounded-md bg-primary text-primary-foreground text-sm font-medium h-9 px-4 hover:bg-primary/90 disabled:opacity-50 disabled:pointer-events-none transition-colors; }
.btn-outline { @apply inline-flex items-center justify-center rounded-md border text-sm font-medium h-9 px-4 hover:bg-accent disabled:opacity-50 transition-colors; }
.btn-danger { @apply inline-flex items-center justify-center rounded-md bg-destructive text-destructive-foreground text-sm font-medium h-9 px-4 hover:bg-destructive/90 disabled:opacity-50 transition-colors; }
.btn-outline-sm { @apply inline-flex items-center justify-center rounded-md border text-xs font-medium h-7 px-2.5 hover:bg-accent transition-colors; }
.btn-danger-sm { @apply inline-flex items-center justify-center rounded-md bg-destructive/10 text-destructive text-xs font-medium h-7 px-2.5 hover:bg-destructive/20 transition-colors; }
.alert-error { @apply rounded-lg bg-destructive/10 border border-destructive/20 px-3 py-2.5 text-sm text-destructive; }
.dialog-overlay { @apply fixed inset-0 z-50 flex items-end sm:items-center sm:p-4 bg-black/50 backdrop-blur-sm; }
.dialog-box { @apply w-full sm:max-w-md rounded-t-2xl sm:rounded-2xl bg-card border shadow-xl p-5 sm:p-6; }
.spinner-dark { @apply inline-block w-5 h-5 border-2 border-muted border-t-foreground rounded-full animate-spin; }
</style>
