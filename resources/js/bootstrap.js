import axios from 'axios';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// ── Laravel Echo (Pusher / Reverb) ──────────────────────────────────────────
// Initialise Echo so the dashboard can subscribe to real-time KPI updates.
// Falls back silently to polling if the keys are not configured in .env.
window.Pusher = Pusher;

try {
    const broadcaster = import.meta.env.VITE_BROADCAST_DRIVER ?? 'pusher';
    const appKey = import.meta.env.VITE_PUSHER_APP_KEY ?? '';

    if (appKey) {
        window.Echo = new Echo({
            broadcaster,
            key: appKey,
            cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'mt1',
            wsHost: import.meta.env.VITE_REVERB_HOST ?? window.location.hostname,
            wsPort: Number(import.meta.env.VITE_REVERB_PORT ?? 8080),
            wssPort: Number(import.meta.env.VITE_REVERB_PORT ?? 443),
            forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
            disableStats: true,
            enabledTransports: ['ws', 'wss'],
            authEndpoint: '/broadcasting/auth',
            auth: {
                headers: {
                    Authorization: `Bearer ${localStorage.getItem('auth_token') ?? ''}`,
                },
            },
        });
    }
} catch {
    // Echo configuration is missing — real-time falls back to polling only.
}
