<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import AppLayout from '@/components/layout/AppLayout.vue';
import Dialog from '@/components/ui/Dialog.vue';
import Input from '@/components/ui/Input.vue';
import Textarea from '@/components/ui/Textarea.vue';
import Alert from '@/components/ui/Alert.vue';
import Skeleton from '@/components/ui/Skeleton.vue';
import { ClipboardList, CheckCircle2, Clock, Users } from 'lucide-vue-next';
import { useToast } from '@/composables/useToast';
import { useTaskAssignmentStore } from '@/stores/taskAssignment';
import { useEmployeeStore } from '@/stores/employee';
import { useKpiComponentStore } from '@/stores/kpiComponent';

const store = useTaskAssignmentStore();
const empStore = useEmployeeStore();
const kpiComponentStore = useKpiComponentStore();
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
    kpi_component_id: '',
    weight: '',
    target_value: '',
    status: 'Pending',
});

const form = reactive(emptyForm());
const errors = reactive({});

const tasks = computed(() => store.assignedTasks);
const selectedKpiComponent = computed(() =>
    kpiComponentStore.components.find((component) => String(component.id) === String(form.kpi_component_id))
);

const summary = computed(() => ({
    total: store.pagination.total,
    done: tasks.value.filter((t) => t.status === 'Selesai').length,
    inProgress: tasks.value.filter((t) => t.status === 'Dalam Proses').length,
    pending: tasks.value.filter((t) => t.status === 'Pending').length,
}));

const summaryCards = [
    { key: 'total',      label: 'Total Tugas',   icon: ClipboardList, color: 'blue' },
    { key: 'done',       label: 'Selesai',        icon: CheckCircle2,  color: 'emerald' },
    { key: 'inProgress', label: 'Dalam Proses',   icon: Clock,         color: 'amber' },
    { key: 'pending',    label: 'Pending',         icon: Users,         color: 'violet' },
];

const colorSchemes = {
    blue:    { border: 'border-blue-200',    icon: 'text-blue-500 bg-blue-50',    value: 'text-blue-700' },
    emerald: { border: 'border-emerald-200', icon: 'text-emerald-500 bg-emerald-50', value: 'text-emerald-700' },
    amber:   { border: 'border-amber-200',   icon: 'text-amber-500 bg-amber-50',  value: 'text-amber-700' },
    violet:  { border: 'border-violet-200',  icon: 'text-violet-500 bg-violet-50', value: 'text-violet-700' },
};

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
        kpiComponentStore.fetchComponents({ is_active: true }),
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
        kpi_component_id: task.kpi_component_id ?? task.kpi_component?.id ?? '',
        weight:       task.weight ?? '',
        target_value: task.target_value ?? '',
        status:       task.status ?? 'Pending',
    });
    showForm.value = true;
}

function applyKpiComponentDefaults() {
    const component = selectedKpiComponent.value;

    if (!component) return;

    form.jenis_pekerjaan = component.objectives || 'Task KPI';
    form.weight = component.bobot !== null && component.bobot !== undefined
        ? Number(component.bobot) * 100
        : form.weight;
    form.target_value = component.target ?? form.target_value;
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
            kpi_component_id: form.kpi_component_id ? Number(form.kpi_component_id) : null,
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

        <!-- Hero -->
        <section class="page-hero">
            <div>
                <div class="page-hero-meta">HR Panel · Manajemen Tugas</div>
                <h2 class="mt-4 text-2xl font-bold leading-tight md:text-3xl">Penugasan Tugas</h2>
                <p class="mt-2 max-w-xl text-sm leading-6 text-white/78">
                    Tetapkan tugas khusus kepada pegawai dengan rentang waktu dan bobot KPI tersendiri.
                </p>
            </div>
        </section>

        <!-- Summary Cards -->
        <div class="grid grid-cols-2 gap-6 lg:grid-cols-4">
            <div
                v-for="card in summaryCards"
                :key="card.key"
                :class="['group relative overflow-hidden rounded-2xl border bg-white p-6 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md', colorSchemes[card.color].border]"
            >
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-medium tracking-wide text-slate-400 uppercase">{{ card.label }}</p>
                        <p :class="['mt-2 tabular-nums tracking-tight text-3xl font-bold', colorSchemes[card.color].value]">
                            {{ summary[card.key] }}
                        </p>
                    </div>
                    <div :class="['flex h-10 w-10 items-center justify-center rounded-xl', colorSchemes[card.color].icon]">
                        <component :is="card.icon" class="h-5 w-5" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Task List -->
        <section class="dashboard-panel overflow-hidden">
            <div class="border-b border-slate-200 px-6 py-5">
                <p class="section-heading">Daftar Tugas</p>
                <h3 class="mt-1 text-lg font-bold text-slate-900">{{ store.pagination.total }} tugas terdaftar</h3>
            </div>

            <div class="p-6">
                <template v-if="store.isLoading">
                    <div class="space-y-3">
                        <Skeleton v-for="i in 6" :key="i" class="h-16 rounded-2xl" />
                    </div>
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

                <div v-else class="py-14 text-center">
                    <svg class="mx-auto mb-3 h-10 w-10 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2m-6 9 2 2 4-4"/>
                    </svg>
                    <p class="text-sm text-slate-400">Belum ada tugas yang ditetapkan.</p>
                </div>
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
                    <label class="form-label">Komponen KPI</label>
                    <select v-model="form.kpi_component_id" class="form-input" @change="applyKpiComponentDefaults">
                        <option value="">Tanpa komponen KPI</option>
                        <option
                            v-for="component in kpiComponentStore.components"
                            :key="component.id"
                            :value="component.id"
                        >
                            {{ component.objectives }} - {{ component.jabatan || 'Semua Jabatan' }}
                        </option>
                    </select>
                    <p class="mt-1 text-[11px] text-slate-400">
                        Jika dipilih, bobot dan target akan mengikuti komponen KPI dan tetap bisa disesuaikan.
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
