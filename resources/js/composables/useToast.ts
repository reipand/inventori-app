import { ref } from 'vue';

export type ToastType = 'success' | 'error' | 'warning' | 'info';

export interface Toast {
    id: number;
    message: string;
    type: ToastType;
}

const toasts = ref<Toast[]>([]);
let counter = 0;

export function useToast() {
    function show(message: string, type: ToastType = 'info', duration = 3500) {
        const id = ++counter;
        toasts.value.push({ id, message, type });
        setTimeout(() => dismiss(id), duration);
    }

    function dismiss(id: number) {
        toasts.value = toasts.value.filter((t) => t.id !== id);
    }

    return {
        toasts,
        show,
        dismiss,
        success: (msg: string) => show(msg, 'success'),
        error: (msg: string) => show(msg, 'error'),
        warning: (msg: string) => show(msg, 'warning'),
        info: (msg: string) => show(msg, 'info'),
    };
}
