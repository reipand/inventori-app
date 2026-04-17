<template>
  <div class="space-y-5">
    <div>
      <h2 class="text-lg font-semibold">Stok Rendah</h2>
      <p class="text-sm text-muted-foreground">Produk yang perlu segera diisi ulang</p>
    </div>

    <div v-if="loading" class="space-y-2">
      <div v-for="i in 5" :key="i" class="skeleton h-12 rounded-lg" />
    </div>
    <div v-else-if="error" class="alert-error">{{ error }}</div>
    <div v-else-if="products.length === 0" class="rounded-xl border bg-card p-12 text-center">
      <span class="text-4xl block mb-3">✅</span>
      <p class="font-medium">Semua stok dalam kondisi baik!</p>
      <p class="text-sm text-muted-foreground mt-1">Tidak ada produk dengan stok rendah atau habis.</p>
    </div>
    <div v-else class="rounded-xl border bg-card overflow-hidden">
      <div class="overflow-x-auto">
      <table class="w-full text-sm min-w-[480px]">
        <thead class="border-b bg-muted/40">
          <tr>
            <th class="th">Nama Produk</th>
            <th class="th hidden sm:table-cell">SKU</th>
            <th class="th hidden md:table-cell">Kategori</th>
            <th class="th text-right">Stok</th>
            <th class="th text-right hidden sm:table-cell">Min</th>
            <th class="th text-right hidden sm:table-cell">Selisih</th>
            <th class="th text-center">Status</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="p in products" :key="p.id" class="border-b last:border-0 hover:bg-muted/30 transition-colors">
            <td class="td font-medium">
              {{ p.name }}
              <p class="text-xs text-muted-foreground font-mono sm:hidden mt-0.5">{{ p.sku }}</p>
            </td>
            <td class="td font-mono text-xs text-muted-foreground hidden sm:table-cell">{{ p.sku }}</td>
            <td class="td text-muted-foreground hidden md:table-cell">{{ p.category?.name ?? '—' }}</td>
            <td class="td text-right font-medium" :class="p.current_stock === 0 ? 'text-destructive' : 'text-yellow-700'">{{ p.current_stock }}</td>
            <td class="td text-right text-muted-foreground hidden sm:table-cell">{{ p.min_stock }}</td>
            <td class="td text-right font-medium hidden sm:table-cell" :class="p.current_stock === 0 ? 'text-destructive' : 'text-yellow-700'">{{ p.current_stock - p.min_stock }}</td>
            <td class="td text-center">
              <span v-if="p.current_stock === 0" class="badge badge-red">Habis</span>
              <span v-else class="badge badge-yellow">Rendah</span>
            </td>
          </tr>
        </tbody>
      </table>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import axios from 'axios';
import type { Product } from '@/services/productService';

const products = ref<Product[]>([]);
const loading = ref(true);
const error = ref('');

onMounted(async () => {
  try {
    const res = await axios.get<{ data: Product[] }>('/api/products/low-stock');
    products.value = res.data.data;
  } catch { error.value = 'Gagal memuat data stok rendah.'; }
  finally { loading.value = false; }
});
</script>

<style scoped>
@reference "../../css/app.css";
.th { @apply px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wide; }
.td { @apply px-4 py-3; }
.badge { @apply inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium; }
.badge-red { @apply bg-red-100 text-red-800; }
.badge-yellow { @apply bg-yellow-100 text-yellow-800; }
.alert-error { @apply rounded-lg bg-destructive/10 border border-destructive/20 px-3 py-2.5 text-sm text-destructive; }
.skeleton { @apply bg-muted animate-pulse; }
</style>
