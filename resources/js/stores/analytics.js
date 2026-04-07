import { defineStore } from 'pinia';
import { ref, reactive } from 'vue';
import api from '@/services/api';

export const useAnalyticsStore = defineStore('analytics', () => {
    const trend = ref(null);
    const perDivision = ref(null);
    const distribution = ref(null);
    const overview = ref(null);

    const isLoadingTrend = ref(false);
    const isLoadingDivision = ref(false);
    const isLoadingDistribution = ref(false);
    const isLoadingOverview = ref(false);

    const filters = reactive({
        tahun: new Date().getFullYear(),
        bulan: null,
        division_id: null,
    });

    async function fetchTrend(params = {}) {
        isLoadingTrend.value = true;
        try {
            const { data: resp } = await api.get('/analytics/trend', {
                params: { tahun: filters.tahun, division_id: filters.division_id || undefined, ...params },
            });
            trend.value = resp.data;
        } finally {
            isLoadingTrend.value = false;
        }
    }

    async function fetchPerDivision(params = {}) {
        isLoadingDivision.value = true;
        try {
            const { data: resp } = await api.get('/analytics/per-division', {
                params: { tahun: filters.tahun, bulan: filters.bulan || undefined, ...params },
            });
            perDivision.value = resp.data;
        } finally {
            isLoadingDivision.value = false;
        }
    }

    async function fetchDistribution(params = {}) {
        isLoadingDistribution.value = true;
        try {
            const { data: resp } = await api.get('/analytics/distribution', {
                params: {
                    tahun: filters.tahun,
                    bulan: filters.bulan || undefined,
                    division_id: filters.division_id || undefined,
                    ...params,
                },
            });
            distribution.value = resp.data;
        } finally {
            isLoadingDistribution.value = false;
        }
    }

    async function fetchOverview(params = {}) {
        isLoadingOverview.value = true;
        try {
            const { data: resp } = await api.get('/analytics/overview', {
                params: {
                    tahun: filters.tahun,
                    bulan: filters.bulan || new Date().getMonth() + 1,
                    ...params,
                },
            });
            overview.value = resp.data;
        } finally {
            isLoadingOverview.value = false;
        }
    }

    async function fetchAll() {
        await Promise.all([
            fetchTrend(),
            fetchPerDivision(),
            fetchDistribution(),
            fetchOverview(),
        ]);
    }

    function setFilter(key, value) {
        filters[key] = value;
    }

    return {
        trend,
        perDivision,
        distribution,
        overview,
        filters,
        isLoadingTrend,
        isLoadingDivision,
        isLoadingDistribution,
        isLoadingOverview,
        fetchTrend,
        fetchPerDivision,
        fetchDistribution,
        fetchOverview,
        fetchAll,
        setFilter,
    };
});
