import { initializeApp, getApps, type FirebaseApp } from 'firebase/app';
import { getMessaging, getToken, onMessage, type Messaging } from 'firebase/messaging';
import axios from 'axios';
import { useNotificationStore } from '../stores/notification';
import { useToast } from './useToast';
import type { Notification } from '../stores/notification';

const firebaseConfig = {
    apiKey: import.meta.env.VITE_FIREBASE_API_KEY,
    authDomain: import.meta.env.VITE_FIREBASE_AUTH_DOMAIN,
    projectId: import.meta.env.VITE_FIREBASE_PROJECT_ID,
    messagingSenderId: import.meta.env.VITE_FIREBASE_MESSAGING_SENDER_ID,
    appId: import.meta.env.VITE_FIREBASE_APP_ID,
};

// Guard: skip FCM entirely if not configured
const isFirebaseConfigured = (): boolean =>
    !!(firebaseConfig.apiKey && firebaseConfig.projectId && firebaseConfig.appId);

let app: FirebaseApp | null = null;
let messaging: Messaging | null = null;
let handlerRegistered = false;

function getFirebaseApp(): FirebaseApp {
    if (!app) {
        app = getApps().length > 0 ? getApps()[0] : initializeApp(firebaseConfig);
    }
    return app;
}

function getFirebaseMessaging(): Messaging {
    if (!messaging) {
        messaging = getMessaging(getFirebaseApp());
    }
    return messaging;
}

export function useFCM() {
    const notifStore = useNotificationStore();
    const toast = useToast();

    async function requestPermissionAndRegister(): Promise<void> {
        try {
            const permission = await Notification.requestPermission();
            if (permission !== 'granted') {
                console.warn('Notification permission not granted.');
                return;
            }

            const vapidKey = import.meta.env.VITE_FIREBASE_VAPID_KEY;
            const token = await getToken(getFirebaseMessaging(), { vapidKey });

            if (token) {
                await notifStore.registerDevice(token);
            } else {
                console.warn('No FCM token received.');
            }
        } catch (error) {
            console.error('Failed to request permission or register device:', error);
        }
    }

    function setupForegroundHandler(): void {
        if (!handlerRegistered) {
            onMessage(getFirebaseMessaging(), (payload) => {
                if (document.visibilityState !== 'visible') return;

                const title = payload.notification?.title ?? 'Notifikasi';
                const body = payload.notification?.body ?? '';

                toast.show(`${title}: ${body}`, 'info');

                const data = payload.data ?? {};
                const now = new Date().toISOString();
                const notification: Notification = {
                    id: Number(data['notification_id']) || Date.now(),
                    user_id: 0,
                    title,
                    message: body,
                    type: (data['type'] as Notification['type']) ?? 'info',
                    link: data['link'] || null,
                    is_read: false,
                    created_at: now,
                    updated_at: now,
                };

                notifStore.addRealtime(notification);
            });
            handlerRegistered = true;
        }
    }

    async function registerServiceWorker(): Promise<void> {
        if ('serviceWorker' in navigator) {
            try {
                await navigator.serviceWorker.register('/firebase-messaging-sw.js');
            } catch (error) {
                console.error('Service worker registration failed:', error);
            }
        }
    }

    async function unregisterDevice(): Promise<void> {
        try {
            await axios.delete('/api/devices/token');
        } catch (error) {
            console.error('Failed to unregister device:', error);
        }
    }

    async function init(): Promise<void> {
        if (!isFirebaseConfigured()) return;
        await registerServiceWorker();
        await requestPermissionAndRegister();
        setupForegroundHandler();
    }

    return {
        init,
        requestPermissionAndRegister,
        setupForegroundHandler,
        unregisterDevice,
    };
}
