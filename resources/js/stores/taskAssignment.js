import { defineStore } from 'pinia';
import { ref, reactive } from 'vue';
import api from '@/services/api';

export const useTaskAssignmentStore = defineStore('task-assignment', () => {
    // HR view — all manually assigned tasks
    const assignedTasks = ref([]);
    // Pegawai view — my tasks
    const myTasks = ref([]);
    const pagination = reactive({ currentPage: 1, lastPage: 1, total: 0, perPage: 15 });
    const isLoading = ref(false);

    const filters = reactive({
        status: '',
        bulan: new Date().getMonth() + 1,
        tahun: new Date().getFullYear(),
        assigned_to: '',
    });

    /** HR: fetch all manually assigned tasks */
    async function fetchAssignedTasks(params = {}) {
        isLoading.value = true;
        try {
            const { data: resp } = await api.get('/tasks', {
                params: {
                    type: 'manual_assignment',
                    ...filters,
                    ...params,
                    page: pagination.currentPage,
                },
            });
            assignedTasks.value = resp.data?.items || [];
            const pg = resp.data?.pagination || {};
            pagination.currentPage = pg.current_page ?? 1;
            pagination.lastPage    = pg.last_page    ?? 1;
            pagination.total       = pg.total        ?? 0;
            pagination.perPage     = pg.per_page     ?? 15;
        } finally {
            isLoading.value = false;
        }
    }

    /** Pegawai: fetch tasks assigned to me */
    async function fetchMyTasks(params = {}) {
        isLoading.value = true;
        try {
            const { data: resp } = await api.get('/my-tasks', { params });
            myTasks.value = resp.data?.items || [];
        } finally {
            isLoading.value = false;
        }
    }

    async function createAssignment(payload) {
        const { data: resp } = await api.post('/tasks', {
            ...payload,
            task_type: 'manual_assignment',
        });
        return resp.data;
    }

    async function updateAssignment(id, payload) {
        const { data: resp } = await api.put(`/tasks/${id}`, payload);
        return resp.data;
    }

    async function deleteAssignment(id) {
        await api.delete(`/tasks/${id}`);
        assignedTasks.value = assignedTasks.value.filter((t) => t.id !== id);
    }

    async function updateTaskStatus(id, payload) {
        const { data: resp } = await api.put(`/tasks/${id}/update-status`, payload);
        return resp.data;
    }

    function setPage(page) {
        pagination.currentPage = page;
    }

    return {
        assignedTasks,
        myTasks,
        pagination,
        isLoading,
        filters,
        fetchAssignedTasks,
        fetchMyTasks,
        createAssignment,
        updateAssignment,
        deleteAssignment,
        updateTaskStatus,
        setPage,
    };
});
