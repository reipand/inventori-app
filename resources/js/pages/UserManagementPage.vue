<template>
  <div class="space-y-5">
    <div class="flex items-start justify-between gap-3">
      <div>
        <h2 class="text-lg font-semibold">Manajemen Pengguna</h2>
        <p class="text-sm text-muted-foreground">Kelola akun pengguna sistem</p>
      </div>
      <button class="btn-primary shrink-0" @click="openAdd">+ Buat Akun</button>
    </div>

    <div v-if="pageError" class="alert-error">{{ pageError }}</div>

    <div v-if="loading" class="space-y-2">
      <div v-for="i in 5" :key="i" class="skeleton h-14 rounded-lg" />
    </div>
    <div v-else class="rounded-xl border bg-card overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm min-w-[560px]">
          <thead class="border-b bg-muted/40">
            <tr>
              <th class="th">Email</th>
              <th class="th text-center">Peran</th>
              <th class="th text-center">Status</th>
              <th class="th text-right">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="users.length === 0">
              <td colspan="4" class="td text-center py-10 text-muted-foreground">Belum ada pengguna.</td>
            </tr>
            <tr v-for="u in users.filter(Boolean)" :key="u.id" class="border-b last:border-0 hover:bg-muted/30 transition-colors">
              <td class="td">{{ u.email }}</td>
              <td class="td text-center">
                <span v-if="u.role === 'pengelola'" class="badge badge-blue">Pengelola</span>
                <span v-else class="badge badge-gray">Kasir</span>
              </td>
              <td class="td text-center">
                <span v-if="u.is_active" class="badge badge-green">Aktif</span>
                <span v-else class="badge badge-red">Nonaktif</span>
              </td>
              <td class="td text-right">
                <div class="flex items-center justify-end gap-1.5">
                  <button class="btn-action" @click="openEdit(u)">Edit</button>
                  <button v-if="u.is_active" class="btn-action-warn" @click="openDeactivate(u)">Nonaktifkan</button>
                  <button v-else class="btn-action-success" @click="activateUser(u)">Aktifkan</button>
                  <button class="btn-action-danger" @click="openDelete(u)">Hapus</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Add Dialog -->
    <Teleport to="body">
      <div v-if="showAdd" class="dialog-overlay" @click.self="!submitting && (showAdd = false)">
        <div class="dialog-box">
          <template v-if="createdPassword">
            <h3 class="text-base font-semibold mb-1">Akun Berhasil Dibuat</h3>
            <p class="text-sm text-muted-foreground mb-4">Salin password sementara di bawah ini dan berikan kepada pengguna.</p>
            <div class="field mb-2">
              <label class="field-label">Email</label>
              <p class="text-sm font-medium">{{ form.email }}</p>
            </div>
            <div class="field mb-4">
              <label class="field-label">Password Sementara</label>
              <div class="flex gap-2 items-center">
                <code class="flex-1 rounded-md border border-input bg-muted px-3 py-2 text-sm font-mono select-all">{{ createdPassword }}</code>
                <button class="btn-outline h-9 px-3 shrink-0" @click="copyPassword">{{ copied ? '✓ Disalin' : 'Salin' }}</button>
              </div>
            </div>
            <p class="text-xs text-muted-foreground mb-4">Pengguna wajib mengganti password setelah login pertama kali.</p>
            <div class="flex justify-end">
              <button class="btn-primary" @click="showAdd = false">Selesai</button>
            </div>
          </template>

          <template v-else>
            <h3 class="text-base font-semibold mb-4">Buat Akun Baru</h3>
            <div v-if="formError" class="alert-error mb-3">{{ formError }}</div>
            <div class="space-y-3">
              <div class="field">
                <label class="field-label">Email <span class="req">*</span></label>
                <input v-model="form.email" type="email" class="input-base" placeholder="email@contoh.com" :disabled="submitting" />
              </div>
              <div class="field">
                <label class="field-label">Peran <span class="req">*</span></label>
                <select v-model="form.role" class="input-base" :disabled="submitting">
                  <option value="" disabled>Pilih peran</option>
                  <option value="pengelola">Pengelola</option>
                  <option value="kasir">Kasir</option>
                </select>
              </div>
              <div class="field">
                <label class="field-label">Password</label>
                <input v-model="form.password" type="password" class="input-base" placeholder="Kosongkan untuk generate otomatis" :disabled="submitting" autocomplete="new-password" />
                <p class="text-xs text-muted-foreground">Jika dikosongkan, password acak akan dikirim ke email.</p>
              </div>
            </div>
            <div class="flex justify-end gap-2 mt-5">
              <button class="btn-outline" @click="showAdd = false" :disabled="submitting">Batal</button>
              <button class="btn-primary" @click="submitAdd" :disabled="submitting">
                {{ submitting ? 'Menyimpan...' : 'Buat Akun' }}
              </button>
            </div>
          </template>
        </div>
      </div>
    </Teleport>

    <!-- Edit Dialog -->
    <Teleport to="body">
      <div v-if="showEdit" class="dialog-overlay" @click.self="!submitting && (showEdit = false)">
        <div class="dialog-box">
          <h3 class="text-base font-semibold mb-4">Edit Akun</h3>
          <div v-if="editError" class="alert-error mb-3">{{ editError }}</div>
          <div class="space-y-3">
            <div class="field">
              <label class="field-label">Email <span class="req">*</span></label>
              <input v-model="editForm.email" type="email" class="input-base" :disabled="submitting" />
            </div>
            <div class="field">
              <label class="field-label">Peran <span class="req">*</span></label>
              <select v-model="editForm.role" class="input-base" :disabled="submitting">
                <option value="pengelola">Pengelola</option>
                <option value="kasir">Kasir</option>
              </select>
            </div>
            <div class="field">
              <label class="field-label">Password Baru</label>
              <input v-model="editForm.password" type="password" class="input-base" placeholder="Kosongkan jika tidak ingin mengubah" :disabled="submitting" autocomplete="new-password" />
            </div>
          </div>
          <div class="flex justify-end gap-2 mt-5">
            <button class="btn-outline" @click="showEdit = false" :disabled="submitting">Batal</button>
            <button class="btn-primary" @click="submitEdit" :disabled="submitting">
              {{ submitting ? 'Menyimpan...' : 'Simpan' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Deactivate Dialog -->
    <Teleport to="body">
      <div v-if="showDeactivate" class="dialog-overlay" @click.self="!submitting && (showDeactivate = false)">
        <div class="dialog-box max-w-sm">
          <h3 class="text-base font-semibold mb-2">Nonaktifkan Akun</h3>
          <p class="text-sm text-muted-foreground mb-4">
            Yakin nonaktifkan akun <span class="font-medium text-foreground">{{ deactivateTarget?.email }}</span>?
            Pengguna tidak akan bisa login setelah dinonaktifkan.
          </p>
          <div v-if="deactivateError" class="alert-error mb-3">{{ deactivateError }}</div>
          <div class="flex justify-end gap-2">
            <button class="btn-outline" @click="showDeactivate = false" :disabled="submitting">Batal</button>
            <button class="btn-danger" @click="confirmDeactivate" :disabled="submitting">
              {{ submitting ? 'Memproses...' : 'Nonaktifkan' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Delete Dialog -->
    <Teleport to="body">
      <div v-if="showDelete" class="dialog-overlay" @click.self="!submitting && (showDelete = false)">
        <div class="dialog-box max-w-sm">
          <h3 class="text-base font-semibold mb-2">Hapus Akun</h3>
          <p class="text-sm text-muted-foreground mb-4">
            Yakin hapus akun <span class="font-medium text-foreground">{{ deleteTarget?.email }}</span>?
            Tindakan ini tidak dapat dibatalkan.
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
import axios from 'axios';
import { useToast } from '@/composables/useToast';

interface User { id: string; email: string; role: 'pengelola' | 'kasir'; is_active: boolean; must_change_password: boolean; }

const toast = useToast();
const users = ref<User[]>([]);
const loading = ref(false);
const pageError = ref('');

// Add
const showAdd = ref(false);
const form = ref({ email: '', role: '' as '' | 'pengelola' | 'kasir', password: '' });
const formError = ref('');
const submitting = ref(false);
const createdPassword = ref('');
const copied = ref(false);

// Edit
const showEdit = ref(false);
const editTarget = ref<User | null>(null);
const editForm = ref({ email: '', role: '' as 'pengelola' | 'kasir', password: '' });
const editError = ref('');

// Deactivate
const showDeactivate = ref(false);
const deactivateTarget = ref<User | null>(null);
const deactivateError = ref('');

// Delete
const showDelete = ref(false);
const deleteTarget = ref<User | null>(null);
const deleteError = ref('');

async function loadUsers() {
  loading.value = true; pageError.value = '';
  try {
    const r = await axios.get<{ data: { data: User[] } }>('/api/users');
    users.value = r.data.data.data;
  } catch { pageError.value = 'Gagal memuat data pengguna.'; }
  finally { loading.value = false; }
}
onMounted(loadUsers);

function openAdd() {
  form.value = { email: '', role: '', password: '' };
  formError.value = ''; createdPassword.value = ''; copied.value = false;
  showAdd.value = true;
}

function openEdit(u: User) {
  editTarget.value = u;
  editForm.value = { email: u.email, role: u.role, password: '' };
  editError.value = '';
  showEdit.value = true;
}

function openDeactivate(u: User) { deactivateTarget.value = u; deactivateError.value = ''; showDeactivate.value = true; }
function openDelete(u: User) { deleteTarget.value = u; deleteError.value = ''; showDelete.value = true; }

async function submitAdd() {
  if (!form.value.email.trim()) { formError.value = 'Email tidak boleh kosong.'; return; }
  if (!form.value.role) { formError.value = 'Peran harus dipilih.'; return; }
  submitting.value = true; formError.value = '';
  try {
    const payload: Record<string, string> = { email: form.value.email.trim(), role: form.value.role };
    if (form.value.password.trim()) payload.password = form.value.password.trim();
    const r = await axios.post<{ data: User & { temp_password?: string } }>('/api/users', payload);
    users.value.unshift(r.data.data);
    createdPassword.value = r.data.data.temp_password ?? '';
    if (!createdPassword.value) {
      showAdd.value = false;
      toast.success('Akun berhasil dibuat.');
    }
  } catch (err: unknown) {
    const e = err as { response?: { status?: number; data?: { error?: { code?: string; message?: string } } } };
    if (e?.response?.status === 409 || e?.response?.data?.error?.code === 'CONFLICT') formError.value = 'Email sudah terdaftar.';
    else formError.value = e?.response?.data?.error?.message ?? 'Gagal membuat akun.';
  } finally { submitting.value = false; }
}

async function copyPassword() {
  await navigator.clipboard.writeText(createdPassword.value);
  copied.value = true;
  setTimeout(() => { copied.value = false; }, 2000);
}

async function submitEdit() {
  if (!editTarget.value) return;
  if (!editForm.value.email.trim()) { editError.value = 'Email tidak boleh kosong.'; return; }
  submitting.value = true; editError.value = '';
  try {
    const payload: Record<string, string> = { email: editForm.value.email.trim(), role: editForm.value.role };
    if (editForm.value.password.trim()) payload.password = editForm.value.password.trim();
    const r = await axios.put<{ data: User }>(`/api/users/${editTarget.value.id}`, payload);
    const idx = users.value.findIndex((u) => u.id === editTarget.value!.id);
    if (idx !== -1) users.value[idx] = { ...users.value[idx], ...r.data.data };
    showEdit.value = false;
    toast.success('Akun berhasil diperbarui.');
  } catch (err: unknown) {
    const e = err as { response?: { status?: number; data?: { error?: { code?: string; message?: string } } } };
    if (e?.response?.status === 409 || e?.response?.data?.error?.code === 'CONFLICT') editError.value = 'Email sudah digunakan akun lain.';
    else editError.value = e?.response?.data?.error?.message ?? 'Gagal memperbarui akun.';
  } finally { submitting.value = false; }
}

async function activateUser(u: User) {
  try {
    await axios.put(`/api/users/${u.id}`, { is_active: true });
    const idx = users.value.findIndex((x) => x.id === u.id);
    if (idx !== -1) users.value[idx] = { ...users.value[idx], is_active: true };
    toast.success('Akun berhasil diaktifkan.');
  } catch (err: unknown) {
    const e = err as { response?: { data?: { error?: { message?: string } } } };
    toast.error(e?.response?.data?.error?.message ?? 'Gagal mengaktifkan akun.');
  }
}

async function confirmDeactivate() {
  if (!deactivateTarget.value) return;
  submitting.value = true; deactivateError.value = '';
  try {
    await axios.put(`/api/users/${deactivateTarget.value.id}/deactivate`);
    const idx = users.value.findIndex((u) => u.id === deactivateTarget.value!.id);
    if (idx !== -1) users.value[idx] = { ...users.value[idx], is_active: false };
    showDeactivate.value = false;
    toast.success('Akun berhasil dinonaktifkan.');
  } catch (err: unknown) {
    const e = err as { response?: { data?: { error?: { message?: string } } } };
    deactivateError.value = e?.response?.data?.error?.message ?? 'Gagal menonaktifkan akun.';
  } finally { submitting.value = false; }
}

async function confirmDelete() {
  if (!deleteTarget.value) return;
  submitting.value = true; deleteError.value = '';
  try {
    await axios.delete(`/api/users/${deleteTarget.value.id}`);
    users.value = users.value.filter((u) => u.id !== deleteTarget.value!.id);
    showDelete.value = false;
    toast.success('Akun berhasil dihapus.');
  } catch (err: unknown) {
    const e = err as { response?: { data?: { error?: { message?: string } } } };
    deleteError.value = e?.response?.data?.error?.message ?? 'Gagal menghapus akun.';
  } finally { submitting.value = false; }
}
</script>

<style scoped>
@reference "../../css/app.css";
.th { @apply px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wide; }
.td { @apply px-4 py-3; }
.field { @apply space-y-1.5; }
.field-label { @apply text-sm font-medium; }
.req { @apply text-destructive; }
.input-base { @apply flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:opacity-50 transition-colors; }
.btn-primary { @apply inline-flex items-center justify-center rounded-md bg-primary text-primary-foreground text-sm font-medium h-9 px-4 hover:bg-primary/90 disabled:opacity-50 transition-colors; }
.btn-outline { @apply inline-flex items-center justify-center rounded-md border text-sm font-medium h-9 px-4 hover:bg-accent disabled:opacity-50 transition-colors; }
.btn-danger { @apply inline-flex items-center justify-center rounded-md bg-destructive text-destructive-foreground text-sm font-medium h-9 px-4 hover:bg-destructive/90 disabled:opacity-50 transition-colors; }
.btn-action { @apply inline-flex items-center justify-center rounded-md border text-xs font-medium h-7 px-2.5 hover:bg-accent transition-colors; }
.btn-action-warn { @apply inline-flex items-center justify-center rounded-md bg-amber-50 text-amber-700 border border-amber-200 text-xs font-medium h-7 px-2.5 hover:bg-amber-100 transition-colors; }
.btn-action-success { @apply inline-flex items-center justify-center rounded-md bg-green-50 text-green-700 border border-green-200 text-xs font-medium h-7 px-2.5 hover:bg-green-100 transition-colors; }
.btn-action-danger { @apply inline-flex items-center justify-center rounded-md bg-destructive/10 text-destructive text-xs font-medium h-7 px-2.5 hover:bg-destructive/20 transition-colors; }
.badge { @apply inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium; }
.badge-green { @apply bg-green-100 text-green-800; }
.badge-blue { @apply bg-blue-100 text-blue-800; }
.badge-gray { @apply bg-muted text-muted-foreground; }
.badge-red { @apply bg-red-100 text-red-700; }
.alert-error { @apply rounded-lg bg-destructive/10 border border-destructive/20 px-3 py-2.5 text-sm text-destructive; }
.dialog-overlay { @apply fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm; }
.dialog-box { @apply w-full max-w-md rounded-2xl bg-card border shadow-xl p-5 sm:p-6; }
.skeleton { @apply bg-muted animate-pulse; }
</style>
