<template>
  <div class="flex h-screen overflow-hidden bg-gray-50">
    <div
      v-if="mobileOpen"
      class="fixed inset-0 z-30 bg-slate-950/30 lg:hidden"
      @click="mobileOpen = false"
    />

    <aside
      class="fixed inset-y-0 left-0 z-40 flex w-[260px] flex-col border-r border-gray-200 bg-white transition-transform duration-200 lg:static lg:translate-x-0"
      :class="mobileOpen ? 'translate-x-0' : '-translate-x-full'"
    >
      <div class="flex items-center gap-3 border-b border-gray-200 px-5 py-5">
        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary/10 text-primary">
          <Package class="h-5 w-5" />
        </div>
        <div>
          <p class="text-sm font-semibold text-gray-900">Cahaya Prima</p>
          <p class="text-xs text-gray-500">Inventory System</p>
        </div>
      </div>

      <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
        <RouterLink
          v-for="item in visibleItems"
          :key="item.path"
          :to="item.path"
          class="nav-item"
          active-class="nav-item-active"
        >
          <component :is="item.icon" class="h-4 w-4 shrink-0" />
          <span>{{ item.label }}</span>
        </RouterLink>
      </nav>

      <div class="border-t border-gray-200 p-4">
        <div class="rounded-xl border border-gray-200 bg-gray-50 p-3">
          <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-white text-sm font-semibold text-gray-700 shadow-sm">
              {{ userInitial }}
            </div>
            <div class="min-w-0">
              <p class="truncate text-sm font-medium text-gray-900">{{ auth.user?.name || auth.user?.email?.split('@')[0] }}</p>
              <p class="truncate text-xs text-gray-500">{{ auth.user?.email }}</p>
            </div>
          </div>
          <div class="mt-3 flex items-center justify-between">
            <span class="rounded-full bg-primary/10 px-2.5 py-1 text-[11px] font-medium capitalize text-primary">
              {{ auth.user?.role }}
            </span>
            <button
              class="inline-flex items-center gap-1 rounded-lg px-2 py-1 text-xs font-medium text-red-600 transition hover:bg-red-50"
              @click="handleLogout"
            >
              <LogOut class="h-3.5 w-3.5" />
              Keluar
            </button>
          </div>
        </div>
      </div>
    </aside>

    <div class="flex min-w-0 flex-1 flex-col">
      <header class="border-b border-gray-200 bg-white">
        <div class="flex h-16 items-center gap-3 px-4 sm:px-6">
          <button
            class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-gray-200 text-gray-600 transition hover:bg-gray-50 lg:hidden"
            @click="mobileOpen = !mobileOpen"
            aria-label="Menu"
          >
            <Menu class="h-5 w-5" />
          </button>

<div class="ml-auto flex items-center gap-3">
            <div class="relative">
              <NotificationBell @toggle="toggleDropdown" />
              <NotificationDropdown v-model="showDropdown" />
            </div>

            <button class="hidden items-center gap-3 rounded-xl border border-gray-200 px-3 py-2 transition hover:bg-gray-50 sm:inline-flex">
              <div class="flex h-8 w-8 items-center justify-center rounded-full bg-primary/10 text-xs font-semibold text-primary">
                {{ userInitial }}
              </div>
              <div class="text-left">
                <p class="text-sm font-medium text-gray-900">{{ auth.user?.name || auth.user?.email?.split('@')[0] }}</p>
                <p class="text-xs text-gray-500 capitalize">{{ auth.user?.role }}</p>
              </div>
            </button>
          </div>
        </div>
      </header>

      <main class="flex-1 overflow-auto">
        <div class="mx-auto w-full max-w-[1440px] p-4 sm:p-6">
          <div class="mb-6">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">Workspace</p>
            <h1 class="mt-2 text-2xl font-semibold tracking-tight text-gray-900">{{ currentPageTitle }}</h1>
          </div>

          <RouterView v-slot="{ Component }">
            <Transition name="page" mode="out-in">
              <component :is="Component" />
            </Transition>
          </RouterView>
        </div>
      </main>
    </div>
  </div>

  <ToastContainer />
</template>

<script setup lang="ts">
import { computed, ref, watch, onMounted, onUnmounted } from 'vue';
import { RouterLink, RouterView, useRoute, useRouter } from 'vue-router';
import {
  AlertTriangle,
  BarChart2,
  Bell,
  LayoutDashboard,
  LogOut,
  Menu,
  Package,
  Search,
  Tag,
  Users,
} from 'lucide-vue-next';
import { useAuthStore } from '@/stores/auth';
import { useNotificationStore } from '@/stores/notification';
import { useFCM } from '@/composables/useFCM';
import { useIdleLogout } from '@/composables/useIdleLogout';
import { useToast } from '@/composables/useToast';
import NotificationBell from '@/components/NotificationBell.vue';
import NotificationDropdown from '@/components/NotificationDropdown.vue';
import ToastContainer from '@/components/ToastContainer.vue';
import type { Component } from 'vue';

interface NavItem {
  label: string;
  path: string;
  icon: Component;
  roles: Array<'pengelola' | 'kasir'>;
}

const NAV_ITEMS: NavItem[] = [
  { label: 'Dashboard', path: '/dashboard', roles: ['pengelola'], icon: LayoutDashboard },
  { label: 'Kategori', path: '/categories', roles: ['pengelola'], icon: Tag },
  { label: 'Inventory', path: '/products', roles: ['pengelola', 'kasir'], icon: Package },
  { label: 'Stok Menipis', path: '/low-stock', roles: ['pengelola'], icon: AlertTriangle },
  { label: 'Laporan', path: '/reports', roles: ['pengelola'], icon: BarChart2 },
  { label: 'Users', path: '/users', roles: ['pengelola'], icon: Users },
  { label: 'Notifikasi', path: '/notifications', roles: ['pengelola', 'kasir'], icon: Bell },
];

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();
const notifStore = useNotificationStore();
const toast = useToast();
const mobileOpen = ref(false);
const showDropdown = ref(false);
const searchQuery = ref('');

const { stop: stopIdleLogout } = useIdleLogout({
  timeoutMs: 15 * 60 * 1000,
  onIdle: async () => {
    if (!auth.isAuthenticated) return;
    await auth.logout();
    toast.show('Sesi berakhir karena tidak aktif', 'warning');
    router.replace('/login');
  },
});

const role = computed(() => auth.user?.role ?? 'kasir');
const visibleItems = computed(() =>
  NAV_ITEMS.filter((item) => item.roles.includes(role.value as 'pengelola' | 'kasir'))
);
const currentPageTitle = computed(() => {
  const match = NAV_ITEMS.find((item) => route.path.startsWith(item.path));
  return match?.label ?? 'Inventori';
});
const userInitial = computed(() => (auth.user?.email?.charAt(0) ?? 'U').toUpperCase());

function toggleDropdown() {
  showDropdown.value = !showDropdown.value;
  if (showDropdown.value && !notifStore.initialized) {
    notifStore.fetchNotifications().catch(() => undefined);
  }
}

function goSearch() {
  const query = searchQuery.value.trim();
  router.push({
    path: '/products',
    query: query ? { search: query } : {},
  });
}

async function handleLogout() {
  await auth.logout();
  router.replace('/login');
}

watch(route, () => {
  mobileOpen.value = false;
  showDropdown.value = false;
});

onMounted(() => {
  useFCM().init().catch(() => undefined);
});

onUnmounted(() => {
  stopIdleLogout();
});
</script>

<style scoped>
@reference "../../css/app.css";

.nav-item {
  @apply flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium text-gray-600 transition;
}

.nav-item:hover {
  @apply bg-gray-100 text-gray-900;
}

.nav-item-active {
  @apply bg-primary/10 text-primary;
}
</style>
