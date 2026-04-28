import api from '@/services/api';

function compactParams(params) {
    return Object.fromEntries(
        Object.entries(params).filter(([, value]) => value !== '' && value !== null && value !== undefined)
    );
}

export const kpiReportService = {
    async listReports(params = {}) {
        const { data } = await api.get('/kpi-reports', {
            params: compactParams(params),
        });

        return data.data;
    },

    async createReport(payload) {
        const { data } = await api.post('/kpi-reports', payload);
        return data.data;
    },

    async updateReport(id, payload) {
        const { data } = await api.put(`/kpi-reports/${id}`, payload);
        return data.data;
    },

    async deleteReport(id) {
        await api.delete(`/kpi-reports/${id}`);
        return id;
    },

    async reviewReport(id, payload) {
        const { data } = await api.patch(`/kpi-reports/${id}/review`, payload);
        return data.data;
    },

    async uploadEvidence(reportId, file) {
        const form = new FormData();
        form.append('file', file);

        const { data } = await api.post(`/kpi-reports/${reportId}/evidence`, form, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });

        return data.data;
    },

    async listIndicators() {
        const { data } = await api.get('/kpi-indicators', {
            params: { per_page: 200 },
        });

        return data.data?.items ?? [];
    },

    async listEmployees() {
        const { data } = await api.get('/employees', {
            params: { per_page: 100 },
        });

        return data.data?.items ?? [];
    },
};
