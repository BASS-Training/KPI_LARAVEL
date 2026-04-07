<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import AppLayout from '@/components/layout/AppLayout.vue';
import Dialog from '@/components/ui/Dialog.vue';
import Input from '@/components/ui/Input.vue';
import Textarea from '@/components/ui/Textarea.vue';
import Alert from '@/components/ui/Alert.vue';
import Skeleton from '@/components/ui/Skeleton.vue';
import { useToast } from '@/composables/useToast';
import { useKpiComponentStore } from '@/stores/kpiComponent';
import { useDivisionStore } from '@/stores/division';
import { useDepartmentStore } from '@/stores/department';
import { usePositionStore } from '@/stores/position';

const store     = useKpiComponentStore();
const divStore  = useDivisionStore();
const deptStore = useDepartmentStore();
const posStore  = usePositionStore();
const toast     = useToast();

const components = computed(() => store.components);
const showForm   = ref(false);
const editMode   = ref(false);
const selectedId = ref(null);
const submitting = ref(false);
const formError  = ref('');
const deleteState = ref({ open: false, id: null, name: '' });

const emptyForm = () => ({
    jabatan:     '',
    division_id: null,
    position_id: null,
    objectives:  '',
    strategy:    '',
    bobot:       0,
    target:      '',
    satuan:      '',
    tipe:        'achievement',
    kpi_type:    null,
    period:      'monthly',
    catatan:     '',
    is_active:   true,
});

const form   = reactive(emptyForm());
const errors = reactive({});

// Cascade: position changes → auto-fill jabatan string
const filteredDepts     = computed(() =>
    form.division_id
        ? deptStore.departments.filter(d => d.division_id === form.division_id)
        : deptStore.departments
);
const filteredPositions = computed(() =>
    form.division_id
        ? posStore.positions.filter(p => {
            const dept = deptStore.findById(p.department_id);
            return dept?.division_id === form.division_id;
        })
        : posStore.positions
);

watch(() => form.position_id, (id) => {
    const pos = posStore.findById(id);
    if (pos) form.jabatan = pos.nama;
});

onMounted(() => {
    store.fetchComponents();
    divStore.fetchDivisions();
    deptStore.fetchDepartments();
    posStore.fetchPositions();
});

function resetForm() {
    Object.assign(form, emptyForm());
    formError.value = '';
    Object.keys(errors).forEach((key) => { errors[key] = ''; });
}

function openCreate() {
    editMode.value  = false;
    selectedId.value = null;
    resetForm();
    showForm.value  = true;
}

function openEdit(item) {
    editMode.value  = true;
    selectedId.value = item.id;
    resetForm();
    Object.assign(form, {
        jabatan:     item.jabatan ?? '',
        division_id: item.division_id ?? null,
        position_id: item.position_id ?? null,
        objectives:  item.objectives ?? '',
        strategy:    item.strategy ?? '',
        bobot:       item.bobot ?? 0,
        target:      item.target ?? '',
        satuan:      item.satuan ?? '',
        tipe:        item.tipe ?? 'achievement',
        kpi_type:    item.kpi_type ?? null,
        period:      item.period ?? 'monthly',
        catatan:     item.catatan ?? '',
        is_active:   Boolean(item.is_active),
    });
    showForm.value = true;
}

function validate() {
    Object.assign(errors, { jabatan: '', objectives: '', strategy: '', bobot: '', tipe: '' });
    let valid = true;
    if (!form.jabatan) { errors.jabatan = 'Jabatan wajib diisi.'; valid = false; }
    if (!form.objectives) { errors.objectives = 'Objective wajib diisi.'; valid = false; }
    if (!form.strategy) { errors.strategy = 'Strategy wajib diisi.'; valid = false; }
    if (Number(form.bobot) < 0 || Number(form.bobot) > 1) { errors.bobot = 'Bobot harus antara 0 sampai 1.'; valid = false; }
    if (!form.tipe) { errors.tipe = 'Tipe wajib dipilih.'; valid = false; }
    return valid;
}

async function submit() {
    if (!validate()) return;
    submitting.value = true;
    formError.value  = '';
    try {
        const payload = {
            ...form,
            bobot:  Number(form.bobot),
            target: form.target === '' ? null : Number(form.target),
        };

        if (editMode.value && selectedId.value) {
            await store.updateComponent(selectedId.value, payload);
            toast.success('Komponen KPI berhasil diperbarui.');
        } else {
            await store.createComponent(payload);
            toast.success('Komponen KPI berhasil ditambahkan.');
        }

        showForm.value = false;
        await store.fetchComponents();
    } catch (err) {
        formError.value = err.response?.data?.message || 'Gagal menyimpan komponen KPI.';
    } finally {
        submitting.value = false;
    }
}

async function confirmDelete() {
    try {
        await store.deleteComponent(deleteState.value.id);
        toast.success('Komponen KPI berhasil dihapus.');
        deleteState.value.open = false;
    } catch (err) {
        toast.error(err.response?.data?.message || 'Gagal menghapus komponen KPI.');
    }
}

const tipeLabels = {
    achievement:   'Achievement',
    csi:           'CSI (Customer Satisfaction Index)',
    zero_delay:    'Zero Delay',
    zero_error:    'Zero Error',
    zero_complaint:'Zero Complaint',
};

const kpiTypeLabels = {
    number:     'Number (Angka)',
    percentage: 'Percentage (%)',
    boolean:    'Boolean (Ya/Tidak)',
};

const periodLabels = {
    daily:   'Harian',
    weekly:  'Mingguan',
    monthly: 'Bulanan',
};
</script>

<template>
    <AppLayout>
        <template #topbar-actions>
            <button class="btn-primary" @click="openCreate">Tambah Komponen</button>
        </template>

        <section class="page-hero">
            <div>
                <div class="page-hero-meta">HR Panel</div>
                <h2 class="mt-4 text-2xl font-bold leading-tight md:text-3xl">Komponen KPI</h2>
                <p class="mt-2 max-w-xl text-sm leading-6 text-white/78">
                    Kelola objective, bobot, target, dan tipe komponen KPI per jabatan dan divisi.
                </p>
            </div>
        </section>

        <section class="dashboard-panel overflow-hidden">
            <div class="border-b border-slate-200 px-6 py-5">
                <p class="section-heading">Daftar Komponen</p>
                <h3 class="mt-1 text-lg font-bold text-slate-900">{{ components.length }} komponen terdaftar</h3>
            </div>

            <div class="p-6">
                <template v-if="store.isLoading">
                    <div class="space-y-3">
                        <Skeleton v-for="i in 6" :key="i" class="h-14 rounded-2xl" />
                    </div>
                </template>
                <template v-else-if="components.length">
                    <div class="space-y-3">
                        <div v-for="item in components" :key="item.id" class="data-row">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="truncate text-sm font-semibold text-slate-900">{{ item.objectives }}</span>
                                    <span v-if="!item.is_active" class="badge-warning text-[10px]">Nonaktif</span>
                                </div>
                                <div class="mt-0.5 truncate text-xs text-slate-500">
                                    {{ item.jabatan }}
                                    <template v-if="item.division_id"> · {{ item.division?.nama ?? '–' }}</template>
                                    · {{ tipeLabels[item.tipe] ?? item.tipe }}
                                    · Bobot {{ item.bobot }}
                                    <template v-if="item.target"> · Target {{ item.target }}{{ item.satuan ? ' ' + item.satuan : '' }}</template>
                                </div>
                            </div>
                            <div class="hidden min-w-[180px] truncate text-sm text-slate-600 md:block">{{ item.strategy }}</div>
                            <div class="flex items-center gap-2">
                                <button class="btn-secondary !px-3 !py-2 text-xs" @click="openEdit(item)">Edit</button>
                                <button class="btn-danger !px-3 !py-2 text-xs" @click="deleteState = { open: true, id: item.id, name: item.objectives }">Hapus</button>
                            </div>
                        </div>
                    </div>
                </template>
                <div v-else class="py-14 text-center text-sm text-slate-400">
                    Belum ada komponen KPI.
                </div>
            </div>
        </section>

        <!-- Form Dialog -->
        <Dialog v-model:open="showForm" :title="editMode ? 'Edit Komponen KPI' : 'Tambah Komponen KPI'" class="max-w-3xl">
            <Alert v-if="formError" variant="danger" class="mb-4">{{ formError }}</Alert>

            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">

                <!-- Divisi -->
                <div>
                    <label class="form-label">Divisi</label>
                    <select v-model="form.division_id" class="form-input">
                        <option :value="null">— Semua Divisi —</option>
                        <option v-for="d in divStore.divisions" :key="d.id" :value="d.id">
                            {{ d.nama }} ({{ d.kode }})
                        </option>
                    </select>
                </div>

                <!-- Jabatan / Position dropdown -->
                <div>
                    <label class="form-label">Jabatan <span class="text-red-500">*</span></label>
                    <select v-model="form.position_id" class="form-input">
                        <option :value="null">— Pilih Jabatan —</option>
                        <option v-for="p in filteredPositions" :key="p.id" :value="p.id">{{ p.nama }}</option>
                    </select>
                    <Input
                        v-model="form.jabatan"
                        class="mt-1.5"
                        placeholder="Atau ketik jabatan langsung..."
                        :class="form.position_id ? 'opacity-50' : ''"
                    />
                    <p v-if="errors.jabatan" class="mt-1 text-xs text-red-500">{{ errors.jabatan }}</p>
                </div>

                <!-- Objectives -->
                <div>
                    <label class="form-label">Objectives <span class="text-red-500">*</span></label>
                    <Input v-model="form.objectives" placeholder="Contoh: System Uptime 99.5%" />
                    <p v-if="errors.objectives" class="mt-1 text-xs text-red-500">{{ errors.objectives }}</p>
                </div>

                <!-- Tipe -->
                <div>
                    <label class="form-label">Tipe KPI <span class="text-red-500">*</span></label>
                    <select v-model="form.tipe" class="form-input">
                        <option v-for="(label, val) in tipeLabels" :key="val" :value="val">{{ label }}</option>
                    </select>
                    <p v-if="errors.tipe" class="mt-1 text-xs text-red-500">{{ errors.tipe }}</p>
                </div>

                <!-- Bobot -->
                <div>
                    <label class="form-label">Bobot <span class="text-red-500">*</span> <span class="text-xs text-slate-400">(0–1)</span></label>
                    <Input v-model="form.bobot" type="number" min="0" max="1" step="0.01" placeholder="0.40" />
                    <p v-if="errors.bobot" class="mt-1 text-xs text-red-500">{{ errors.bobot }}</p>
                </div>

                <!-- Target -->
                <div>
                    <label class="form-label">Target</label>
                    <Input v-model="form.target" type="number" step="0.01" placeholder="Contoh: 95" />
                </div>

                <!-- Satuan -->
                <div>
                    <label class="form-label">Satuan</label>
                    <Input v-model="form.satuan" placeholder="Contoh: %, klien, laporan" />
                </div>

                <!-- KPI Type -->
                <div>
                    <label class="form-label">Tipe Data</label>
                    <select v-model="form.kpi_type" class="form-input">
                        <option :value="null">— Pilih Tipe Data —</option>
                        <option v-for="(label, val) in kpiTypeLabels" :key="val" :value="val">{{ label }}</option>
                    </select>
                </div>

                <!-- Period -->
                <div>
                    <label class="form-label">Periode</label>
                    <select v-model="form.period" class="form-input">
                        <option v-for="(label, val) in periodLabels" :key="val" :value="val">{{ label }}</option>
                    </select>
                </div>

                <!-- Status -->
                <div>
                    <label class="form-label">Status</label>
                    <select v-model="form.is_active" class="form-input">
                        <option :value="true">Aktif</option>
                        <option :value="false">Nonaktif</option>
                    </select>
                </div>

                <!-- Strategy -->
                <div class="md:col-span-2">
                    <label class="form-label">Strategy <span class="text-red-500">*</span></label>
                    <Textarea v-model="form.strategy" rows="3" placeholder="Jelaskan cara mencapai objective ini..." />
                    <p v-if="errors.strategy" class="mt-1 text-xs text-red-500">{{ errors.strategy }}</p>
                </div>

                <!-- Catatan -->
                <div class="md:col-span-2">
                    <label class="form-label">Catatan</label>
                    <Textarea v-model="form.catatan" rows="2" placeholder="Catatan tambahan (opsional)..." />
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <button class="btn-secondary" :disabled="submitting" @click="showForm = false">Batal</button>
                <button class="btn-primary" :disabled="submitting" @click="submit">
                    {{ submitting ? 'Menyimpan...' : editMode ? 'Perbarui Komponen' : 'Simpan Komponen' }}
                </button>
            </div>
        </Dialog>

        <!-- Delete Dialog -->
        <Dialog v-model:open="deleteState.open" title="Hapus Komponen KPI" class="max-w-lg">
            <p class="mt-3 text-sm text-slate-600">Hapus <strong>{{ deleteState.name }}</strong> dari master komponen KPI?</p>
            <div class="mt-6 flex justify-end gap-3">
                <button class="btn-secondary" @click="deleteState.open = false">Batal</button>
                <button class="btn-danger" @click="confirmDelete">Ya, Hapus</button>
            </div>
        </Dialog>
    </AppLayout>
</template>
