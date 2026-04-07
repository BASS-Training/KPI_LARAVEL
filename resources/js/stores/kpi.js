import { defineStore } from 'pinia';
import { ref } from 'vue';
import api from '@/services/api';

// Konversi string predikat ke { label, color } untuk RankRow + Badge
function resolvePredikat(str) {
    const map = {
        'Baik Sekali': { label: 'Baik Sekali', color: 'success' },
        'Baik': { label: 'Baik', color: 'success' },
        'Cukup': { label: 'Cukup', color: 'warning' },
        'Kurang': { label: 'Kurang', color: 'warning' },
        'Buruk': { label: 'Buruk', color: 'danger' },
    };
    return map[str] || { label: str, color: 'default' };
}

export const useKpiStore = defineStore('kpi', () => {
    const myKpi = ref(null);
    const userKpi = ref({});
    const ranking = ref([]);
    const isLoading = ref(false);

    async function fetchMyKpi() {
        isLoading.value = true;
        myKpi.value = null;
        try {
            // Response: { success, data: { user, total, predikat, components }, message }
            const { data: resp } = await api.get('/kpi/me');
            const raw = resp.data;

            // Normalisasi agar komponen dashboard bisa pakai field yang konsisten
            myKpi.value = {
                total_score: raw.total,
                predikat: raw.predikat,
                task_count: raw.components.reduce((sum, c) => sum + (c.jumlah_task || 0), 0),
                // Deteksi flag dari tipe komponen (skor 0 berarti ada insiden)
                delay_count: raw.components.filter((c) => c.tipe === 'zero_delay' && c.skor === 0).length,
                error_count: raw.components.filter((c) => c.tipe === 'zero_error' && c.skor === 0).length,
                complaint_count: raw.components.filter((c) => c.tipe === 'zero_complaint' && c.skor === 0).length,
                components: raw.components.map((c) => ({
                    id: c.id,
                    name: c.objectives,
                    score: c.skor,
                })),
            };
        } finally {
            isLoading.value = false;
        }
    }

    async function fetchUserKpi(userId) {
        const { data: resp } = await api.get(`/kpi/${userId}`);
        userKpi.value[userId] = resp.data;
        return resp.data;
    }

    async function fetchRanking() {
        isLoading.value = true;
        ranking.value = [];
        try {
            // Response: { success, data: [{user, total, predikat, components},...], message }
            const { data: resp } = await api.get('/kpi/ranking');
            ranking.value = (resp.data || []).map((item, index) => ({
                rank: index + 1,
                user_id: item.user?.id,
                name: item.user?.nama,
                position: item.user?.jabatan,
                kpi_score: item.total,
                predikat: resolvePredikat(item.predikat),
            }));
        } finally {
            isLoading.value = false;
        }
    }

    return { myKpi, userKpi, ranking, isLoading, fetchMyKpi, fetchUserKpi, fetchRanking };
});
