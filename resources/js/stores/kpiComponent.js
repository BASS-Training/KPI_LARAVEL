import { defineStore } from 'pinia';
import { ref } from 'vue';
import api from '@/services/api';

export const useKpiComponentStore = defineStore('kpi-component', () => {
    const components = ref([]);
    const isLoading = ref(false);

    async function fetchComponents(params = {}) {
        isLoading.value = true;
        try {
            const { data: resp } = await api.get('/kpi-components', { params: { per_page: 100, ...params } });
            components.value = resp.data?.items || [];
        } finally {
            isLoading.value = false;
        }
    }

    async function createComponent(payload) {
        const { data: resp } = await api.post('/kpi-components', payload);
        return resp.data;
    }

    async function updateComponent(id, payload) {
        const { data: resp } = await api.put(`/kpi-components/${id}`, payload);
        return resp.data;
    }

    async function deleteComponent(id) {
        await api.delete(`/kpi-components/${id}`);
        components.value = components.value.filter((item) => item.id !== id);
    }

    return { components, isLoading, fetchComponents, createComponent, updateComponent, deleteComponent };
});
