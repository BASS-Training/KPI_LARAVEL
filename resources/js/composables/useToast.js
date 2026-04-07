import { inject } from 'vue';

/**
 * Composable untuk menampilkan toast notification.
 * Harus digunakan di dalam komponen yang dibungkus <Toaster>.
 */
export function useToast() {
    const toast = inject('toast', null);

    function show(message, variant = 'success') {
        if (!toast) {
            console.warn('[useToast] Toaster tidak ditemukan di tree komponen.');
            return;
        }
        const duration = variant === 'error' ? 5000 : 3000;
        toast({ message, variant, duration });
    }

    return {
        success: (msg) => show(msg, 'success'),
        error: (msg) => show(msg, 'error'),
        warning: (msg) => show(msg, 'warning'),
        info: (msg) => show(msg, 'info'),
    };
}
