import { defineStore } from 'pinia';
import { ref } from 'vue';
import api from '@/services/api';

export const useEmployeeStore = defineStore('employee', () => {
    const employees = ref([]);
    const total = ref(0);
    const isLoading = ref(false);

    async function fetchEmployees(params = {}) {
        isLoading.value = true;
        employees.value = [];
        try {
            // Response: { success, data: { items: [...], pagination: {...} }, message }
            const { data: resp } = await api.get('/employees', { params: { per_page: 100, ...params } });
            employees.value = resp.data?.items || [];
            total.value = resp.data?.pagination?.total ?? employees.value.length;
        } finally {
            isLoading.value = false;
        }
    }

    async function createEmployee(payload) {
        const { data: resp } = await api.post('/employees', payload);
        return resp.data;
    }

    async function updateEmployee(id, payload) {
        const { data: resp } = await api.put(`/employees/${id}`, payload);
        return resp.data;
    }

    async function deleteEmployee(id) {
        await api.delete(`/employees/${id}`);
        employees.value = employees.value.filter((employee) => employee.id !== id);
        total.value = Math.max(0, total.value - 1);
    }

    return { employees, total, isLoading, fetchEmployees, createEmployee, updateEmployee, deleteEmployee };
});
