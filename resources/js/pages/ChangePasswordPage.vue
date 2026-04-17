<template>
  <div class="min-h-screen flex items-center justify-center bg-background px-4">
    <div class="w-full max-w-sm">
      <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-primary mb-4">
          <span class="text-primary-foreground font-bold text-lg">IN</span>
        </div>
        <h1 class="text-xl font-bold">Ganti Kata Sandi</h1>
        <p class="text-sm text-muted-foreground mt-1">Anda wajib mengganti kata sandi sebelum melanjutkan.</p>
      </div>

      <div class="bg-card border rounded-xl shadow-sm p-6 space-y-5">
        <div v-if="error" class="flex items-start gap-2 rounded-lg bg-destructive/10 border border-destructive/20 px-3 py-2.5 text-sm text-destructive">
          <span class="shrink-0 mt-0.5">✕</span>
          <span>{{ error }}</span>
        </div>
        <div v-if="success" class="flex items-start gap-2 rounded-lg bg-green-50 border border-green-200 px-3 py-2.5 text-sm text-green-800">
          <span class="shrink-0 mt-0.5">✓</span>
          <span>{{ success }}</span>
        </div>

        <form @submit.prevent="handleSubmit" class="space-y-4">
          <div class="space-y-1.5">
            <label class="text-sm font-medium">Kata Sandi Saat Ini</label>
            <input v-model="form.current_password" type="password" required :disabled="loading" class="input-base" />
          </div>
          <div class="space-y-1.5">
            <label class="text-sm font-medium">Kata Sandi Baru</label>
            <input v-model="form.new_password" type="password" required :disabled="loading" class="input-base" />
          </div>
          <div class="space-y-1.5">
            <label class="text-sm font-medium">Konfirmasi Kata Sandi Baru</label>
            <input v-model="form.new_password_confirmation" type="password" required :disabled="loading" class="input-base" />
            <p v-if="mismatch" class="text-xs text-destructive">Kata sandi tidak cocok.</p>
          </div>
          <button type="submit" :disabled="loading || mismatch" class="btn-primary w-full">
            <span v-if="loading" class="inline-flex items-center gap-2"><span class="spinner" />Menyimpan...</span>
            <span v-else>Simpan Kata Sandi</span>
          </button>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const router = useRouter();
const auth = useAuthStore();

const loading = ref(false);
const error = ref('');
const success = ref('');
const form = ref({ current_password: '', new_password: '', new_password_confirmation: '' });

const mismatch = computed(() =>
  form.value.new_password_confirmation.length > 0 &&
  form.value.new_password !== form.value.new_password_confirmation
);

async function handleSubmit() {
  error.value = '';
  success.value = '';
  loading.value = true;
  try {
    await auth.changePassword(form.value);
    success.value = 'Kata sandi berhasil diubah. Mengalihkan...';
    setTimeout(() => {
      router.replace(auth.isKasir ? '/transactions/out' : '/dashboard');
    }, 1200);
  } catch (err: unknown) {
    const e = err as { response?: { data?: { error?: { message?: string } } } };
    error.value = e?.response?.data?.error?.message ?? 'Gagal mengubah kata sandi.';
  } finally {
    loading.value = false;
  }
}
</script>

<style scoped>
@reference "../../css/app.css";
.input-base {
  @apply flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm
         placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2
         focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50 transition-colors;
}
.btn-primary {
  @apply inline-flex items-center justify-center rounded-md bg-primary text-primary-foreground
         text-sm font-medium h-10 px-4 transition-colors hover:bg-primary/90
         disabled:pointer-events-none disabled:opacity-50;
}
.spinner {
  @apply inline-block w-4 h-4 border-2 border-primary-foreground/30 border-t-primary-foreground
         rounded-full animate-spin;
}
</style>
