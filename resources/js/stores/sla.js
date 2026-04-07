import { defineStore } from 'pinia';
import { ref } from 'vue';
import api from '@/services/api';

export const useSlaStore = defineStore('sla', () => {
    const slas = ref([]);
    const isLoading = ref(false);

    async function fetchSla(params = {}) {
        isLoading.value = true;
        try {
            const { data: resp } = await api.get('/sla', { params: { per_page: 100, ...params } });
            slas.value = resp.data?.items || [];
        } finally {
            isLoading.value = false;
        }
    }

    async function createSla(payload) {
        const { data: resp } = await api.post('/sla', payload);
        return resp.data;
    }

    async function updateSla(id, payload) {
        const { data: resp } = await api.put(`/sla/${id}`, payload);
        return resp.data;
    }

    async function deleteSla(id) {
        await api.delete(`/sla/${id}`);
        slas.value = slas.value.filter((item) => item.id !== id);
    }

    return { slas, isLoading, fetchSla, createSla, updateSla, deleteSla };
});
