import { defineStore } from 'pinia';
import { ref } from 'vue';
import api from '@/services/api';

export const useSettingStore = defineStore('setting', () => {
    const settings = ref([]);
    const isLoading = ref(false);

    async function fetchSettings() {
        isLoading.value = true;
        try {
            const { data: response } = await api.get('/settings');
            settings.value = response.data || [];
        } finally {
            isLoading.value = false;
        }
    }

    async function updateSettings(payload) {
        const { data: response } = await api.put('/settings', { settings: payload });
        settings.value = response.data || [];
        return response.data;
    }

    return {
        settings,
        isLoading,
        fetchSettings,
        updateSettings,
    };
});
