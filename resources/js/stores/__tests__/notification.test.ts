/**
 * Property-based tests for NotificationStore using fast-check
 *
 * Validates: Requirements G1.1, G1.2, G1.3
 */

import { describe, it, beforeEach, vi } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';
import fc from 'fast-check';
import { useNotificationStore, type Notification, type NotificationType } from '../notification';

// Mock axios so API calls don't hit the network
vi.mock('axios', () => ({
    default: {
        get: vi.fn().mockResolvedValue({ data: { data: [], last_page: 1 } }),
        patch: vi.fn().mockResolvedValue({ data: { success: true } }),
        post: vi.fn().mockResolvedValue({ data: { success: true } }),
    },
}));

// Arbitrary for NotificationType
const notificationTypeArbitrary = fc.constantFrom<NotificationType>(
    'success',
    'warning',
    'danger',
    'info'
);

// Arbitrary for a single Notification object
const notificationArbitrary: fc.Arbitrary<Notification> = fc.record({
    id: fc.integer({ min: 1, max: 1_000_000 }),
    user_id: fc.integer({ min: 1, max: 1000 }),
    title: fc.string({ minLength: 1, maxLength: 100 }),
    message: fc.string({ minLength: 1, maxLength: 200 }),
    type: notificationTypeArbitrary,
    link: fc.oneof(fc.string({ minLength: 1, maxLength: 100 }), fc.constant(null)),
    is_read: fc.boolean(),
    created_at: fc.constant(new Date().toISOString()),
    updated_at: fc.constant(new Date().toISOString()),
});

describe('NotificationStore — property-based tests', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
    });

    /**
     * G1.1 — unreadCount selalu === notifications.filter(n => !n.is_read).length
     *
     * **Validates: Requirements G1.1**
     */
    it('G1.1: unreadCount is always consistent with notifications array', () => {
        fc.assert(
            fc.property(fc.array(notificationArbitrary), (notifs) => {
                const store = useNotificationStore();
                store.$patch({ notifications: notifs });
                return store.unreadCount === notifs.filter((n) => !n.is_read).length;
            })
        );
    });

    /**
     * G1.2 — markAllAsRead() selalu menghasilkan unreadCount === 0
     *
     * **Validates: Requirements G1.2**
     */
    it('G1.2: markAllAsRead always results in unreadCount === 0', async () => {
        await fc.assert(
            fc.asyncProperty(fc.array(notificationArbitrary, { minLength: 1 }), async (notifs) => {
                const store = useNotificationStore();
                store.$patch({ notifications: notifs });

                await store.markAllAsRead();

                return store.unreadCount === 0;
            })
        );
    });

    /**
     * G1.3 — addRealtime() selalu menambah item ke index 0
     *
     * **Validates: Requirements G1.3**
     */
    it('G1.3: addRealtime always prepends the notification to index 0', () => {
        fc.assert(
            fc.property(
                fc.array(notificationArbitrary),
                notificationArbitrary,
                (existingNotifs, newNotif) => {
                    const store = useNotificationStore();
                    store.$patch({ notifications: [...existingNotifs] });

                    const beforeLength = store.notifications.length;
                    store.addRealtime(newNotif);

                    return (
                        store.notifications.length === beforeLength + 1 &&
                        store.notifications[0].id === newNotif.id
                    );
                }
            )
        );
    });
});
