import { computed, reactive, ref } from 'vue';
import { defineStore } from 'pinia';
import { useApiHandler } from '@/composables/useApiHandler';
import { usePagination } from '@/composables/usePagination';
import { kpiReportService } from '@/services/kpiReportService';

const defaultFilters = () => ({
    bulan: new Date().getMonth() + 1,
    tahun: new Date().getFullYear(),
    user_id: '',
    status: '',
    search: '',
    page: 1,
    per_page: 8,
});

export const useKpiReportStore = defineStore('kpiReport', () => {
    const reports = ref([]);
    const employees = ref([]);
    const indicators = ref([]);
    const isSaving = ref(false);
    const isDeleting = ref(false);
    const isReviewing = ref(false);
    const isUploadingEvidence = ref(false);
    const filters = reactive(defaultFilters());

    const listHandler = useApiHandler();
    const pagination = usePagination({ perPage: filters.per_page });

    const employeeOptions = computed(() => employees.value.map((employee) => ({
        value: String(employee.id),
        label: employee.nama,
    })));

    const indicatorOptions = computed(() => indicators.value.map((indicator) => ({
        value: String(indicator.id),
        label: indicator.position
            ? `${indicator.name} (${indicator.position.nama})`
            : indicator.name,
    })));

    async function fetchReports(overrides = {}) {
        Object.assign(filters, overrides);

        const result = await listHandler.execute(
            () => kpiReportService.listReports(filters),
            { rethrow: true }
        );

        reports.value = result?.items ?? [];
        pagination.sync(result?.pagination ?? {});
        return reports.value;
    }

    async function bootstrapReferenceData(loadEmployees = false) {
        const requests = [kpiReportService.listIndicators()];

        if (loadEmployees) {
            requests.push(kpiReportService.listEmployees());
        }

        const [indicatorData, employeeData] = await Promise.all(requests);

        indicators.value = indicatorData ?? [];

        if (loadEmployees) {
            employees.value = employeeData ?? [];
        }
    }

    function patchLocalReport(id, patch) {
        const index = reports.value.findIndex((report) => report.id === id);

        if (index === -1) {
            return null;
        }

        const previous = { ...reports.value[index] };
        reports.value[index] = { ...reports.value[index], ...patch };
        return previous;
    }

    async function createReport(payload) {
        return listHandler.execute(
            async () => {
                const created = await kpiReportService.createReport(payload);
                reports.value = [created, ...reports.value];
                pagination.state.total += 1;
                return created;
            },
            {
                loadingRef: isSaving,
                rethrow: true,
            }
        );
    }

    async function updateReport(id, payload) {
        const optimistic = patchLocalReport(id, payload);

        return listHandler.execute(
            async () => {
                const updated = await kpiReportService.updateReport(id, payload);
                patchLocalReport(id, updated);
                return updated;
            },
            {
                loadingRef: isSaving,
                onError: () => {
                    if (optimistic) {
                        patchLocalReport(id, optimistic);
                    }
                },
                rethrow: true,
            }
        );
    }

    async function deleteReport(id) {
        const previousReports = [...reports.value];
        reports.value = reports.value.filter((report) => report.id !== id);
        pagination.state.total = Math.max(0, pagination.state.total - 1);

        return listHandler.execute(
            () => kpiReportService.deleteReport(id),
            {
                loadingRef: isDeleting,
                onError: () => {
                    reports.value = previousReports;
                    pagination.state.total += 1;
                },
                rethrow: true,
            }
        );
    }

    async function reviewReport(id, payload) {
        const optimistic = patchLocalReport(id, payload);

        return listHandler.execute(
            async () => {
                const updated = await kpiReportService.reviewReport(id, payload);
                patchLocalReport(id, updated);
                return updated;
            },
            {
                loadingRef: isReviewing,
                onError: () => {
                    if (optimistic) {
                        patchLocalReport(id, optimistic);
                    }
                },
                rethrow: true,
            }
        );
    }

    async function uploadEvidence(reportId, file) {
        return listHandler.execute(
            async () => {
                const evidence = await kpiReportService.uploadEvidence(reportId, file);
                patchLocalReport(reportId, evidence);
                return evidence;
            },
            {
                loadingRef: isUploadingEvidence,
                rethrow: true,
            }
        );
    }

    function setFilter(key, value) {
        filters[key] = value;
    }

    function resetFilters() {
        Object.assign(filters, defaultFilters());
        pagination.reset();
    }

    return {
        reports,
        employees,
        indicators,
        filters,
        pagination: pagination.state,
        employeeOptions,
        indicatorOptions,
        isLoading: listHandler.isLoading,
        isSaving,
        isDeleting,
        isReviewing,
        isUploadingEvidence,
        error: listHandler.error,
        fetchReports,
        bootstrapReferenceData,
        createReport,
        updateReport,
        deleteReport,
        reviewReport,
        uploadEvidence,
        setFilter,
        resetFilters,
    };
});
