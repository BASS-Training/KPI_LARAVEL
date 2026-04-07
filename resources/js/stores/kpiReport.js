import { defineStore } from 'pinia';
import { ref, reactive } from 'vue';
import api from '@/services/api';

export const useKpiReportStore = defineStore('kpiReport', () => {
    const reports = ref([]);
    const isLoading = ref(false);
    const isSaving = ref(false);

    const pagination = reactive({
        currentPage: 1,
        lastPage: 1,
        perPage: 20,
        total: 0,
    });

    const filters = reactive({
        bulan: new Date().getMonth() + 1,
        tahun: new Date().getFullYear(),
        user_id: null,
        kpi_component_id: null,
        status: null,
        per_page: 20,
    });

    async function fetchReports(params = {}) {
        isLoading.value = true;
        try {
            const merged = { ...filters, ...params };
            // Remove null/empty
            Object.keys(merged).forEach((k) => {
                if (merged[k] === null || merged[k] === '') delete merged[k];
            });

            const { data: resp } = await api.get('/kpi-reports', { params: merged });
            reports.value = resp.data?.items ?? [];
            const p = resp.data?.pagination ?? {};
            pagination.currentPage = p.current_page ?? 1;
            pagination.lastPage = p.last_page ?? 1;
            pagination.perPage = p.per_page ?? 20;
            pagination.total = p.total ?? 0;
        } finally {
            isLoading.value = false;
        }
    }

    async function createReport(payload) {
        isSaving.value = true;
        try {
            const { data: resp } = await api.post('/kpi-reports', payload);
            reports.value.unshift(resp.data);
            return resp.data;
        } finally {
            isSaving.value = false;
        }
    }

    async function updateReport(id, payload) {
        isSaving.value = true;
        try {
            const { data: resp } = await api.put(`/kpi-reports/${id}`, payload);
            const idx = reports.value.findIndex((r) => r.id === id);
            if (idx !== -1) reports.value[idx] = resp.data;
            return resp.data;
        } finally {
            isSaving.value = false;
        }
    }

    async function deleteReport(id) {
        await api.delete(`/kpi-reports/${id}`);
        reports.value = reports.value.filter((r) => r.id !== id);
    }

    async function uploadEvidence(reportId, file) {
        const form = new FormData();
        form.append('file', file);
        const { data: resp } = await api.post(`/kpi-reports/${reportId}/evidence`, form, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });
        const idx = reports.value.findIndex((r) => r.id === reportId);
        if (idx !== -1) {
            reports.value[idx] = { ...reports.value[idx], file_evidence_url: resp.data.file_evidence_url };
        }
        return resp.data;
    }

    function setFilter(key, value) {
        filters[key] = value;
    }

    return {
        reports,
        isLoading,
        isSaving,
        pagination,
        filters,
        fetchReports,
        createReport,
        updateReport,
        deleteReport,
        uploadEvidence,
        setFilter,
    };
});
