import { defineStore } from 'pinia';
import { ref, reactive } from 'vue';
import api from '@/services/api';

export const useKpiIndicatorStore = defineStore('kpi-indicator', () => {
    const indicators = ref([]);
    const meta = reactive({ departments: [], roles: [], formula_types: [] });
    const isLoading = ref(false);
    const isMetaLoading = ref(false);

    async function fetchIndicators(params = {}) {
        isLoading.value = true;
        try {
            const { data: resp } = await api.get('/kpi-indicators', { params });
            indicators.value = resp.data?.items || [];
        } finally {
            isLoading.value = false;
        }
    }

    async function fetchMeta() {
        isMetaLoading.value = true;
        try {
            const { data: resp } = await api.get('/kpi-indicators/meta');
            meta.departments = resp.data?.departments || [];
            meta.roles = resp.data?.roles || [];
            meta.formula_types = resp.data?.formula_types || [];
        } finally {
            isMetaLoading.value = false;
        }
    }

    async function createIndicator(payload) {
        const { data: resp } = await api.post('/kpi-indicators', payload);
        return resp.data;
    }

    async function updateIndicator(id, payload) {
        const { data: resp } = await api.put(`/kpi-indicators/${id}`, payload);
        return resp.data;
    }

    async function deleteIndicator(id) {
        await api.delete(`/kpi-indicators/${id}`);
        indicators.value = indicators.value.filter((i) => i.id !== id);
    }

    return {
        indicators,
        meta,
        isLoading,
        isMetaLoading,
        fetchIndicators,
        fetchMeta,
        createIndicator,
        updateIndicator,
        deleteIndicator,
    };
});
