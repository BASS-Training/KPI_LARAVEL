<script setup>
import { computed, onMounted, reactive, watch } from 'vue';
import AppLayout from '@/components/layout/AppLayout.vue';
import Button from '@/components/ui/Button.vue';
import Input from '@/components/ui/Input.vue';
import Select from '@/components/ui/Select.vue';
import PageHeader from '@/components/shared/PageHeader.vue';
import FilterPanel from '@/components/shared/FilterPanel.vue';
import { RefreshCw } from 'lucide-vue-next';
import { useToast } from '@/composables/useToast';
import { useAutoRefresh, formatTime } from '@/composables/useAutoRefresh';
import { monthOptions, reportStatusOptions } from '@/modules/kpi-reports/constants';
import KpiEvidenceDialog from '@/modules/kpi-reports/components/KpiEvidenceDialog.vue';
import KpiReportDetailDialog from '@/modules/kpi-reports/components/KpiReportDetailDialog.vue';
import KpiReportEmptyState from '@/modules/kpi-reports/components/KpiReportEmptyState.vue';
import KpiReportList from '@/modules/kpi-reports/components/KpiReportList.vue';
import KpiReportPagination from '@/modules/kpi-reports/components/KpiReportPagination.vue';
import KpiReportReviewDialog from '@/modules/kpi-reports/components/KpiReportReviewDialog.vue';
import KpiReportSkeletonList from '@/modules/kpi-reports/components/KpiReportSkeletonList.vue';
import KpiReportSummaryCards from '@/modules/kpi-reports/components/KpiReportSummaryCards.vue';
import { useKpiReportStore } from '@/stores/kpiReport';

const toast = useToast();
const store = useKpiReportStore();

const ui = reactive({
    search: '',
    status: 'submitted',
    user_id: '',
    bulan: '',
    tahun: new Date().getFullYear(),
});

const dialog = reactive({
    detailOpen: false,
    detailReport: null,
    evidenceOpen: false,
    evidenceReport: null,
    reviewOpen: false,
    reviewReport: null,
    reviewAction: 'approved',
});

const yearOptions = computed(() => {
    const year = new Date().getFullYear();
    return [year - 1, year, year + 1].map((value) => ({ value, label: String(value) }));
});

const employeeOptions = computed(() => [
    { value: '', label: 'Semua pegawai' },
    ...store.employeeOptions,
]);

const monthFilterOptions = computed(() => [
    { value: '', label: 'Semua bulan' },
    ...monthOptions,
]);

const summaryCards = computed(() => {
    const total = store.reports.length;
    const submitted = store.reports.filter((report) => report.status === 'submitted').length;
    const approved = store.reports.filter((report) => report.status === 'approved').length;
    const average = total
        ? Math.round(store.reports.reduce((sum, report) => sum + Number(report.persentase || 0), 0) / total)
        : 0;

    return [
        { label: 'Antrian Review', value: submitted, hint: 'Laporan yang perlu keputusan reviewer.' },
        { label: 'Sudah Disetujui', value: approved, hint: 'Laporan lolos verifikasi di halaman ini.' },
        { label: 'Dengan Evidence', value: store.reports.filter((report) => report.file_evidence_url).length, hint: 'Membantu verifikasi lebih cepat.' },
        { label: 'Rata-rata Capaian', value: `${average}%`, hint: 'Ringkasan performa dari hasil filter aktif.' },
    ];
});

const pageRange = computed(() => {
    const from = store.pagination.total === 0 ? 0 : ((store.pagination.currentPage - 1) * store.pagination.perPage) + 1;
    const to = Math.min(store.pagination.currentPage * store.pagination.perPage, store.pagination.total);

    return { from, to };
});

async function loadReports(overrides = {}) {
    await store.fetchReports({
        search: ui.search,
        status: ui.status,
        user_id: ui.user_id,
        bulan: ui.bulan,
        tahun: ui.tahun,
        page: store.pagination.currentPage,
        per_page: store.pagination.perPage,
        ...overrides,
    });
}

const { refresh, lastUpdated, isRefreshing } = useAutoRefresh(() => loadReports(), { interval: 20_000 });

onMounted(async () => {
    await store.bootstrapReferenceData(true);
    await loadReports({ page: 1 });
});

watch(
    () => [ui.status, ui.user_id, ui.bulan, ui.tahun],
    () => {
        loadReports({ page: 1 });
    }
);

watch(
    () => ui.search,
    (() => {
        let timeoutId;

        return () => {
            clearTimeout(timeoutId);
            timeoutId = setTimeout(() => {
                loadReports({ page: 1 });
            }, 300);
        };
    })()
);

async function handleReviewSubmit({ id, payload }) {
    try {
        await store.reviewReport(id, payload);
        dialog.reviewOpen = false;
        toast.success(payload.status === 'approved' ? 'Laporan berhasil disetujui.' : 'Laporan berhasil ditolak.');
    } catch (error) {
        toast.error(error.userMessage || 'Gagal memproses review laporan.');
    }
}

function handlePageChange(page) {
    loadReports({ page });
}
</script>

<template>
    <AppLayout>
        <PageHeader
            eyebrow="HR Manager | Review Center"
            title="Antrian review KPI yang fokus pada keputusan"
            description="Telusuri laporan berdasarkan pegawai, status, dan periode. Semua aksi review berjalan dari dialog tanpa kehilangan konteks halaman."
        >
            <template #actions>
                <div class="flex items-center gap-3">
                    <span v-if="lastUpdated" class="text-xs text-white/55">{{ formatTime(lastUpdated) }}</span>
                    <Button variant="outline" class="!border-white/20 !bg-white/10 !text-white hover:!bg-white/20" @click="refresh">
                        <RefreshCw class="h-4 w-4" :class="{ 'animate-spin': isRefreshing }" />
                        Refresh
                    </Button>
                </div>
            </template>
        </PageHeader>

        <KpiReportSummaryCards :items="summaryCards" />

        <FilterPanel
            eyebrow="Filter Review"
            title="Persempit antrian tanpa kehilangan konteks"
            description="Cari laporan berdasarkan pegawai, status, dan periode untuk mempercepat keputusan review."
            :result-text="`${store.pagination.total} laporan ditemukan pada filter aktif`"
        >
                <div class="space-y-2">
                    <label class="form-label">Cari laporan</label>
                    <Input v-model="ui.search" placeholder="Cari nama pegawai, komponen KPI, atau catatan..." />
                </div>
                <div class="space-y-2">
                    <label class="form-label">Pegawai</label>
                    <Select v-model="ui.user_id" :options="employeeOptions" />
                </div>
                <div class="space-y-2">
                    <label class="form-label">Status</label>
                    <Select v-model="ui.status" :options="reportStatusOptions" />
                </div>
                <div class="space-y-2">
                    <label class="form-label">Bulan</label>
                    <Select v-model="ui.bulan" :options="monthFilterOptions" />
                </div>
                <div class="space-y-2">
                    <label class="form-label">Tahun</label>
                    <Select v-model="ui.tahun" :options="yearOptions" />
                </div>
        </FilterPanel>

        <section class="space-y-4">
            <KpiReportSkeletonList v-if="store.isLoading" />
            <KpiReportEmptyState
                v-else-if="!store.reports.length"
                title="Tidak ada laporan yang cocok dengan filter aktif"
                description="Coba ubah kombinasi pegawai, status, bulan, atau kata kunci pencarian untuk menemukan laporan yang relevan."
            />
            <template v-else>
                <KpiReportList
                    :reports="store.reports"
                    show-employee
                    allow-review
                    @detail="dialog.detailReport = $event; dialog.detailOpen = true"
                    @evidence="dialog.evidenceReport = $event; dialog.evidenceOpen = true"
                    @approve="dialog.reviewReport = $event; dialog.reviewAction = 'approved'; dialog.reviewOpen = true"
                    @reject="dialog.reviewReport = $event; dialog.reviewAction = 'rejected'; dialog.reviewOpen = true"
                />
                <KpiReportPagination
                    :current-page="store.pagination.currentPage"
                    :last-page="store.pagination.lastPage"
                    :from="pageRange.from"
                    :to="pageRange.to"
                    :total="store.pagination.total"
                    @update:page="handlePageChange"
                />
            </template>
        </section>

        <KpiReportDetailDialog
            v-model:open="dialog.detailOpen"
            :report="dialog.detailReport"
            show-employee
            @evidence="dialog.evidenceReport = $event; dialog.evidenceOpen = true"
        />

        <KpiEvidenceDialog v-model:open="dialog.evidenceOpen" :report="dialog.evidenceReport" />

        <KpiReportReviewDialog
            v-model:open="dialog.reviewOpen"
            :report="dialog.reviewReport"
            :action="dialog.reviewAction"
            :is-submitting="store.isReviewing"
            @submit="handleReviewSubmit"
        />
    </AppLayout>
</template>
