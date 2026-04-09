import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import api from '@/services/api';

export const usePositionStore = defineStore('position', () => {
    const positions = ref([]);
    const isLoading = ref(false);

    async function fetchPositions(params = {}) {
        isLoading.value = true;
        try {
            const { data } = await api.get('/positions', { params });
            positions.value = data.data ?? data;
        } finally {
            isLoading.value = false;
        }
    }

    async function createPosition(payload) {
        const { data: resp } = await api.post('/positions', payload);
        positions.value.push(resp.data);
        return resp.data;
    }

    async function updatePosition(id, payload) {
        const { data: resp } = await api.put(`/positions/${id}`, payload);
        const idx = positions.value.findIndex(p => p.id === id);
        if (idx !== -1) positions.value[idx] = resp.data;
        return resp.data;
    }

    async function deletePosition(id) {
        await api.delete(`/positions/${id}`);
        positions.value = positions.value.filter(p => p.id !== id);
    }

    const asOptions = computed(() =>
        positions.value.map(p => ({
            value: p.id,
            label: p.nama,
            department_id: p.department_id,
            level: p.level,
        }))
    );

    function byDepartment(deptId) {
        if (!deptId) return positions.value;
        return positions.value.filter(p => p.department_id === deptId);
    }

    function findById(id) {
        return positions.value.find(p => p.id === id) ?? null;
    }

    return {
        positions, isLoading,
        fetchPositions, createPosition, updatePosition, deletePosition,
        asOptions, byDepartment, findById,
    };
});
