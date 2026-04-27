import { defineStore } from 'pinia';
import { ref } from 'vue';
import api from '@/services/api';

export const useKpiComponentStore = defineStore('kpi-component', () => {
    const components = ref([]);
    const isLoading = ref(false);
    const pagination = ref({ total: 0, current_page: 1, last_page: 1 });

    async function fetchComponents(params = {}) {
        isLoading.value = true;
        try {
            const { data: resp } = await api.get('/kpi-components', { params });
            components.value = resp.data ?? [];
            if (resp.meta) {
                pagination.value = {
                    total: resp.meta.total ?? 0,
                    current_page: resp.meta.current_page ?? 1,
                    last_page: resp.meta.last_page ?? 1,
                };
            }
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
        components.value = components.value.filter((c) => c.id !== id);
    }

    return {
        components,
        isLoading,
        pagination,
        fetchComponents,
        createComponent,
        updateComponent,
        deleteComponent,
    };
});
