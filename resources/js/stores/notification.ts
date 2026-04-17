import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import axios from 'axios';

export type NotificationType = 'success' | 'warning' | 'danger' | 'info';

export interface Notification {
    id: number;
    user_id: number;
    title: string;
    message: string;
    type: NotificationType;
    link: string | null;
    is_read: boolean;
    created_at: string;
    updated_at: string;
}

// Backward compatibility interface
export interface LowStockAlert {
    productId: string;
    productName: string;
    currentStock: number;
    minStock: number;
}

export const useNotificationStore = defineStore('notification', () => {
    const notifications = ref<Notification[]>([]);
    const loading = ref(false);
    const error = ref<string | null>(null);
    const initialized = ref(false);
    const hasMore = ref(true);
    const page = ref(1);

    // Computed: unreadCount selalu konsisten dengan array
    const unreadCount = computed(() =>
        notifications.value.filter((n) => !n.is_read).length
    );

    // Backward compatibility: lowStockAlerts computed
    const lowStockAlerts = computed<LowStockAlert[]>(() =>
        notifications.value
            .filter((n) => n.type === 'warning')
            .map((n) => {
                // Parse productId, productName, currentStock, minStock dari message
                // Format message: "productId|productName|currentStock|minStock"
                const parts = n.message.split('|');
                return {
                    productId: parts[0] ?? String(n.id),
                    productName: parts[1] ?? n.title,
                    currentStock: Number(parts[2] ?? 0),
                    minStock: Number(parts[3] ?? 0),
                };
            })
    );

    // Fetch notifications dari API dengan pagination
    async function fetchNotifications(pageNum?: number): Promise<void> {
        loading.value = true;
        try {
            const currentPage = pageNum ?? 1;
            const response = await axios.get('/api/notifications', {
                params: { page: currentPage },
            });

            const data = response.data;
            const items: Notification[] = data.data ?? data;

            if (currentPage === 1) {
                notifications.value = items;
            } else {
                notifications.value = [...notifications.value, ...items];
            }

            // Update pagination state
            if (data.last_page !== undefined) {
                hasMore.value = currentPage < data.last_page;
            } else {
                hasMore.value = items.length > 0;
            }
            page.value = currentPage;
            error.value = null;
            initialized.value = true;
        } catch (err) {
            console.error('Failed to fetch notifications:', err);
            error.value = 'Gagal memuat notifikasi';
        } finally {
            loading.value = false;
        }
    }

    // Mark single notification as read
    async function markAsRead(id: number): Promise<void> {
        try {
            await axios.patch(`/api/notifications/${id}/read`);
            const item = notifications.value.find((n) => n.id === id);
            if (item) {
                item.is_read = true;
            }
        } catch (error) {
            console.error('Failed to mark notification as read:', error);
        }
    }

    // Mark all notifications as read
    async function markAllAsRead(): Promise<void> {
        try {
            await axios.patch('/api/notifications/read-all');
            notifications.value.forEach((n) => {
                n.is_read = true;
            });
        } catch (error) {
            console.error('Failed to mark all notifications as read:', error);
        }
    }

    // Add realtime notification from FCM foreground (prepend to index 0)
    function addRealtime(notification: Notification): void {
        notifications.value.unshift(notification);
    }

    // Register FCM device token
    async function registerDevice(token: string): Promise<void> {
        try {
            await axios.post('/api/devices/register', { fcm_token: token });
        } catch (error) {
            console.error('Failed to register device:', error);
        }
    }

    // Backward compatibility: addLowStockAlert
    function addLowStockAlert(alert: LowStockAlert): void {
        // Avoid duplicates by productId
        const exists = notifications.value.some(
            (n) => n.type === 'warning' && n.message.startsWith(alert.productId + '|')
        );
        if (exists) return;

        const now = new Date().toISOString();
        const notification: Notification = {
            id: Date.now(),
            user_id: 0,
            title: `Stok Rendah: ${alert.productName}`,
            message: `${alert.productId}|${alert.productName}|${alert.currentStock}|${alert.minStock}`,
            type: 'warning',
            link: null,
            is_read: false,
            created_at: now,
            updated_at: now,
        };
        notifications.value.unshift(notification);
    }

    // Dismiss notification by id (general purpose)
    function dismissNotification(id: number): void {
        notifications.value = notifications.value.filter((n) => n.id !== id);
    }

    // Backward compatibility: dismissAlert by productId
    function dismissAlert(productId: string): void {
        notifications.value = notifications.value.filter(
            (n) => !(n.type === 'warning' && n.message.startsWith(productId + '|'))
        );
    }

    // Backward compatibility: clearAll
    function clearAll(): void {
        notifications.value = [];
    }

    // Reset error state
    function clearError(): void {
        error.value = null;
    }

    // Add transaction alert (success or info type)
    function addTransactionAlert(
        type: 'transaction_in' | 'transaction_out',
        payload: { productName: string; quantity: number; currentStock: number }
    ): void {
        const now = new Date().toISOString();
        const isIn = type === 'transaction_in';
        const notification: Notification = {
            id: Date.now(),
            user_id: 0,
            title: isIn ? 'Transaksi Masuk Berhasil' : 'Transaksi Keluar Berhasil',
            message: `${payload.productName}: ${payload.quantity} unit (stok: ${payload.currentStock})`,
            type: isIn ? 'success' : 'info',
            link: null,
            is_read: false,
            created_at: now,
            updated_at: now,
        };
        notifications.value.unshift(notification);
    }

    return {
        // State
        notifications,
        loading,
        error,
        initialized,
        hasMore,
        page,
        // Computed
        unreadCount,
        lowStockAlerts,
        // Actions
        fetchNotifications,
        markAsRead,
        markAllAsRead,
        addRealtime,
        registerDevice,
        clearError,
        // Backward compatibility
        addLowStockAlert,
        dismissAlert,
        dismissNotification,
        clearAll,
        addTransactionAlert,
    };
});
