import { onForegroundMessage, requestFcmToken } from '@/services/firebase';
import { useNotificationStore } from '@/stores/notification';

let echoChannelName = null;
let unsubscribeFcm = null;

export function useNotification() {
    const store = useNotificationStore();

    async function init(userId) {
        if (!userId) return;

        // Real-time in-app notifications via Laravel Echo
        if (window.Echo) {
            echoChannelName = `kpi.user.${userId}`;
            window.Echo.private(echoChannelName).listen('.notification.new', (data) => {
                store.addRealtime(data);
            });
        }

        // Native push notifications via FCM (optional — only if browser permits)
        if ('Notification' in window && Notification.permission !== 'denied') {
            const permission = await Notification.requestPermission();
            if (permission === 'granted') {
                await requestFcmToken();
                unsubscribeFcm = onForegroundMessage(({ notification }) => {
                    new Notification(notification?.title ?? 'KPI BASS', {
                        body: notification?.body ?? '',
                        icon: '/favicon.ico',
                    });
                });
            }
        }
    }

    function cleanup() {
        if (echoChannelName && window.Echo) {
            window.Echo.leave(echoChannelName);
            echoChannelName = null;
        }
        if (unsubscribeFcm) {
            unsubscribeFcm();
            unsubscribeFcm = null;
        }
    }

    return { init, cleanup };
}
