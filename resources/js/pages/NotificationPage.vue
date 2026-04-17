<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { CheckCircle, AlertTriangle, XCircle, Info, X, Bell } from 'lucide-vue-next';
import { useNotificationStore, type NotificationType } from '@/stores/notification';

const notifStore = useNotificationStore();

// Filter tab state
type FilterTab = 'all' | NotificationType;
const activeTab = ref<FilterTab>('all');
const tabs: { key: FilterTab; label: string }[] = [
    { key: 'all', label: 'Semua' },
    { key: 'success', label: 'Success' },
    { key: 'warning', label: 'Warning' },
    { key: 'danger', label: 'Danger' },
    { key: 'info', label: 'Info' },
];

// Filtered + sorted notifications (newest first)
const filteredNotifications = computed(() => {
    const sorted = [...notifStore.notifications].sort(
        (a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime()
    );
    if (activeTab.value === 'all') return sorted;
    return sorted.filter((n) => n.type === activeTab.value);
});

// Icon + color config per type
const typeConfig: Record<NotificationType, { icon: typeof CheckCircle; iconClass: string; bgClass: string }> = {
    success: { icon: CheckCircle, iconClass: 'text-green-600', bgClass: 'bg-green-50' },
    warning: { icon: AlertTriangle, iconClass: 'text-yellow-600', bgClass: 'bg-yellow-50' },
    danger: { icon: XCircle, iconClass: 'text-red-600', bgClass: 'bg-red-50' },
    info: { icon: Info, iconClass: 'text-blue-600', bgClass: 'bg-blue-50' },
};

// Relative time helper
function relativeTime(dateStr: string): string {
    const now = Date.now();
    const diff = Math.floor((now - new Date(dateStr).getTime()) / 1000);
    if (diff < 60) return `${diff} detik lalu`;
    if (diff < 3600) return `${Math.floor(diff / 60)} menit lalu`;
    if (diff < 86400) return `${Math.floor(diff / 3600)} jam lalu`;
    return `${Math.floor(diff / 86400)} hari lalu`;
}

// Load more
async function loadMore() {
    if (!notifStore.hasMore || notifStore.loading) return;
    await notifStore.fetchNotifications(notifStore.page + 1);
}

onMounted(async () => {
    await notifStore.fetchNotifications();
    await notifStore.markAllAsRead();
});
</script>

<template>
    <div class="max-w-2xl mx-auto px-4 py-6">
        <!-- Header -->
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-xl font-semibold text-gray-800">Notifikasi</h1>
            <div class="flex gap-2">
                <button
                    class="text-sm text-blue-600 hover:underline"
                    @click="notifStore.markAllAsRead()"
                >
                    Tandai semua dibaca
                </button>
                <span class="text-gray-300">|</span>
                <button
                    class="text-sm text-red-500 hover:underline"
                    @click="notifStore.clearAll()"
                >
                    Hapus semua
                </button>
            </div>
        </div>

        <!-- Filter tabs -->
        <div class="flex gap-1 mb-4 border-b border-gray-200 overflow-x-auto scrollbar-none -mb-px">
            <button
                v-for="tab in tabs"
                :key="tab.key"
                class="px-3 py-2 text-sm font-medium whitespace-nowrap shrink-0 transition-colors border-b-2"
                :class="
                    activeTab === tab.key
                        ? 'border-blue-600 text-blue-600'
                        : 'border-transparent text-gray-500 hover:text-gray-700'
                "
                @click="activeTab = tab.key"
            >
                {{ tab.label }}
            </button>
        </div>

        <!-- Notification list -->
        <div v-if="filteredNotifications.length > 0" class="space-y-2">
            <div
                v-for="notif in filteredNotifications"
                :key="notif.id"
                class="flex items-start gap-3 p-3 rounded-lg border border-gray-100 bg-white hover:bg-gray-50 transition-colors"
            >
                <!-- Icon -->
                <div
                    class="flex-shrink-0 w-9 h-9 rounded-full flex items-center justify-center"
                    :class="typeConfig[notif.type].bgClass"
                >
                    <component
                        :is="typeConfig[notif.type].icon"
                        class="w-5 h-5"
                        :class="typeConfig[notif.type].iconClass"
                    />
                </div>

                <!-- Content -->
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-800 truncate">{{ notif.title }}</p>
                    <p class="text-sm text-gray-500 mt-0.5">{{ notif.message }}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ relativeTime(notif.created_at) }}</p>
                </div>

                <!-- Dismiss button -->
                <button
                    class="flex-shrink-0 text-gray-400 hover:text-gray-600 transition-colors"
                    aria-label="Hapus notifikasi"
                    @click="notifStore.dismissNotification(notif.id)"
                >
                    <X class="w-4 h-4" />
                </button>
            </div>
        </div>

        <!-- Empty state -->
        <div v-else class="flex flex-col items-center justify-center py-16 text-gray-400">
            <Bell class="w-12 h-12 mb-3 opacity-30" />
            <p class="text-sm">Tidak ada notifikasi</p>
        </div>

        <!-- Load more -->
        <div v-if="notifStore.hasMore && filteredNotifications.length > 0" class="mt-4 text-center">
            <button
                class="text-sm text-blue-600 hover:underline disabled:opacity-50"
                :disabled="notifStore.loading"
                @click="loadMore"
            >
                {{ notifStore.loading ? 'Memuat...' : 'Muat lebih banyak' }}
            </button>
        </div>
    </div>
</template>
