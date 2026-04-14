<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import AppLayout from '@/components/layout/AppLayout.vue';
import Button from '@/components/ui/Button.vue';
import Input from '@/components/ui/Input.vue';
import Select from '@/components/ui/Select.vue';
import { RefreshCw } from 'lucide-vue-next';
import { useToast } from '@/composables/useToast';
import { useAutoRefresh, formatTime } from '@/composables/useAutoRefresh';
import { monthOptions, reportStatusOptions } from '@/modules/kpi-reports/constants';
import KpiEvidenceDialog from '@/modules/kpi-reports/components/KpiEvidenceDialog.vue';
import KpiReportDeleteDialog from '@/modules/kpi-reports/components/KpiReportDeleteDialog.vue';
import KpiReportDetailDialog from '@/modules/kpi-reports/components/KpiReportDetailDialog.vue';
import KpiReportEmptyState from '@/modules/kpi-reports/components/KpiReportEmptyState.vue';
import KpiReportFormDialog from '@/modules/kpi-reports/components/KpiReportFormDialog.vue';
import KpiReportList from '@/modules/kpi-reports/components/KpiReportList.vue';
import KpiReportPagination from '@/modules/kpi-reports/components/KpiReportPagination.vue';
import KpiReportSkeletonList from '@/modules/kpi-reports/components/KpiReportSkeletonList.vue';
import KpiReportSummaryCards from '@/modules/kpi-reports/components/KpiReportSummaryCards.vue';
import { useKpiReportStore } from '@/stores/kpiReport';

const toast = useToast();
const store = useKpiReportStore();

const ui = reactive({
    search: '',
    status: '',
    bulan: new Date().getMonth() + 1,
    tahun: new Date().getFullYear(),
});

const dialog = reactive({
    formOpen: false,
    formReport: null,
    detailOpen: false,
    detailReport: null,
    deleteOpen: false,
    deleteReport: null,
    evidenceOpen: false,
    evidenceReport: null,
});

const formError = ref('');

const yearOptions = computed(() => {
    const year = new Date().getFullYear();
    return [year - 1, year, year + 1].map((value) => ({ value, label: String(value) }));
});

const summaryCards = computed(() => {
    const total = store.reports.length;
    const submitted = store.reports.filter((report) => report.status === 'submitted').length;
    const drafts = store.reports.filter((report) => report.status === 'draft').length;
    const average = total
        ? Math.round(store.reports.reduce((sum, report) => sum + Number(report.persentase || 0), 0) / total)
        : 0;

    return [
        { label: 'Draft Aktif', value: drafts, hint: 'Siap dilanjutkan sebelum submit final.' },
        { label: 'Menunggu Review', value: submitted, hint: 'Laporan sudah masuk antrian reviewer.' },
        { label: 'Evidence Terunggah', value: store.reports.filter((report) => report.file_evidence_url).length, hint: 'Dokumen pendukung terhubung ke laporan.' },
        { label: 'Rata-rata Capaian', value: `${average}%`, hint: 'Ringkasan performa dari halaman aktif.' },
    ];
});

const pageRange = computed(() => {
    const from = store.pagination.total === 0 ? 0 : ((store.pagination.currentPage - 1) * store.pagination.perPage) + 1;
    const to = Math.min(store.pagination.currentPage * store.pagination.perPage, store.pagination.total);

    return { from, to };
});

async function loadReports(overrides = {}) {
    await store.fetchReports({
        bulan: ui.bulan,
        tahun: ui.tahun,
        status: ui.status,
        search: ui.search,
        page: store.pagination.currentPage,
        per_page: store.pagination.perPage,
        ...overrides,
    });
}

const { refresh, lastUpdated, isRefreshing } = useAutoRefresh(() => loadReports(), { interval: 30_000 });

onMounted(async () => {
    await store.bootstrapReferenceData(false);
    await loadReports({ page: 1 });
});

watch(
    () => [ui.status, ui.bulan, ui.tahun],
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

function openCreateDialog() {
    formError.value = '';
    dialog.formReport = null;
    dialog.formOpen = true;
}

function openEditDialog(report) {
    formError.value = '';
    dialog.formReport = report;
    dialog.formOpen = true;
}

async function handleFormSubmit({ id, payload, evidence }) {
    formError.value = '';

    try {
        const report = id
            ? await store.updateReport(id, payload)
            : await store.createReport(payload);

        if (evidence && report?.id) {
            await store.uploadEvidence(report.id, evidence);
        }

        dialog.formOpen = false;
        dialog.formReport = null;
        toast.success(
            payload.status === 'submitted'
                ? (id ? 'Laporan KPI berhasil diperbarui dan dikirim ke HR.' : 'Laporan KPI berhasil dikirim ke HR.')
                : (id ? 'Draft laporan KPI berhasil diperbarui.' : 'Draft laporan KPI berhasil disimpan.')
        );
    } catch (error) {
        formError.value = error.userMessage || 'Gagal menyimpan laporan KPI.';
    }
}

async function handleQuickSubmit(report) {
    try {
        await store.updateReport(report.id, {
            kpi_component_id: report.kpi_component_id,
            period_type: report.period_type,
            tanggal: report.tanggal,
            period_label: report.period_label,
            nilai_target: report.nilai_target,
            nilai_aktual: report.nilai_aktual,
            catatan: report.catatan,
            status: 'submitted',
        });

        toast.success('Laporan KPI berhasil dikirim ke HR.');
    } catch (error) {
        toast.error(error.userMessage || 'Gagal mengirim laporan KPI ke HR.');
    }
}

async function handleDeleteConfirm() {
    try {
        await store.deleteReport(dialog.deleteReport.id);
        dialog.deleteOpen = false;
        toast.success('Laporan KPI berhasil dihapus.');
    } catch (error) {
        toast.error(error.userMessage || 'Gagal menghapus laporan KPI.');
    }
}

function handlePageChange(page) {
    loadReports({ page });
}
</script>

<template>
    <AppLayout>
        <section class="page-hero">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <div class="page-hero-meta">Pegawai | KPI Workspace</div>
                    <h1 class="mt-4 text-3xl font-semibold tracking-tight">Laporan KPI yang rapi, cepat, dan mudah direview</h1>
                    <p class="mt-3 max-w-2xl text-sm leading-6 text-white/80">
                        Kelola draft, evidence, dan progres realisasi dalam satu workspace dengan feedback yang jelas dan tanpa pindah halaman.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <span v-if="lastUpdated" class="text-xs text-white/55">{{ formatTime(lastUpdated) }}</span>
                    <Button variant="outline" class="!border-white/20 !bg-white/10 !text-white hover:!bg-white/20" @click="refresh">
                        <RefreshCw class="h-4 w-4" :class="{ 'animate-spin': isRefreshing }" />
                        Refresh
                    </Button>
                    <Button class="!bg-white !text-slate-900 hover:!bg-slate-100" @click="openCreateDialog">Buat laporan</Button>
                </div>
            </div>
        </section>

        <KpiReportSummaryCards :items="summaryCards" />

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4 dark:border-slate-800">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Filter & Pencarian</p>
                    <h2 class="mt-0.5 text-sm font-semibold text-slate-900 dark:text-slate-100">Saring laporan berdasarkan kriteria</h2>
                </div>
            </div>
            <div class="p-6">
                <div class="grid gap-4 lg:grid-cols-[1.4fr_repeat(3,minmax(0,1fr))]">
                    <div>
                        <label class="form-label">Cari laporan</label>
                        <Input v-model="ui.search" placeholder="Cari komponen KPI atau catatan..." />
                    </div>
                    <div>
                        <label class="form-label">Status</label>
                        <Select v-model="ui.status" :options="reportStatusOptions" />
                    </div>
                    <div>
                        <label class="form-label">Bulan</label>
                        <Select v-model="ui.bulan" :options="monthOptions" />
                    </div>
                    <div>
                        <label class="form-label">Tahun</label>
                        <Select v-model="ui.tahun" :options="yearOptions" />
                    </div>
                </div>
            </div>
        </section>

        <section class="space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Daftar Laporan KPI</h2>
                    <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">
                        <template v-if="store.pagination.total">
                            {{ pageRange.from }}–{{ pageRange.to }} dari {{ store.pagination.total }} laporan
                        </template>
                        <template v-else>Tidak ada laporan pada filter ini</template>
                    </p>
                </div>
            </div>

            <KpiReportSkeletonList v-if="store.isLoading" />
            <KpiReportEmptyState
                v-else-if="!store.reports.length"
                title="Belum ada laporan KPI pada periode ini"
                description="Mulai dari draft baru, isi realisasi, lalu lampirkan evidence agar proses review lebih cepat."
            >
                <div class="mt-5">
                    <Button @click="openCreateDialog">Buat laporan pertama</Button>
                </div>
            </KpiReportEmptyState>
            <template v-else>
                <KpiReportList
                    :reports="store.reports"
                    allow-manage
                    @submit="handleQuickSubmit"
                    @edit="openEditDialog"
                    @delete="dialog.deleteReport = $event; dialog.deleteOpen = true"
                    @detail="dialog.detailReport = $event; dialog.detailOpen = true"
                    @evidence="dialog.evidenceReport = $event; dialog.evidenceOpen = true"
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

        <KpiReportFormDialog
            v-model:open="dialog.formOpen"
            :report="dialog.formReport"
            :components="store.components"
            :component-options="store.componentOptions"
            :is-saving="store.isSaving"
            :is-uploading-evidence="store.isUploadingEvidence"
            :error-message="formError"
            @submit="handleFormSubmit"
        />

        <KpiReportDetailDialog
            v-model:open="dialog.detailOpen"
            :report="dialog.detailReport"
            @evidence="dialog.evidenceReport = $event; dialog.evidenceOpen = true"
        />

        <KpiReportDeleteDialog
            v-model:open="dialog.deleteOpen"
            :is-deleting="store.isDeleting"
            :report-title="dialog.deleteReport?.kpi_component?.objectives || 'laporan ini'"
            @confirm="handleDeleteConfirm"
        />

        <KpiEvidenceDialog v-model:open="dialog.evidenceOpen" :report="dialog.evidenceReport" />
    </AppLayout>
</template>
