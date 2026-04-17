<template>
  <!-- Root: split screen -->
  <div class="min-h-screen flex font-inter">

    <!-- ═══════════════════════════════════════════
         LEFT PANEL — branding (hidden on mobile)
    ════════════════════════════════════════════ -->
    <div class="brand-panel hidden sm:flex flex-col justify-between p-10 relative overflow-hidden">
      <!-- Decorative circles -->
      <div class="deco-circle deco-circle--top" aria-hidden="true" />
      <div class="deco-circle deco-circle--bottom" aria-hidden="true" />

      <!-- Logo + name -->
      <div class="relative z-10">
        <div class="flex items-center gap-3 mb-12">
          <div class="w-10 h-10 rounded-xl bg-white/15 flex items-center justify-center backdrop-blur-sm border border-white/20">
            <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10" />
            </svg>
          </div>
          <div>
            <p class="text-white font-semibold text-base leading-tight">Cahaya Prima</p>
            <p class="text-white/60 text-xs">Inventory Management</p>
          </div>
        </div>

        <!-- Headline -->
        <h1 class="text-white text-3xl font-bold leading-tight mb-3">
          Kelola stok dengan<br />lebih cerdas
        </h1>
        <p class="text-white/70 text-sm leading-relaxed max-w-xs">
          Platform inventaris terpadu untuk toko agen minuman — real-time, akurat, dan mudah digunakan.
        </p>
      </div>

      <!-- Stats -->
      <div class="relative z-10 space-y-3">
        <div v-for="stat in stats" :key="stat.label" class="flex items-center gap-3">
          <div class="w-8 h-8 rounded-lg bg-white/10 flex items-center justify-center shrink-0">
            <span class="text-sm" aria-hidden="true">{{ stat.icon }}</span>
          </div>
          <div>
            <p class="text-white text-sm font-medium">{{ stat.label }}</p>
            <p class="text-white/55 text-xs">{{ stat.desc }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════
         RIGHT PANEL — login form
    ════════════════════════════════════════════ -->
    <div class="flex-1 flex items-center justify-center bg-gray-50 px-4 py-12">
      <div class="login-card w-full max-w-[360px]">

        <!-- Mobile logo (only visible on small screens) -->
        <div class="sm:hidden text-center mb-8">
          <div class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-navy mb-3">
            <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10" />
            </svg>
          </div>
          <h2 class="text-xl font-bold text-gray-900">Cahaya Prima</h2>
          <p class="text-sm text-gray-500">Inventory Management</p>
        </div>

        <!-- Card header -->
        <div class="mb-6">
          <h2 class="text-xl font-bold text-gray-900">Selamat datang</h2>
          <p class="text-sm text-gray-500 mt-1">Masuk ke akun Anda untuk melanjutkan</p>
        </div>

        <!-- ── Error banner ── -->
        <Transition name="error-fade">
          <div
            v-if="errorMsg"
            role="alert"
            class="flex items-start gap-2.5 rounded-lg bg-red-50 border border-red-200 px-3.5 py-3 text-sm text-red-700 mb-5"
          >
            <svg class="w-4 h-4 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
            </svg>
            <span>{{ errorMsg }}</span>
          </div>
        </Transition>

        <!-- ── Form ── -->
        <form @submit.prevent="handleSubmit" novalidate class="space-y-4">

          <!-- Email -->
          <div class="space-y-1.5">
            <label for="email" class="field-label">Email</label>
            <div class="input-wrapper" :class="{ 'input-error': fieldErrors.email, 'input-focus': focusedField === 'email' }">
              <svg class="input-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
              </svg>
              <input
                id="email"
                v-model="email"
                type="email"
                placeholder="nama@contoh.com"
                autocomplete="email"
                :disabled="loading"
                class="input-field"
                @focus="focusedField = 'email'"
                @blur="focusedField = ''; validateEmail()"
                @input="clearError('email')"
                aria-describedby="email-error"
              />
            </div>
            <p v-if="fieldErrors.email" id="email-error" class="field-error" role="alert">{{ fieldErrors.email }}</p>
          </div>

          <!-- Password -->
          <div class="space-y-1.5">
            <label for="password" class="field-label">Kata Sandi</label>
            <div class="input-wrapper" :class="{ 'input-error': fieldErrors.password, 'input-focus': focusedField === 'password' }">
              <svg class="input-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
              </svg>
              <input
                id="password"
                v-model="password"
                :type="showPassword ? 'text' : 'password'"
                placeholder="Minimal 6 karakter"
                autocomplete="current-password"
                :disabled="loading"
                class="input-field pr-10"
                @focus="focusedField = 'password'"
                @blur="focusedField = ''; validatePassword()"
                @input="clearError('password')"
                aria-describedby="password-error"
              />
              <button
                type="button"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors p-0.5"
                :aria-label="showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
                @click="showPassword = !showPassword"
              >
                <!-- Eye icon -->
                <svg v-if="!showPassword" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <!-- Eye-off icon -->
                <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                </svg>
              </button>
            </div>
            <p v-if="fieldErrors.password" id="password-error" class="field-error" role="alert">{{ fieldErrors.password }}</p>
          </div>

          <!-- Remember me + forgot password -->
          <div class="flex items-center justify-between">
            <label class="flex items-center gap-2 cursor-pointer select-none">
              <input
                v-model="rememberMe"
                type="checkbox"
                class="w-4 h-4 rounded border-gray-300 text-navy accent-navy cursor-pointer"
              />
              <span class="text-sm text-gray-600">Ingat saya</span>
            </label>
            <a href="#" class="text-sm text-navy font-medium hover:underline" tabindex="0">
              Lupa kata sandi?
            </a>
          </div>

          <!-- Submit button -->
          <button
            type="submit"
            :disabled="loading"
            class="submit-btn"
            aria-label="Masuk ke sistem"
          >
            <span v-if="loading" class="inline-flex items-center gap-2">
              <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
              </svg>
              Memproses...
            </span>
            <span v-else>Masuk</span>
          </button>
        </form>

        <!-- Divider -->
        <div class="flex items-center gap-3 my-5">
          <div class="flex-1 h-px bg-gray-200" />
          <span class="text-xs text-gray-400 font-medium">atau</span>
          <div class="flex-1 h-px bg-gray-200" />
        </div>

        <!-- Footer note -->
        <p class="text-center text-sm text-gray-500">
          Belum punya akun?
          <span class="text-navy font-medium">Hubungi pengelola</span>
        </p>
      </div>
    </div>

  </div>
</template>

<script setup lang="ts">
// ── Imports ──────────────────────────────────────────────────────────────────
import { ref, reactive } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

// ── Router & store ────────────────────────────────────────────────────────────
const router = useRouter();
const auth = useAuthStore();

// ── Form state ────────────────────────────────────────────────────────────────
const email = ref('');
const password = ref('');
const rememberMe = ref(false);
const showPassword = ref(false);
const loading = ref(false);
const errorMsg = ref('');
const focusedField = ref('');

// Per-field validation errors
const fieldErrors = reactive({ email: '', password: '' });

// ── Branding stats ────────────────────────────────────────────────────────────
const stats = [
  { icon: '📦', label: 'Manajemen Produk', desc: 'Kelola ribuan SKU dengan mudah' },
  { icon: '👥', label: 'Multi Peran', desc: 'Pengelola & Kasir dengan akses berbeda' },
  { icon: '📊', label: 'Monitoring Real-time', desc: 'Pantau stok & transaksi setiap saat' },
];

// ── Validation helpers ────────────────────────────────────────────────────────
function validateEmail(): boolean {
  if (!email.value.trim()) {
    fieldErrors.email = 'Email wajib diisi.';
    return false;
  }
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!emailRegex.test(email.value)) {
    fieldErrors.email = 'Format email tidak valid.';
    return false;
  }
  fieldErrors.email = '';
  return true;
}

function validatePassword(): boolean {
  if (!password.value) {
    fieldErrors.password = 'Kata sandi wajib diisi.';
    return false;
  }
  if (password.value.length < 6) {
    fieldErrors.password = 'Kata sandi minimal 6 karakter.';
    return false;
  }
  fieldErrors.password = '';
  return true;
}

// Clear error when user starts typing again
function clearError(field: 'email' | 'password') {
  fieldErrors[field] = '';
  errorMsg.value = '';
}

// ── Submit handler ────────────────────────────────────────────────────────────
async function handleSubmit() {
  // Run full validation before submit
  const emailOk = validateEmail();
  const passwordOk = validatePassword();
  if (!emailOk || !passwordOk) return;

  errorMsg.value = '';
  loading.value = true;

  try {
    const response = await auth.login({ email: email.value, password: password.value });
    const user = response.data.user;

    // Redirect based on state
    if (user.must_change_password) {
      router.replace('/change-password');
    } else {
      router.replace(user.role === 'kasir' ? '/products' : '/dashboard');
    }
  } catch (err: unknown) {
    const e = err as { response?: { data?: { error?: { message?: string } } } };
    errorMsg.value = e?.response?.data?.error?.message ?? 'Email atau kata sandi salah.';
  } finally {
    loading.value = false;
  }
}
</script>

<style scoped>
@reference "../../css/app.css";

/* ── Font ── */
.font-inter { font-family: 'Inter', ui-sans-serif, system-ui, sans-serif; }

/* ── Left panel ── */
.brand-panel {
  width: 420px;
  min-width: 380px;
  background-color: #1e3a5f;
  flex-shrink: 0;
}

/* Decorative circles */
.deco-circle {
  position: absolute;
  border-radius: 9999px;
  background: rgba(255, 255, 255, 0.06);
  pointer-events: none;
}
.deco-circle--top {
  width: 320px;
  height: 320px;
  top: -80px;
  right: -80px;
}
.deco-circle--bottom {
  width: 240px;
  height: 240px;
  bottom: -60px;
  left: -60px;
}

/* ── Login card ── */
.login-card {
  background: #ffffff;
  border-radius: 16px;
  padding: 32px;
  box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08), 0 1px 4px rgba(0, 0, 0, 0.04);
  animation: fadeUp 0.3s ease both;
}

@keyframes fadeUp {
  from { opacity: 0; transform: translateY(10px); }
  to   { opacity: 1; transform: translateY(0); }
}

/* ── Field label ── */
.field-label {
  @apply block text-sm font-medium text-gray-700;
}

/* ── Input wrapper ── */
.input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
  border: 1.5px solid #e5e7eb;
  border-radius: 8px;
  background: #f9fafb;
  transition: border-color 0.15s, box-shadow 0.15s;
}
.input-wrapper:hover {
  border-color: #d1d5db;
}
.input-wrapper.input-focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.12);
  background: #ffffff;
}
.input-wrapper.input-error {
  border-color: #f87171;
  box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.08);
}

/* ── Input icon ── */
.input-icon {
  width: 16px;
  height: 16px;
  color: #9ca3af;
  flex-shrink: 0;
  margin-left: 12px;
}

/* ── Input field ── */
.input-field {
  flex: 1;
  height: 40px;
  padding: 0 12px;
  background: transparent;
  border: none;
  outline: none;
  font-size: 14px;
  color: #111827;
  min-width: 0;
}
.input-field::placeholder {
  color: #9ca3af;
}
.input-field:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* ── Field error text ── */
.field-error {
  @apply text-xs text-red-500 mt-1;
}

/* ── Navy color ── */
.text-navy { color: #1e3a5f; }
.bg-navy   { background-color: #1e3a5f; }
.accent-navy { accent-color: #1e3a5f; }

/* ── Submit button ── */
.submit-btn {
  width: 100%;
  height: 42px;
  background-color: #1e3a5f;
  color: #ffffff;
  font-size: 14px;
  font-weight: 600;
  border-radius: 8px;
  border: none;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  transition: background-color 0.15s, transform 0.15s, box-shadow 0.15s;
}
.submit-btn:hover:not(:disabled) {
  background-color: #16304f;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(30, 58, 95, 0.35);
}
.submit-btn:active:not(:disabled) {
  transform: translateY(0);
  box-shadow: none;
}
.submit-btn:disabled {
  opacity: 0.65;
  cursor: not-allowed;
}

/* ── Error banner transition ── */
.error-fade-enter-active,
.error-fade-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}
.error-fade-enter-from,
.error-fade-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}
</style>
