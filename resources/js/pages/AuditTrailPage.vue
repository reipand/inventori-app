<template>
  <div class="space-y-5">
    <div>
      <h2 class="text-lg font-semibold">Audit Trail</h2>
      <p class="text-sm text-muted-foreground">Riwayat semua perubahan data di sistem</p>
    </div>

    <!-- Filters -->
    <div class="rounded-xl border bg-card p-4 space-y-4">
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="field">
          <label class="field-label">Jenis Aktivitas</label>
          <select v-model="filters.action" class="input-base">
            <option value="">Semua Aktivitas</option>
            <option value="create">Penambahan Data</option>
            <option value="update">Perubahan Data</option>
            <option value="delete">Penghapusan Data</option>
          </select>
        </div>
        <div class="field">
          <label class="field-label">Nama Pengguna</label>
          <input v-model="filters.user_name" type="text" class="input-base" placeholder="Cari nama..." />
        </div>
        <div class="field">
          <label class="field-label">Tanggal Awal</label>
          <input v-model="filters.start_date" type="date" class="input-base" @change="onDateChange" />
        </div>
        <div class="field">
          <label class="field-label">Tanggal Akhir</label>
          <input v-model="filters.end_date" type="date" class="input-base" @change="onDateChange" />
        </div>
      </div>
      <div v-if="dateError" class="text-sm text-destructive">{{ dateError }}</div>
      <div class="flex items-center gap-2 justify-end">
        <button class="btn-outline-sm" @click="resetFilters">Reset</button>
        <button class="btn-primary-sm" @click="applyFilters" :disabled="!!dateError">Terapkan</button>
      </div>
    </div>

    <div v-if="loading" class="space-y-2">
      <div v-for="i in 8" :key="i" class="skeleton h-12 rounded-lg" />
    </div>
    <div v-else-if="error" class="alert-error">{{ error }}</div>
    <div v-else class="rounded-xl border bg-card overflow-hidden">
      <div class="overflow-x-auto">
      <table class="w-full text-sm min-w-[540px]">
        <thead class="border-b bg-muted/40">
          <tr>
            <th class="th">Waktu</th>
            <th class="th">Data yang Diubah</th>
            <th class="th text-center">Aktivitas</th>
            <th class="th hidden sm:table-cell">Dilakukan Oleh</th>
            <th class="th text-center">Detail</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="!logs?.length">
            <td colspan="5" class="td text-center py-10 text-muted-foreground">Tidak ada data audit trail.</td>
          </tr>
          <template v-for="log in (logs ?? [])" :key="log.id">
            <tr class="border-b hover:bg-muted/30 transition-colors">
              <td class="td text-muted-foreground whitespace-nowrap text-xs">{{ fmtDateTime(log.created_at) }}</td>
              <td class="td">
                <span class="font-medium">{{ log.entity_label }}</span>
                <span class="ml-1 text-xs text-muted-foreground font-mono">#{{ log.entity_id.slice(0, 8) }}</span>
                <p class="text-xs text-muted-foreground sm:hidden mt-0.5">{{ log.user?.name ?? '—' }}</p>
              </td>
              <td class="td text-center">
                <span v-if="log.action === 'create'" class="badge badge-green">Ditambahkan</span>
                <span v-else-if="log.action === 'update'" class="badge badge-blue">Diubah</span>
                <span v-else class="badge badge-red">Dihapus</span>
              </td>
              <td class="td hidden sm:table-cell">
                <p class="font-medium">{{ log.user?.name ?? '—' }}</p>
                <p v-if="log.user?.email" class="text-xs text-muted-foreground">{{ log.user.email }}</p>
              </td>
              <td class="td text-center">
                <button v-if="log.old_data || log.new_data" class="text-xs text-primary underline hover:no-underline" @click="toggleDetail(log.id)">
                  {{ expanded.has(log.id) ? 'Tutup' : 'Lihat' }}
                </button>
                <span v-else class="text-xs text-muted-foreground">—</span>
              </td>
            </tr>
            <tr v-if="expanded.has(log.id)" :key="log.id + '-detail'" class="bg-muted/20">
              <td colspan="5" class="px-4 py-3">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                  <!-- Untuk create: hanya tampilkan data baru -->
                  <template v-if="log.action === 'create'">
                    <div class="sm:col-span-2">
                      <p class="font-semibold text-muted-foreground mb-2">Data yang ditambahkan:</p>
                      <div class="bg-background rounded-lg border p-3 space-y-1.5">
                        <div v-for="(val, key) in log.new_data" :key="key" class="flex gap-2">
                          <span class="text-muted-foreground w-36 shrink-0">{{ key }}</span>
                          <span class="font-medium text-gray-800 break-all">{{ val ?? '—' }}</span>
                        </div>
                      </div>
                    </div>
                  </template>
                  <!-- Untuk delete: hanya tampilkan data lama -->
                  <template v-else-if="log.action === 'delete'">
                    <div class="sm:col-span-2">
                      <p class="font-semibold text-muted-foreground mb-2">Data yang dihapus:</p>
                      <div class="bg-background rounded-lg border p-3 space-y-1.5">
                        <div v-for="(val, key) in log.old_data" :key="key" class="flex gap-2">
                          <span class="text-muted-foreground w-36 shrink-0">{{ key }}</span>
                          <span class="font-medium text-gray-800 break-all">{{ val ?? '—' }}</span>
                        </div>
                      </div>
                    </div>
                  </template>
                  <!-- Untuk update: tampilkan perbandingan sebelum & sesudah -->
                  <template v-else>
                    <div class="sm:col-span-2">
                      <p class="font-semibold text-muted-foreground mb-2">Perubahan yang dilakukan:</p>
                      <div class="bg-background rounded-lg border overflow-hidden">
                        <table class="w-full text-xs">
                          <thead class="bg-muted/40 border-b">
                            <tr>
                              <th class="px-3 py-2 text-left font-semibold text-muted-foreground">Field</th>
                              <th class="px-3 py-2 text-left font-semibold text-red-600">Sebelum</th>
                              <th class="px-3 py-2 text-left font-semibold text-green-600">Sesudah</th>
                            </tr>
                          </thead>
                          <tbody>
                            <tr v-for="(newVal, key) in log.new_data" :key="key" class="border-b last:border-0">
                              <td class="px-3 py-2 text-muted-foreground">{{ key }}</td>
                              <td class="px-3 py-2 text-red-700 line-through break-all">{{ log.old_data?.[key] ?? '—' }}</td>
                              <td class="px-3 py-2 text-green-700 font-medium break-all">{{ newVal ?? '—' }}</td>
                            </tr>
                          </tbody>
                        </table>
                      </div>
                    </div>
                  </template>
                </div>
              </td>
            </tr>
          </template>
        </tbody>
      </table>
      </div>
    </div>

    <div v-if="totalPages > 1" class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2 text-sm text-muted-foreground">
      <span class="text-xs">Halaman {{ currentPage }} / {{ totalPages }} · {{ totalLogs }} entri</span>
      <div class="flex gap-2">
        <button class="btn-outline-sm" :disabled="currentPage <= 1" @click="fetchLogs(currentPage - 1)">← Prev</button>
        <button class="btn-outline-sm" :disabled="currentPage >= totalPages" @click="fetchLogs(currentPage + 1)">Next →</button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import axios from 'axios';

interface AuditLog {
  id: string; entity_type: string; entity_label: string; entity_id: string;
  action: 'create' | 'update' | 'delete'; changed_by: string;
  user?: { id: string; name: string; email: string };
  old_data: Record<string, unknown> | null;
  new_data: Record<string, unknown> | null;
  created_at: string;
}

const logs = ref<AuditLog[]>([]);
const loading = ref(false);
const error = ref('');
const currentPage = ref(1);
const totalPages = ref(1);
const totalLogs = ref(0);
const expanded = ref<Set<string>>(new Set());
const dateError = ref('');
const filters = ref({ action: '' as '' | 'create' | 'update' | 'delete', user_name: '', start_date: '', end_date: '' });

async function fetchLogs(page = 1) {
  if (dateError.value) return;
  loading.value = true; error.value = '';
  try {
    const params: Record<string, string | number> = { page };
    if (filters.value.action) params.action = filters.value.action;
    if (filters.value.user_name.trim()) params.user_name = filters.value.user_name.trim();
    if (filters.value.start_date) params.start_date = filters.value.start_date;
    if (filters.value.end_date) params.end_date = filters.value.end_date;
    const r = await axios.get<{ data: { data: AuditLog[]; current_page: number; last_page: number; total: number } }>('/api/audit-logs', { params });
    logs.value = r.data.data.data; currentPage.value = r.data.data.current_page;
    totalPages.value = r.data.data.last_page; totalLogs.value = r.data.data.total;
  } catch { error.value = 'Gagal memuat audit trail.'; }
  finally { loading.value = false; }
}

function onDateChange() {
  dateError.value = '';
  if (filters.value.start_date && filters.value.end_date && filters.value.start_date > filters.value.end_date)
    dateError.value = 'Tanggal awal tidak boleh lebih besar dari tanggal akhir.';
}

function applyFilters() { if (!dateError.value) { expanded.value = new Set(); fetchLogs(1); } }
function resetFilters() { filters.value = { action: '', user_name: '', start_date: '', end_date: '' }; dateError.value = ''; expanded.value = new Set(); fetchLogs(1); }

function toggleDetail(id: string) {
  const s = new Set(expanded.value);
  s.has(id) ? s.delete(id) : s.add(id);
  expanded.value = s;
}

function fmtDateTime(d: string) {
  return d ? new Date(d).toLocaleString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '—';
}

onMounted(() => fetchLogs(1));
</script>

<style scoped>
@reference "../../css/app.css";
.th { @apply px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wide; }
.td { @apply px-4 py-3; }
.field { @apply space-y-1.5; }
.field-label { @apply text-sm font-medium; }
.input-base { @apply flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring transition-colors; }
.btn-primary-sm { @apply inline-flex items-center justify-center rounded-md bg-primary text-primary-foreground text-xs font-medium h-8 px-3 hover:bg-primary/90 disabled:opacity-50 transition-colors; }
.btn-outline-sm { @apply inline-flex items-center justify-center rounded-md border text-xs font-medium h-8 px-3 hover:bg-accent disabled:opacity-50 transition-colors; }
.badge { @apply inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium; }
.badge-green { @apply bg-green-100 text-green-800; }
.badge-blue { @apply bg-blue-100 text-blue-800; }
.badge-red { @apply bg-red-100 text-red-800; }
.alert-error { @apply rounded-lg bg-destructive/10 border border-destructive/20 px-3 py-2.5 text-sm text-destructive; }
.skeleton { @apply bg-muted animate-pulse; }
</style>
