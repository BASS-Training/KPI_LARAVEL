<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import AppLayout from '@/components/layout/AppLayout.vue';
import Dialog from '@/components/ui/Dialog.vue';
import Input from '@/components/ui/Input.vue';
import Textarea from '@/components/ui/Textarea.vue';
import Alert from '@/components/ui/Alert.vue';
import { ClipboardList, CheckCircle2, Clock, Users } from 'lucide-vue-next';
import PageHeader from '@/components/shared/PageHeader.vue';
import MetricCard from '@/components/shared/MetricCard.vue';
import EmptyState from '@/components/shared/EmptyState.vue';
import LoadingRows from '@/components/shared/LoadingRows.vue';
import { useToast } from '@/composables/useToast';
import { useTaskAssignmentStore } from '@/stores/taskAssignment';
import { useEmployeeStore } from '@/stores/employee';
import { useKpiIndicatorStore } from '@/stores/kpiIndicator';

const store = useTaskAssignmentStore();
const empStore = useEmployeeStore();
const kpiIndicatorStore = useKpiIndicatorStore();
const toast = useToast();

const showForm = ref(false);
const editMode = ref(false);
const selectedId = ref(null);
const submitting = ref(false);
const formError = ref('');
const deleteState = ref({ open: false, id: null, title: '' });

const emptyForm = () => ({
    judul: '',
    deskripsi: '',
    assigned_to: '',
    start_date: '',
    end_date: '',
    jenis_pekerjaan: 'Task KPI',
    kpi_indicator_id: '',
    weight: '',
    target_value: '',
    status: 'Pending',
});

const form = reactive(emptyForm());
const errors = reactive({});

const tasks = computed(() => store.assignedTasks);
const selectedKpiIndicator = computed(() =>
    kpiIndicatorStore.indicators.find((ind) => String(ind.id) === String(form.kpi_indicator_id))
);

const summary = computed(() => ({
    total: store.pagination.total,
    done: tasks.value.filter((t) => t.status === 'Selesai').length,
    inProgress: tasks.value.filter((t) => t.status === 'Dalam Proses').length,
    pending: tasks.value.filter((t) => t.status === 'Pending').length,
}));

const summaryCards = [
    { key: 'total',      label: 'Total Tugas',   icon: ClipboardList, tone: 'neutral', hint: 'Seluruh assignment aktif' },
    { key: 'done',       label: 'Selesai',        icon: CheckCircle2,  tone: 'excellent', hint: 'Tugas sudah tuntas' },
    { key: 'inProgress', label: 'Dalam Proses',   icon: Clock,         tone: 'average', hint: 'Sedang dikerjakan' },
    { key: 'pending',    label: 'Pending',         icon: Users,         tone: 'bad', hint: 'Butuh follow-up HR' },
];

const statusOptions = [
    { value: 'Pending',       label: 'Pending' },
    { value: 'Dalam Proses',  label: 'Dalam Proses' },
    { value: 'Selesai',       label: 'Selesai' },
];

const statusBadge = {
    'Selesai':      'badge-success',
    'Dalam Proses': 'badge-warning',
    'Pending':      'badge-neutral',
};

onMounted(async () => {
    await Promise.all([
        store.fetchAssignedTasks(),
        empStore.fetchEmployees ? empStore.fetchEmployees() : empStore.fetchEmployees?.(),
        kpiIndicatorStore.fetchIndicators(),
    ]);
});

function resetForm() {
    Object.assign(form, emptyForm());
    formError.value = '';
    Object.keys(errors).forEach((k) => { errors[k] = ''; });
}

function openCreate() {
    editMode.value = false;
    selectedId.value = null;
    resetForm();
    showForm.value = true;
}

function openEdit(task) {
    editMode.value = true;
    selectedId.value = task.id;
    resetForm();
    Object.assign(form, {
        judul:        task.judul ?? '',
        deskripsi:    task.deskripsi ?? '',
        assigned_to:  task.assigned_to ?? task.assignee?.id ?? '',
        start_date:   task.start_date ?? '',
        end_date:     task.end_date ?? '',
        jenis_pekerjaan: task.jenis_pekerjaan ?? 'Task KPI',
        kpi_indicator_id: task.kpi_indicator_id ?? task.kpiIndicator?.id ?? '',
        weight:       task.weight ?? '',
        target_value: task.target_value ?? '',
        status:       task.status ?? 'Pending',
    });
    showForm.value = true;
}

function applyIndicatorDefaults() {
    const indicator = selectedKpiIndicator.value;
    if (!indicator) return;

    form.jenis_pekerjaan = indicator.name || 'Task KPI';
    if (indicator.weight !== null && indicator.weight !== undefined) {
        form.weight = Number(indicator.weight);
    }
    if (indicator.default_target_value !== null && indicator.default_target_value !== undefined) {
        form.target_value = indicator.default_target_value;
    }
}

function validate() {
    Object.assign(errors, { judul: '', assigned_to: '', start_date: '', end_date: '', weight: '' });
    let valid = true;

    if (!form.judul.trim()) { errors.judul = 'Judul tugas wajib diisi.'; valid = false; }
    if (!form.assigned_to) { errors.assigned_to = 'Pilih pegawai yang ditugaskan.'; valid = false; }
    if (!form.start_date) { errors.start_date = 'Tanggal mulai wajib diisi.'; valid = false; }
    if (!form.end_date) { errors.end_date = 'Tanggal selesai wajib diisi.'; valid = false; }
    if (form.start_date && form.end_date && form.end_date < form.start_date) {
        errors.end_date = 'Tanggal selesai tidak boleh sebelum tanggal mulai.'; valid = false;
    }
    if (form.weight !== '' && (Number(form.weight) < 0 || Number(form.weight) > 100)) {
        errors.weight = 'Bobot harus antara 0 dan 100.'; valid = false;
    }

    return valid;
}

async function submit() {
    if (!validate()) return;
    submitting.value = true;
    formError.value = '';

    try {
        const payload = {
            judul:        form.judul,
            deskripsi:    form.deskripsi || null,
            assigned_to:  Number(form.assigned_to),
            start_date:   form.start_date,
            end_date:     form.end_date,
            jenis_pekerjaan: form.jenis_pekerjaan || 'Task KPI',
            kpi_indicator_id: form.kpi_indicator_id ? Number(form.kpi_indicator_id) : null,
            weight:       form.weight !== '' ? Number(form.weight) : null,
            target_value: form.target_value !== '' ? Number(form.target_value) : null,
            status:       form.status,
        };

        if (editMode.value && selectedId.value) {
            await store.updateAssignment(selectedId.value, payload);
            toast.success('Tugas berhasil diperbarui.');
        } else {
            await store.createAssignment(payload);
            toast.success('Tugas berhasil ditetapkan.');
        }

        showForm.value = false;
        await store.fetchAssignedTasks();
    } catch (err) {
        formError.value = err.response?.data?.message || 'Gagal menyimpan tugas.';
    } finally {
        submitting.value = false;
    }
}

async function confirmDelete() {
    try {
        await store.deleteAssignment(deleteState.value.id);
        toast.success('Tugas berhasil dihapus.');
        deleteState.value.open = false;
    } catch (err) {
        toast.error(err.response?.data?.message || 'Gagal menghapus tugas.');
    }
}

function formatDate(d) {
    if (!d) return '-';
    return new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
}

function isOverdue(task) {
    if (task.status === 'Selesai') return false;
    if (!task.end_date) return false;
    return new Date(task.end_date) < new Date();
}
</script>

<template>
    <AppLayout>
        <template #topbar-actions>
            <button class="btn-primary" @click="openCreate">
                <svg class="mr-1.5 h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 5v14M5 12h14"/>
                </svg>
                Tetapkan Tugas
            </button>
        </template>

        <PageHeader
            eyebrow="HR Panel - Manajemen Tugas"
            title="Penugasan Tugas"
            description="Tetapkan tugas khusus kepada pegawai dengan deadline, bobot KPI, status, dan target yang jelas."
        >
            <template #actions>
                <button class="btn-primary" @click="openCreate">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 5v14M5 12h14"/>
                    </svg>
                    Tetapkan Tugas
                </button>
            </template>
        </PageHeader>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <MetricCard
                v-for="card in summaryCards"
                :key="card.key"
                :label="card.label"
                :value="summary[card.key]"
                :hint="card.hint"
                :tone="card.tone"
            >
                <template #icon>
                    <component :is="card.icon" class="h-5 w-5" />
                </template>
            </MetricCard>
        </div>

        <!-- Task List -->
        <section class="dashboard-panel overflow-hidden">
            <div class="border-b border-slate-200 px-6 py-5">
                <p class="section-heading">Daftar Tugas</p>
                <h3 class="mt-1 text-lg font-bold text-slate-900">{{ store.pagination.total }} tugas terdaftar</h3>
            </div>

            <div class="p-6">
                <template v-if="store.isLoading">
                    <LoadingRows :rows="6" />
                </template>

                <template v-else-if="tasks.length">
                    <div class="space-y-3">
                        <div
                            v-for="task in tasks"
                            :key="task.id"
                            :class="['data-row', isOverdue(task) ? 'border-l-2 border-l-red-400' : '']"
                        >
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="text-sm font-semibold text-slate-900">{{ task.judul }}</span>
                                    <span :class="[statusBadge[task.status] ?? 'badge-neutral', '!text-[10px]']">
                                        {{ task.status }}
                                    </span>
                                    <span v-if="isOverdue(task)" class="badge-danger !text-[10px]">Terlambat</span>
                                </div>
                                <div class="mt-0.5 flex flex-wrap gap-x-3 text-xs text-slate-500">
                                    <span v-if="task.assignee">
                                        <svg class="mr-0.5 inline-block h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
                                        </svg>
                                        {{ task.assignee?.nama ?? task.assigned_to }}
                                    </span>
                                    <span>
                                        <svg class="mr-0.5 inline-block h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>
                                        </svg>
                                        {{ formatDate(task.start_date) }} – {{ formatDate(task.end_date) }}
                                    </span>
                                    <span v-if="task.weight">· Bobot {{ task.weight }}%</span>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <button class="btn-secondary !px-3 !py-1.5 text-xs" @click="openEdit(task)">Edit</button>
                                <button
                                    class="btn-danger !px-3 !py-1.5 text-xs"
                                    @click="deleteState = { open: true, id: task.id, title: task.judul }"
                                >
                                    Hapus
                                </button>
                            </div>
                        </div>
                    </div>
                </template>

                <EmptyState
                    v-else
                    title="Belum ada tugas yang ditetapkan"
                    description="Tambahkan assignment KPI agar pekerjaan pegawai dapat dipantau dengan deadline dan status yang jelas."
                    action-label="Tetapkan Tugas"
                    @action="openCreate"
                />
            </div>
        </section>

        <!-- Form Dialog -->
        <Dialog v-model:open="showForm" :title="editMode ? 'Edit Tugas' : 'Tetapkan Tugas Baru'" class="max-w-xl">
            <Alert v-if="formError" variant="danger" class="mb-4">{{ formError }}</Alert>

            <div class="mt-4 space-y-4">
                <div>
                    <label class="form-label">Pegawai <span class="text-red-500">*</span></label>
                    <select v-model="form.assigned_to" class="form-input">
                        <option value="">— Pilih Pegawai —</option>
                        <option
                            v-for="emp in empStore.employees"
                            :key="emp.id"
                            :value="emp.id"
                        >
                            {{ emp.nama }} — {{ emp.jabatan }}
                        </option>
                    </select>
                    <p v-if="errors.assigned_to" class="mt-1 text-xs text-red-500">{{ errors.assigned_to }}</p>
                </div>

                <div>
                    <label class="form-label">Judul Tugas <span class="text-red-500">*</span></label>
                    <Input v-model="form.judul" placeholder="Contoh: Buat laporan rekonsiliasi Q2" />
                    <p v-if="errors.judul" class="mt-1 text-xs text-red-500">{{ errors.judul }}</p>
                </div>

                <div>
                    <label class="form-label">Indikator KPI</label>
                    <select v-model="form.kpi_indicator_id" class="form-input" @change="applyIndicatorDefaults">
                        <option value="">— Tanpa indikator KPI —</option>
                        <option
                            v-for="indicator in kpiIndicatorStore.indicators"
                            :key="indicator.id"
                            :value="indicator.id"
                        >
                            {{ indicator.name }} ({{ indicator.weight }}%)
                        </option>
                    </select>
                    <p class="mt-1 text-[11px] text-slate-400">
                        Jika dipilih, bobot dan target default akan diisi otomatis dan tetap bisa disesuaikan.
                    </p>
                </div>

                <div>
                    <label class="form-label">Jenis Pekerjaan</label>
                    <Input v-model="form.jenis_pekerjaan" placeholder="Contoh: Task KPI, Administratif, Pelayanan" />
                </div>

                <div>
                    <label class="form-label">Deskripsi</label>
                    <Textarea v-model="form.deskripsi" rows="3" placeholder="Detail tugas, instruksi, atau deliverable..." />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Tanggal Mulai <span class="text-red-500">*</span></label>
                        <Input v-model="form.start_date" type="date" />
                        <p v-if="errors.start_date" class="mt-1 text-xs text-red-500">{{ errors.start_date }}</p>
                    </div>
                    <div>
                        <label class="form-label">Tanggal Selesai <span class="text-red-500">*</span></label>
                        <Input v-model="form.end_date" type="date" />
                        <p v-if="errors.end_date" class="mt-1 text-xs text-red-500">{{ errors.end_date }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Bobot KPI (%)</label>
                        <Input v-model="form.weight" type="number" min="0" max="100" step="0.01" placeholder="10" />
                        <p v-if="errors.weight" class="mt-1 text-xs text-red-500">{{ errors.weight }}</p>
                    </div>
                    <div>
                        <label class="form-label">Target Nilai</label>
                        <Input v-model="form.target_value" type="number" step="0.01" placeholder="100" />
                    </div>
                </div>

                <div>
                    <label class="form-label">Status Awal</label>
                    <select v-model="form.status" class="form-input">
                        <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                    </select>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3 border-t border-slate-100 pt-4">
                <button class="btn-secondary" :disabled="submitting" @click="showForm = false">Batal</button>
                <button class="btn-primary" :disabled="submitting" @click="submit">
                    {{ submitting ? 'Menyimpan...' : editMode ? 'Perbarui Tugas' : 'Tetapkan Tugas' }}
                </button>
            </div>
        </Dialog>

        <!-- Delete Dialog -->
        <Dialog v-model:open="deleteState.open" title="Hapus Tugas" class="max-w-md">
            <p class="mt-3 text-sm text-slate-600">
                Hapus tugas <strong>{{ deleteState.title }}</strong>? Tindakan ini tidak dapat dibatalkan.
            </p>
            <div class="mt-6 flex justify-end gap-3">
                <button class="btn-secondary" @click="deleteState.open = false">Batal</button>
                <button class="btn-danger" @click="confirmDelete">Ya, Hapus</button>
            </div>
        </Dialog>
    </AppLayout>
</template>
