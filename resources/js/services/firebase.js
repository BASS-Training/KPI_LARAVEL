import { initializeApp } from 'firebase/app';
import { getMessaging, getToken, onMessage } from 'firebase/messaging';
import api from '@/services/api';

const firebaseConfig = {
    apiKey:            import.meta.env.VITE_FIREBASE_API_KEY,
    authDomain:        import.meta.env.VITE_FIREBASE_AUTH_DOMAIN,
    projectId:         import.meta.env.VITE_FIREBASE_PROJECT_ID,
    messagingSenderId: import.meta.env.VITE_FIREBASE_MESSAGING_SENDER_ID,
    appId:             import.meta.env.VITE_FIREBASE_APP_ID,
};

const isConfigured = Boolean(firebaseConfig.apiKey && firebaseConfig.projectId);

let _messaging = null;

function getFirebaseMessaging() {
    if (!isConfigured) return null;
    if (!_messaging) {
        const app = initializeApp(firebaseConfig);
        _messaging = getMessaging(app);
    }
    return _messaging;
}

export async function requestFcmToken() {
    const messaging = getFirebaseMessaging();
    if (!messaging || !import.meta.env.VITE_FIREBASE_VAPID_KEY) return null;

    try {
        const swReg = await navigator.serviceWorker.register('/firebase-messaging-sw.js');

        // Send config to SW for background message handling
        const sw = swReg.installing ?? swReg.waiting ?? swReg.active;
        sw?.postMessage({ type: 'FIREBASE_CONFIG', config: firebaseConfig });

        const token = await getToken(messaging, {
            vapidKey: import.meta.env.VITE_FIREBASE_VAPID_KEY,
            serviceWorkerRegistration: swReg,
        });

        if (token) {
            await api.post('/fcm/token', {
                token,
                device_type: /Mobi|Android/i.test(navigator.userAgent) ? 'mobile' : 'web',
            });
        }

        return token;
    } catch (err) {
        console.warn('[FCM] token request failed:', err.message);
        return null;
    }
}

export function onForegroundMessage(handler) {
    const messaging = getFirebaseMessaging();
    if (!messaging) return () => {};
    return onMessage(messaging, handler);
}
