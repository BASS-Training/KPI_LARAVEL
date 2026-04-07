import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import api from '@/services/api';

export const useNotificationStore = defineStore('notification', () => {
    const notifications = ref([]);
    const isLoading = ref(false);

    const unreadCount = computed(() =>
        notifications.value.filter((n) => !n.is_read).length,
    );

    const recent = computed(() => notifications.value.slice(0, 5));

    async function fetchNotifications() {
        isLoading.value = true;
        try {
            const { data: resp } = await api.get('/notifications');
            notifications.value = resp.data?.items ?? [];
        } finally {
            isLoading.value = false;
        }
    }

    async function markRead(id) {
        await api.put(`/notifications/${id}/read`);
        const n = notifications.value.find((n) => n.id === id);
        if (n) n.is_read = true;
    }

    async function markAllRead() {
        await api.put('/notifications/read-all');
        notifications.value.forEach((n) => (n.is_read = true));
    }

    return {
        notifications,
        isLoading,
        unreadCount,
        recent,
        fetchNotifications,
        markRead,
        markAllRead,
    };
});
