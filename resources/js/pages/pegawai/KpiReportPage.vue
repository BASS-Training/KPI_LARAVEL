<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useKpiReportStore } from '@/stores/kpiReport';
import { useKpiColor } from '@/composables/useKpiColor';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/components/layout/AppLayout.vue';
import Dialog from '@/components/ui/Dialog.vue';
import Input from '@/components/ui/Input.vue';
import Select from '@/components/ui/Select.vue';
import Skeleton from '@/components/ui/Skeleton.vue';
import ScoreBadge from '@/components/shared/ScoreBadge.vue';
import api from '@/services/api';

const store  = useKpiReportStore();
const toast  = useToast();
const { getProgressClass, formatPct } = useKpiColor();

// ── Filter state ─────────────────────────────────────────────────────────────
const filterBulan = ref(new Date().getMonth() + 1);
const filterTahun = ref(new Date().getFullYear());

// ── Form state ────────────────────────────────────────────────────────────────
const showForm   = ref(false);
const editMode   = ref(false);
const editId     = ref(null);
const formError  = ref('');
const evidenceFile = ref(null);
const uploadingEvidence = ref(false);

const emptyForm = () => ({
    kpi_component_id: '',
    period_type: 'monthly',
    tanggal: new Date().toISOString().slice(0, 10),
    period_label: '',
    nilai_target: '',
    nilai_aktual: '',
    catatan: '',
    status: 'submitted',
});
const form = reactive(emptyForm());

// ── KPI component options ────────────────────────────────────────────────────
const kpiComponents = ref([]);
const componentOptions = computed(() =>
    kpiComponents.value.map((c) => ({ value: String(c.id), label: c.objectives })),
);
const selectedComponent = computed(() =>
    kpiComponents.value.find((c) => String(c.id) === String(form.kpi_component_id)),
);

// ── Period label helpers ──────────────────────────────────────────────────────
const bulanOptions = Array.from({ length: 12 }, (_, i) => ({
    value: i + 1,
    label: new Date(0, i).toLocaleDateString('id-ID', { month: 'long' }),
}));
const tahunOptions = [2024, 2025, 2026, 2027].map((y) => ({ value: y, label: String(y) }));

function buildPeriodLabel() {
    const d = new Date(form.tanggal || new Date());
    if (form.period_type === 'monthly') {
        form.period_label = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;
    } else if (form.period_type === 'weekly') {
        // ISO week
        const jan1 = new Date(d.getFullYear(), 0, 1);
        const week = Math.ceil(((d - jan1) / 86400000 + jan1.getDay() + 1) / 7);
        form.period_label = `${d.getFullYear()}-W${String(week).padStart(2, '0')}`;
    } else {
        form.period_label = form.tanggal;
    }
}

// ── Delete dialog ─────────────────────────────────────────────────────────────
const deleteDialog = reactive({ open: false, id: null });

const reports   = computed(() => store.reports);
const isLoading = computed(() => store.isLoading);
const isSaving  = computed(() => store.isSaving);

// ── Percentage computed ───────────────────────────────────────────────────────
function pctFromForm() {
    const a = parseFloat(form.nilai_aktual);
    const t = parseFloat(form.nilai_target) || parseFloat(selectedComponent.value?.target) || 0;
    if (!t) return a > 0 ? 100 : 0;
    return Math.round((a / t) * 100 * 10) / 10;
}

onMounted(async () => {
    await loadComponents();
    await loadReports();
});

async function loadComponents() {
    const { data: resp } = await api.get('/kpi-components', { params: { per_page: 100 } });
    kpiComponents.value = resp.data?.items ?? [];
}

async function loadReports() {
    await store.fetchReports({ bulan: filterBulan.value, tahun: filterTahun.value });
}

async function applyFilter() { await loadReports(); }

function openCreate() {
    Object.assign(form, emptyForm());
    editMode.value   = false;
    editId.value     = null;
    formError.value  = '';
    evidenceFile.value = null;
    buildPeriodLabel();
    showForm.value   = true;
}

function openEdit(r) {
    Object.assign(form, {
        kpi_component_id: String(r.kpi_component_id),
        period_type:      r.period_type,
        tanggal:          r.tanggal,
        period_label:     r.period_label,
        nilai_target:     r.nilai_target ?? '',
        nilai_aktual:     r.nilai_aktual ?? '',
        catatan:          r.catatan ?? '',
        status:           r.status,
    });
    editMode.value  = true;
    editId.value    = r.id;
    formError.value = '';
    evidenceFile.value = null;
    showForm.value  = true;
}

async function save() {
    formError.value = '';
    if (!form.kpi_component_id) { formError.value = 'Pilih komponen KPI.'; return; }
    if (!form.tanggal)           { formError.value = 'Tanggal wajib diisi.'; return; }
    if (form.nilai_aktual === '') { formError.value = 'Nilai aktual wajib diisi.'; return; }
    buildPeriodLabel();

    // Use component target if not filled
    if (!form.nilai_target && selectedComponent.value?.target) {
        form.nilai_target = selectedComponent.value.target;
    }

    const payload = {
        kpi_component_id: Number(form.kpi_component_id),
        period_type:      form.period_type,
        tanggal:          form.tanggal,
        period_label:     form.period_label,
        nilai_target:     form.nilai_target !== '' ? Number(form.nilai_target) : null,
        nilai_aktual:     Number(form.nilai_aktual),
        catatan:          form.catatan || null,
        status:           form.status,
    };

    try {
        let saved;
        if (editMode.value) {
            saved = await store.updateReport(editId.value, payload);
            toast.success('Laporan berhasil diperbarui.');
        } else {
            saved = await store.createReport(payload);
            toast.success('Laporan KPI berhasil disimpan.');
        }

        // Upload evidence if selected
        if (evidenceFile.value && saved?.id) {
            uploadingEvidence.value = true;
            try {
                await store.uploadEvidence(saved.id, evidenceFile.value);
                toast.success('File evidence berhasil diunggah.');
            } catch {
                toast.warning('Laporan tersimpan, tapi gagal upload evidence.');
            } finally {
                uploadingEvidence.value = false;
            }
        }

        showForm.value = false;
    } catch (e) {
        formError.value = e.userMessage ?? 'Gagal menyimpan laporan.';
    }
}

async function doDelete() {
    try {
        await store.deleteReport(deleteDialog.id);
        toast.success('Laporan dihapus.');
    } catch (e) {
        toast.error(e.userMessage ?? 'Gagal menghapus.');
    } finally {
        deleteDialog.open = false;
    }
}

function formatDate(v) {
    if (!v) return '-';
    return new Date(v).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
}
</script>

<template>
    <AppLayout>
        <!-- Hero -->
        <section class="page-hero">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <div class="page-hero-meta">Laporan KPI Saya</div>
                    <h2 class="mt-4 text-2xl font-bold leading-tight md:text-3xl">Progress & Pencapaian KPI</h2>
                    <p class="mt-2 max-w-xl text-sm leading-6 text-white/78">
                        Input progress KPI, upload bukti (evidence), dan pantau pencapaian target Anda.
                    </p>
                </div>
                <button class="btn-primary shrink-0" @click="openCreate">+ Input Progress KPI</button>
            </div>
        </section>

        <!-- Filter -->
        <div class="flex flex-wrap items-end gap-3">
            <div>
                <label class="form-label">Bulan</label>
                <select v-model="filterBulan" class="form-select">
                    <option v-for="opt in bulanOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                </select>
            </div>
            <div>
                <label class="form-label">Tahun</label>
                <select v-model="filterTahun" class="form-select">
                    <option v-for="opt in tahunOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                </select>
            </div>
            <button class="btn-secondary" @click="applyFilter">Terapkan</button>
        </div>

        <!-- Report list -->
        <section class="dashboard-panel mt-2 overflow-hidden">
            <div class="border-b border-slate-200 px-6 py-4">
                <p class="section-heading">Laporan Periode Ini</p>
                <h3 class="mt-1 text-lg font-bold text-slate-900">{{ store.pagination.total }} laporan ditemukan</h3>
            </div>

            <div v-if="isLoading" class="space-y-3 p-6">
                <Skeleton v-for="i in 4" :key="i" class="h-20 rounded-xl" />
            </div>

            <div v-else-if="!reports.length" class="py-16 text-center text-sm text-slate-400">
                Belum ada laporan untuk periode ini. Klik <strong>+ Input Progress KPI</strong>.
            </div>

            <div v-else class="divide-y divide-slate-100">
                <div v-for="r in reports" :key="r.id" class="px-6 py-4 transition-colors hover:bg-slate-50">
                    <div class="flex items-start gap-4">
                        <!-- Score badge -->
                        <div class="flex h-12 w-12 shrink-0 flex-col items-center justify-center rounded-xl border-2"
                            :class="r.persentase > 100 ? 'border-blue-200 bg-blue-50' : r.persentase >= 80 ? 'border-emerald-200 bg-emerald-50' : r.persentase >= 50 ? 'border-amber-200 bg-amber-50' : 'border-red-200 bg-red-50'"
                        >
                            <span class="text-[11px] font-bold"
                                :class="r.persentase > 100 ? 'text-blue-600' : r.persentase >= 80 ? 'text-emerald-600' : r.persentase >= 50 ? 'text-amber-600' : 'text-red-600'">
                                {{ r.persentase !== null ? Math.round(r.persentase) + '%' : '-' }}
                            </span>
                        </div>

                        <!-- Info -->
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-sm font-semibold text-slate-900">
                                    {{ r.kpi_component?.objectives ?? `KPI #${r.kpi_component_id}` }}
                                </span>
                                <ScoreBadge :score-label="r.score_label" :show-pct="false" />
                                <span class="badge-neutral text-[10px]">{{ r.period_label }}</span>
                                <span :class="r.status === 'submitted' || r.status === 'approved' ? 'badge-success' : r.status === 'rejected' ? 'badge-danger' : 'badge-warning'" class="text-[10px]">
                                    {{ { draft: 'Draft', submitted: 'Submitted', approved: 'Approved', rejected: 'Rejected' }[r.status] }}
                                </span>
                            </div>

                            <!-- Progress bar -->
                            <div class="mt-2 max-w-xs">
                                <div class="mb-1 flex justify-between text-[10px] text-slate-500">
                                    <span>Aktual: {{ r.nilai_aktual ?? '-' }} / Target: {{ r.nilai_target ?? r.kpi_component?.target ?? '-' }}</span>
                                    <span>{{ formatPct(r.persentase) }}</span>
                                </div>
                                <div class="h-1.5 rounded-full bg-slate-200">
                                    <div
                                        :class="['h-1.5 rounded-full transition-all', getProgressClass(r.persentase)]"
                                        :style="{ width: Math.min(100, r.persentase ?? 0) + '%' }"
                                    />
                                </div>
                            </div>

                            <div class="mt-1.5 flex flex-wrap items-center gap-3 text-[11px] text-slate-400">
                                <span>{{ formatDate(r.tanggal) }}</span>
                                <span v-if="r.catatan" class="truncate max-w-[200px]">📝 {{ r.catatan }}</span>
                                <a v-if="r.file_evidence_url" :href="r.file_evidence_url" target="_blank" class="text-blue-500 hover:underline">📎 Evidence</a>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div v-if="r.status === 'draft'" class="flex shrink-0 gap-2">
                            <button class="btn-secondary !px-3 !py-1.5 text-xs" @click="openEdit(r)">Edit</button>
                            <button class="btn-danger !px-3 !py-1.5 text-xs" @click="deleteDialog.open = true; deleteDialog.id = r.id">Hapus</button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Form Dialog -->
        <Dialog
            v-model:open="showForm"
            :title="editMode ? 'Edit Laporan KPI' : 'Input Progress KPI'"
            class="max-w-lg"
        >
            <div class="mt-4 space-y-4">
                <div>
                    <label class="form-label">Komponen KPI <span class="text-red-500">*</span></label>
                    <Select v-model="form.kpi_component_id" :options="componentOptions" placeholder="Pilih komponen KPI" />
                    <p v-if="selectedComponent" class="mt-1 text-[11px] text-slate-500">
                        Target default: {{ selectedComponent.target ?? '-' }} {{ selectedComponent.satuan }}
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="form-label">Periode</label>
                        <Select
                            v-model="form.period_type"
                            :options="[{ value:'daily',label:'Harian' },{ value:'weekly',label:'Mingguan' },{ value:'monthly',label:'Bulanan' }]"
                        />
                    </div>
                    <div>
                        <label class="form-label">Tanggal <span class="text-red-500">*</span></label>
                        <Input v-model="form.tanggal" type="date" @change="buildPeriodLabel" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="form-label">Nilai Target</label>
                        <Input v-model="form.nilai_target" type="number" placeholder="Otomatis dari komponen" />
                    </div>
                    <div>
                        <label class="form-label">Nilai Aktual <span class="text-red-500">*</span></label>
                        <Input v-model="form.nilai_aktual" type="number" placeholder="0" />
                    </div>
                </div>

                <!-- Preview percentage -->
                <div v-if="form.nilai_aktual !== ''" class="rounded-lg bg-slate-50 px-4 py-3">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-slate-600">Estimasi Pencapaian</span>
                        <span class="font-bold text-slate-900">{{ pctFromForm() }}%</span>
                    </div>
                    <div class="mt-2 h-2 rounded-full bg-slate-200">
                        <div
                            :class="['h-2 rounded-full', getProgressClass(pctFromForm())]"
                            :style="{ width: Math.min(100, pctFromForm()) + '%' }"
                        />
                    </div>
                </div>

                <div>
                    <label class="form-label">Catatan</label>
                    <textarea v-model="form.catatan" rows="2" class="form-textarea" placeholder="Keterangan tambahan..." />
                </div>

                <div>
                    <label class="form-label">Upload Evidence (opsional)</label>
                    <input
                        type="file"
                        accept=".pdf,.png,.jpg,.jpeg,.doc,.docx,.xlsx"
                        class="block w-full text-sm text-slate-500 file:mr-3 file:rounded-lg file:border-0 file:bg-blue-50 file:px-3 file:py-1.5 file:text-xs file:font-medium file:text-blue-700 hover:file:bg-blue-100"
                        @change="e => evidenceFile = e.target.files[0]"
                    />
                    <p class="mt-1 text-[10px] text-slate-400">Maks 5MB. Format: PDF, PNG, JPG, DOC, XLSX</p>
                </div>

                <p v-if="formError" class="rounded-lg bg-red-50 px-3 py-2 text-xs text-red-600">{{ formError }}</p>

                <div class="flex justify-end gap-2 pt-2">
                    <button class="btn-secondary" @click="showForm = false">Batal</button>
                    <button class="btn-primary" :disabled="isSaving || uploadingEvidence" @click="save">
                        {{ isSaving ? 'Menyimpan...' : uploadingEvidence ? 'Upload...' : editMode ? 'Simpan' : 'Submit Laporan' }}
                    </button>
                </div>
            </div>
        </Dialog>

        <!-- Delete dialog -->
        <Dialog v-model:open="deleteDialog.open" title="Hapus Laporan">
            <p class="mt-3 text-sm text-slate-600">Yakin ingin menghapus laporan ini? Tindakan tidak dapat dibatalkan.</p>
            <div class="mt-5 flex justify-end gap-2">
                <button class="btn-secondary" @click="deleteDialog.open = false">Batal</button>
                <button class="btn-danger" @click="doDelete">Hapus</button>
            </div>
        </Dialog>
    </AppLayout>
</template>
