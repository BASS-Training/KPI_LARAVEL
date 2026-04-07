import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import api from '@/services/api';

export const useDivisionStore = defineStore('division', () => {
    const divisions = ref([]);
    const isLoading = ref(false);

    async function fetchDivisions(params = {}) {
        isLoading.value = true;
        try {
            const { data: resp } = await api.get('/divisions', { params });
            // controller returns success({ data: [...] }) → resp.data is the array
            divisions.value = resp.data ?? [];
        } finally {
            isLoading.value = false;
        }
    }

    async function createDivision(payload) {
        const { data: resp } = await api.post('/divisions', payload);
        const division = resp.data;
        divisions.value.push(division);
        return division;
    }

    async function updateDivision(id, payload) {
        const { data: resp } = await api.put(`/divisions/${id}`, payload);
        const division = resp.data;
        const idx = divisions.value.findIndex(d => d.id === id);
        if (idx !== -1) divisions.value[idx] = division;
        return division;
    }

    async function deleteDivision(id) {
        await api.delete(`/divisions/${id}`);
        divisions.value = divisions.value.filter(d => d.id !== id);
    }

    const activeDivisions = computed(() => divisions.value.filter(d => d.is_active));

    const asOptions = computed(() =>
        divisions.value.map(d => ({ value: d.id, label: `${d.nama} (${d.kode})` }))
    );

    return {
        divisions, isLoading,
        fetchDivisions, createDivision, updateDivision, deleteDivision,
        activeDivisions, asOptions,
    };
});
