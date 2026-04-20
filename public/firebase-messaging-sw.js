importScripts('https://www.gstatic.com/firebasejs/10.12.0/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/10.12.0/firebase-messaging-compat.js');

let initialized = false;

self.addEventListener('message', ({ data }) => {
    if (data?.type === 'FIREBASE_CONFIG' && !initialized) {
        try {
            firebase.initializeApp(data.config);
            firebase.messaging().onBackgroundMessage(({ notification, data: d }) => {
                self.registration.showNotification(notification?.title ?? 'KPI BASS', {
                    body: notification?.body ?? '',
                    icon: '/favicon.ico',
                    data: d ?? {},
                });
            });
            initialized = true;
        } catch (e) {
            console.error('[SW] Firebase init failed:', e);
        }
    }
});
