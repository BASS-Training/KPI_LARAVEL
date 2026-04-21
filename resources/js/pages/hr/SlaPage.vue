<script setup>
import { computed, nextTick, onMounted, reactive, ref, watch } from 'vue';
import AppLayout from '@/components/layout/AppLayout.vue';
import Dialog from '@/components/ui/Dialog.vue';
import Input from '@/components/ui/Input.vue';
import Textarea from '@/components/ui/Textarea.vue';
import Alert from '@/components/ui/Alert.vue';
import PageHeader from '@/components/shared/PageHeader.vue';
import EmptyState from '@/components/shared/EmptyState.vue';
import LoadingRows from '@/components/shared/LoadingRows.vue';
import { useToast } from '@/composables/useToast';
import { useSlaStore } from '@/stores/sla';
import { useDepartmentStore } from '@/stores/department';
import { usePositionStore } from '@/stores/position';

const store     = useSlaStore();
const deptStore = useDepartmentStore();
const posStore  = usePositionStore();
const toast     = useToast();

const slas           = computed(() => store.slas);
const showForm       = ref(false);
const editMode       = ref(false);
const selectedId     = ref(null);
const submitting     = ref(false);
const formError      = ref('');
const deleteState    = ref({ open: false, id: null, name: '' });
const syncingHierarchy = ref(false);

const emptyForm = () => ({
    nama_pekerjaan: '',
    department_id:  null,
    jabatan:        '',
    position_id:    null,
    durasi_jam:     '',
    keterangan:     '',
});

const form   = reactive(emptyForm());
const errors = reactive({});

const filteredPositions = computed(() =>
    form.department_id
        ? posStore.positions.filter(p => p.department_id === form.department_id)
        : posStore.positions
);

watch(() => form.department_id, () => {
    if (syncingHierarchy.value) return;
    form.position_id = null;
    form.jabatan     = '';
});

watch(() => form.position_id, (id) => {
    if (syncingHierarchy.value) return;
    const position = posStore.findById(id);
    form.jabatan = position?.nama ?? '';
});

onMounted(() => {
    store.fetchSla();
    deptStore.fetchDepartments();
    posStore.fetchPositions();
});

function resetForm() {
    Object.assign(form, emptyForm());
    formError.value = '';
    Object.keys(errors).forEach(k => { errors[k] = ''; });
}

function openCreate() {
    editMode.value   = false;
    selectedId.value = null;
    resetForm();
    showForm.value   = true;
}

function openEdit(item) {
    syncingHierarchy.value = true;
    editMode.value   = true;
    selectedId.value = item.id;
    resetForm();

    const selectedPosition   = item.position_id ? posStore.findById(item.position_id) : null;
    const selectedDepartment = selectedPosition?.department_id
        ? deptStore.findById(selectedPosition.department_id)
        : null;

    Object.assign(form, {
        nama_pekerjaan: item.nama_pekerjaan ?? '',
        department_id:  selectedDepartment?.id ?? null,
        jabatan:        item.jabatan ?? '',
        position_id:    item.position_id ?? null,
        durasi_jam:     item.durasi_jam ?? '',
        keterangan:     item.keterangan ?? '',
    });
    nextTick(() => { syncingHierarchy.value = false; });
    showForm.value = true;
}

function validate() {
    Object.assign(errors, { nama_pekerjaan: '', jabatan: '', durasi_jam: '' });
    let valid = true;
    if (!form.nama_pekerjaan)                       { errors.nama_pekerjaan = 'Nama pekerjaan wajib diisi.'; valid = false; }
    if (!form.jabatan)                              { errors.jabatan = 'Jabatan wajib diisi.'; valid = false; }
    if (!form.durasi_jam || Number(form.durasi_jam) <= 0) { errors.durasi_jam = 'Durasi jam harus lebih dari 0.'; valid = false; }
    return valid;
}

async function submit() {
    if (!validate()) return;
    submitting.value = true;
    formError.value  = '';
    try {
        const payload = {
            nama_pekerjaan: form.nama_pekerjaan,
            jabatan:        form.jabatan,
            position_id:    form.position_id,
            durasi_jam:     Number(form.durasi_jam),
            keterangan:     form.keterangan,
        };
        if (editMode.value && selectedId.value) {
            await store.updateSla(selectedId.value, payload);
            toast.success('SLA berhasil diperbarui.');
        } else {
            await store.createSla(payload);
            toast.success('SLA berhasil ditambahkan.');
        }
        showForm.value = false;
        await store.fetchSla();
    } catch (err) {
        formError.value = err.response?.data?.message || 'Gagal menyimpan SLA.';
    } finally {
        submitting.value = false;
    }
}

async function confirmDelete() {
    try {
        await store.deleteSla(deleteState.value.id);
        toast.success('SLA berhasil dihapus.');
        deleteState.value.open = false;
    } catch (err) {
        toast.error(err.response?.data?.message || 'Gagal menghapus SLA.');
    }
}
</script>

<template>
    <AppLayout>
        <template #topbar-actions>
            <button class="btn-primary" @click="openCreate">Tambah SLA</button>
        </template>

        <PageHeader
            eyebrow="HR Panel"
            title="SLA Pekerjaan"
            description="Kelola standar durasi kerja berdasarkan jabatan dan jenis pekerjaan untuk memantau keterlambatan."
        >
            <template #actions>
                <button class="btn-primary" @click="openCreate">Tambah SLA</button>
            </template>
        </PageHeader>

        <section class="dashboard-panel overflow-hidden">
            <div class="border-b border-slate-200 px-6 py-5">
                <p class="section-heading">Daftar SLA</p>
                <h3 class="mt-1 text-lg font-bold text-slate-900">{{ slas.length }} SLA terdaftar</h3>
            </div>

            <div class="p-6">
                <template v-if="store.isLoading">
                    <LoadingRows :rows="5" height="h-14" />
                </template>
                <template v-else-if="slas.length">
                    <div class="space-y-3">
                        <div v-for="item in slas" :key="item.id" class="data-row">
                            <div class="min-w-0 flex-1">
                                <div class="truncate text-sm font-semibold text-slate-900">{{ item.nama_pekerjaan }}</div>
                                <div class="mt-0.5 truncate text-xs text-slate-500">
                                    {{ item.jabatan }} · {{ item.durasi_jam }} jam
                                </div>
                            </div>
                            <div class="hidden min-w-[260px] truncate text-sm text-slate-600 md:block">{{ item.keterangan || '-' }}</div>
                            <div class="flex items-center gap-2">
                                <button class="btn-secondary !px-3 !py-2 text-xs" @click="openEdit(item)">Edit</button>
                                <button class="btn-danger !px-3 !py-2 text-xs" @click="deleteState = { open: true, id: item.id, name: item.nama_pekerjaan }">Hapus</button>
                            </div>
                        </div>
                    </div>
                </template>
                <EmptyState
                    v-else
                    title="Belum ada data SLA"
                    description="Tambahkan SLA agar sistem dapat menilai deadline dan keterlambatan pekerjaan secara konsisten."
                    action-label="Tambah SLA"
                    @action="openCreate"
                />
            </div>
        </section>

        <Dialog v-model:open="showForm" :title="editMode ? 'Edit SLA' : 'Tambah SLA'" class="max-w-2xl">
            <Alert v-if="formError" variant="danger" class="mb-4">{{ formError }}</Alert>

            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label class="form-label">Nama Pekerjaan <span class="text-red-500">*</span></label>
                    <Input v-model="form.nama_pekerjaan" placeholder="Contoh: Pembuatan Invoice" />
                    <p v-if="errors.nama_pekerjaan" class="mt-1 text-xs text-red-500">{{ errors.nama_pekerjaan }}</p>
                </div>

                <div>
                    <label class="form-label">Departemen</label>
                    <select v-model="form.department_id" class="form-input">
                        <option :value="null">— Pilih Departemen —</option>
                        <option v-for="d in deptStore.departments" :key="d.id" :value="d.id">{{ d.nama }}</option>
                    </select>
                </div>

                <div>
                    <label class="form-label">Jabatan <span class="text-red-500">*</span></label>
                    <select v-model="form.position_id" class="form-input">
                        <option :value="null">— Pilih Jabatan —</option>
                        <option v-for="p in filteredPositions" :key="p.id" :value="p.id">{{ p.nama }}</option>
                    </select>
                    <p v-if="errors.jabatan" class="mt-1 text-xs text-red-500">{{ errors.jabatan }}</p>
                </div>

                <div>
                    <label class="form-label">Durasi Jam <span class="text-red-500">*</span></label>
                    <Input v-model="form.durasi_jam" type="number" min="1" placeholder="Contoh: 8" />
                    <p class="mt-1 text-[11px] text-slate-400">Waktu maksimal penyelesaian dalam jam kerja.</p>
                    <p v-if="errors.durasi_jam" class="mt-1 text-xs text-red-500">{{ errors.durasi_jam }}</p>
                </div>

                <div class="md:col-span-2">
                    <label class="form-label">Keterangan</label>
                    <Textarea v-model="form.keterangan" rows="3" placeholder="Keterangan atau syarat SLA..." />
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <button class="btn-secondary" :disabled="submitting" @click="showForm = false">Batal</button>
                <button class="btn-primary" :disabled="submitting" @click="submit">
                    {{ submitting ? 'Menyimpan...' : editMode ? 'Perbarui SLA' : 'Simpan SLA' }}
                </button>
            </div>
        </Dialog>

        <Dialog v-model:open="deleteState.open" title="Hapus SLA" class="max-w-lg">
            <p class="mt-3 text-sm text-slate-600">Hapus <strong>{{ deleteState.name }}</strong> dari daftar SLA?</p>
            <div class="mt-6 flex justify-end gap-3">
                <button class="btn-secondary" @click="deleteState.open = false">Batal</button>
                <button class="btn-danger" @click="confirmDelete">Ya, Hapus</button>
            </div>
        </Dialog>
    </AppLayout>
</template>
