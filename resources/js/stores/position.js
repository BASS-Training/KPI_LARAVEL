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

    return { positions, isLoading, fetchPositions, asOptions, byDepartment, findById };
});
