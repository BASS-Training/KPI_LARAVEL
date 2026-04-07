import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import api from '@/services/api';

export const useDepartmentStore = defineStore('department', () => {
    const departments = ref([]);
    const isLoading   = ref(false);

    async function fetchDepartments(params = {}) {
        isLoading.value = true;
        try {
            const { data: resp } = await api.get('/departments', { params });
            departments.value = resp.data ?? [];
        } finally {
            isLoading.value = false;
        }
    }

    async function createDepartment(payload) {
        const { data: resp } = await api.post('/departments', payload);
        departments.value.push(resp.data);
        return resp.data;
    }

    async function updateDepartment(id, payload) {
        const { data: resp } = await api.put(`/departments/${id}`, payload);
        const idx = departments.value.findIndex(d => d.id === id);
        if (idx !== -1) departments.value[idx] = resp.data;
        return resp.data;
    }

    async function deleteDepartment(id) {
        await api.delete(`/departments/${id}`);
        departments.value = departments.value.filter(d => d.id !== id);
    }

    const asOptions = computed(() =>
        departments.value.map(d => ({ value: d.id, label: d.nama, division_id: d.division_id }))
    );

    function byDivision(divisionId) {
        if (!divisionId) return departments.value;
        return departments.value.filter(d => d.division_id === divisionId);
    }

    function findById(id) {
        return departments.value.find(d => d.id === id) ?? null;
    }

    return {
        departments, isLoading,
        fetchDepartments, createDepartment, updateDepartment, deleteDepartment,
        asOptions, byDivision, findById,
    };
});
