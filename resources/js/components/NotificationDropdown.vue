<script setup lang="ts">
import { computed } from 'vue';
import { useRouter } from 'vue-router';
import { CheckCircle, AlertTriangle, XCircle, Info, Bell, RefreshCw } from 'lucide-vue-next';
import { useNotificationStore, type Notification, type NotificationType } from '../stores/notification';

const props = defineProps<{
    modelValue: boolean;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: boolean];
}>();

const router = useRouter();
const notifStore = useNotificationStore();

const typeIconMap: Record<NotificationType, typeof CheckCircle> = {
    success: CheckCircle,
    warning: AlertTriangle,
    danger: XCircle,
    info: Info,
};

const typeColorMap: Record<NotificationType, string> = {
    success: 'text-green-600',
    warning: 'text-yellow-600',
    danger: 'text-red-600',
    info: 'text-blue-600',
};

function isToday(dateStr: string): boolean {
    const d = new Date(dateStr);
    const now = new Date();
    return d.toDateString() === now.toDateString();
}

function isYesterday(dateStr: string): boolean {
    const d = new Date(dateStr);
    const yesterday = new Date();
    yesterday.setDate(yesterday.getDate() - 1);
    return d.toDateString() === yesterday.toDateString();
}

const grouped = computed(() => {
    const today: Notification[] = [];
    const yesterday: Notification[] = [];
    const older: Notification[] = [];

    for (const n of notifStore.notifications) {
        if (isToday(n.created_at)) today.push(n);
        else if (isYesterday(n.created_at)) yesterday.push(n);
        else older.push(n);
    }

    return { today, yesterday, older };
});

function relativeTime(dateStr: string): string {
    const diff = Date.now() - new Date(dateStr).getTime();
    const minutes = Math.floor(diff / 60000);
    if (minutes < 1) return 'Baru saja';
    if (minutes < 60) return `${minutes} menit lalu`;
    const hours = Math.floor(minutes / 60);
    if (hours < 24) return `${hours} jam lalu`;
    const days = Math.floor(hours / 24);
    return `${days} hari lalu`;
}

async function handleItemClick(notification: Notification) {
    await notifStore.markAsRead(notification.id);
    if (notification.link) {
        emit('update:modelValue', false);
        router.push(notification.link);
    }
}

async function handleMarkAllRead() {
    await notifStore.markAllAsRead();
}

async function handleRetry() {
    notifStore.clearError();
    await notifStore.fetchNotifications();
}
</script>

<template>
    <Transition name="notif-dropdown">
        <div
            v-if="modelValue"
            class="absolute right-0 top-full z-50 mt-2 flex w-[360px] flex-col rounded-xl border border-gray-200 bg-white shadow-md"
        >
            <!-- Header -->
            <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3">
                <span class="font-semibold text-gray-800 text-sm">Notifikasi</span>
                <button
                    v-if="notifStore.notifications.length > 0 && !notifStore.error"
                    class="text-xs font-medium text-primary transition-colors duration-150 hover:text-primary/80"
                    @click="handleMarkAllRead"
                >
                    Tandai semua dibaca
                </button>
            </div>

            <!-- Scrollable list -->
            <div class="overflow-y-auto" style="max-height: 400px;">

                <!-- Skeleton loading -->
                <template v-if="notifStore.loading">
                    <div v-for="i in 3" :key="i" class="flex gap-3 px-4 py-3 border-b border-gray-50">
                        <div class="animate-pulse bg-gray-200 rounded-full w-8 h-8 flex-shrink-0"></div>
                        <div class="flex-1 space-y-2">
                            <div class="animate-pulse bg-gray-200 rounded h-3 w-3/4"></div>
                            <div class="animate-pulse bg-gray-200 rounded h-3 w-full"></div>
                            <div class="animate-pulse bg-gray-200 rounded h-2 w-1/3"></div>
                        </div>
                    </div>
                </template>

                <!-- Error state -->
                <template v-else-if="notifStore.error">
                    <div class="flex flex-col items-center justify-center py-10 px-4 text-center gap-3">
                        <XCircle class="w-10 h-10 text-red-400 opacity-70" />
                        <span class="text-sm text-gray-600">Gagal memuat notifikasi</span>
                        <button
                            class="flex items-center gap-1.5 text-xs text-blue-600 hover:text-blue-800 transition-colors duration-150 border border-blue-200 hover:border-blue-400 rounded-lg px-3 py-1.5"
                            @click="handleRetry"
                        >
                            <RefreshCw class="w-3.5 h-3.5" />
                            Coba Ulang
                        </button>
                    </div>
                </template>

                <!-- Empty state -->
                <template v-else-if="notifStore.notifications.length === 0">
                    <div class="flex flex-col items-center justify-center py-12 text-gray-400">
                        <Bell class="w-10 h-10 mb-3 opacity-40" />
                        <span class="text-sm">Tidak ada notifikasi</span>
                    </div>
                </template>

                <!-- Grouped notifications -->
                <template v-else>
                    <!-- Hari ini -->
                    <template v-if="grouped.today.length > 0">
                        <div class="px-4 py-2 text-xs font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">
                            Hari ini
                        </div>
                        <button
                            v-for="n in grouped.today"
                            :key="n.id"
                            class="w-full text-left flex gap-3 px-4 py-3 border-b border-gray-50 transition-colors duration-150 hover:bg-gray-50"
                            :class="{ 'bg-blue-50': !n.is_read }"
                            @click="handleItemClick(n)"
                        >
                            <component
                                :is="typeIconMap[n.type]"
                                class="w-5 h-5 flex-shrink-0 mt-0.5"
                                :class="typeColorMap[n.type]"
                            />
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-800 truncate">{{ n.title }}</p>
                                <p class="text-xs text-gray-500 mt-0.5 line-clamp-2">{{ n.message }}</p>
                                <p class="text-xs text-gray-400 mt-1">{{ relativeTime(n.created_at) }}</p>
                            </div>
                        </button>
                    </template>

                    <!-- Kemarin -->
                    <template v-if="grouped.yesterday.length > 0">
                        <div class="px-4 py-2 text-xs font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">
                            Kemarin
                        </div>
                        <button
                            v-for="n in grouped.yesterday"
                            :key="n.id"
                            class="w-full text-left flex gap-3 px-4 py-3 border-b border-gray-50 transition-colors duration-150 hover:bg-gray-50"
                            :class="{ 'bg-blue-50': !n.is_read }"
                            @click="handleItemClick(n)"
                        >
                            <component
                                :is="typeIconMap[n.type]"
                                class="w-5 h-5 flex-shrink-0 mt-0.5"
                                :class="typeColorMap[n.type]"
                            />
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-800 truncate">{{ n.title }}</p>
                                <p class="text-xs text-gray-500 mt-0.5 line-clamp-2">{{ n.message }}</p>
                                <p class="text-xs text-gray-400 mt-1">{{ relativeTime(n.created_at) }}</p>
                            </div>
                        </button>
                    </template>

                    <!-- Lebih lama -->
                    <template v-if="grouped.older.length > 0">
                        <div class="px-4 py-2 text-xs font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">
                            Lebih lama
                        </div>
                        <button
                            v-for="n in grouped.older"
                            :key="n.id"
                            class="w-full text-left flex gap-3 px-4 py-3 border-b border-gray-50 transition-colors duration-150 hover:bg-gray-50"
                            :class="{ 'bg-blue-50': !n.is_read }"
                            @click="handleItemClick(n)"
                        >
                            <component
                                :is="typeIconMap[n.type]"
                                class="w-5 h-5 flex-shrink-0 mt-0.5"
                                :class="typeColorMap[n.type]"
                            />
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-800 truncate">{{ n.title }}</p>
                                <p class="text-xs text-gray-500 mt-0.5 line-clamp-2">{{ n.message }}</p>
                                <p class="text-xs text-gray-400 mt-1">{{ relativeTime(n.created_at) }}</p>
                            </div>
                        </button>
                    </template>
                </template>

            </div>
        </div>
    </Transition>
</template>

<style scoped>
/* fade + slide-down 150ms */
.notif-dropdown-enter-active {
    transition: opacity 0.15s ease, transform 0.15s ease;
}
.notif-dropdown-leave-active {
    transition: opacity 0.15s ease, transform 0.15s ease;
}
.notif-dropdown-enter-from {
    opacity: 0;
    transform: translateY(-8px);
}
.notif-dropdown-leave-to {
    opacity: 0;
    transform: translateY(-8px);
}
</style>
