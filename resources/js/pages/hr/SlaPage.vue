<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import AppLayout from '@/components/layout/AppLayout.vue';
import Dialog from '@/components/ui/Dialog.vue';
import Input from '@/components/ui/Input.vue';
import Textarea from '@/components/ui/Textarea.vue';
import Alert from '@/components/ui/Alert.vue';
import Skeleton from '@/components/ui/Skeleton.vue';
import { useToast } from '@/composables/useToast';
import { useSlaStore } from '@/stores/sla';
import { usePositionStore } from '@/stores/position';

const store    = useSlaStore();
const posStore = usePositionStore();
const toast    = useToast();

const slas       = computed(() => store.slas);
const showForm   = ref(false);
const editMode   = ref(false);
const selectedId = ref(null);
const submitting = ref(false);
const formError  = ref('');
const deleteState = ref({ open: false, id: null, name: '' });

const emptyForm = () => ({
    nama_pekerjaan: '',
    jabatan:        '',
    position_id:    null,
    durasi_jam:     '',
    keterangan:     '',
});

const form   = reactive(emptyForm());
const errors = reactive({});

// Auto-fill jabatan string when position is selected
watch(() => form.position_id, (id) => {
    const pos = posStore.findById(id);
    if (pos) form.jabatan = pos.nama;
});

onMounted(() => {
    store.fetchSla();
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
        nama_pekerjaan: item.nama_pekerjaan ?? '',
        jabatan:        item.jabatan ?? '',
        position_id:    item.position_id ?? null,
        durasi_jam:     item.durasi_jam ?? '',
        keterangan:     item.keterangan ?? '',
    });
    showForm.value = true;
}

function validate() {
    Object.assign(errors, { nama_pekerjaan: '', jabatan: '', durasi_jam: '' });
    let valid = true;
    if (!form.nama_pekerjaan) { errors.nama_pekerjaan = 'Nama pekerjaan wajib diisi.'; valid = false; }
    if (!form.jabatan)        { errors.jabatan = 'Jabatan wajib diisi.'; valid = false; }
    if (!form.durasi_jam || Number(form.durasi_jam) <= 0) { errors.durasi_jam = 'Durasi jam harus lebih dari 0.'; valid = false; }
    return valid;
}

async function submit() {
    if (!validate()) return;
    submitting.value = true;
    formError.value  = '';
    try {
        const payload = { ...form, durasi_jam: Number(form.durasi_jam) };
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

const levelBadge = {
    staff:      'badge-info',
    supervisor: 'badge-warning',
    manager:    'badge-success',
    director:   'bg-purple-100 text-purple-700 rounded-full px-2 py-0.5 text-[10px] font-semibold',
    executive:  'bg-rose-100 text-rose-700 rounded-full px-2 py-0.5 text-[10px] font-semibold',
};
</script>

<template>
    <AppLayout>
        <template #topbar-actions>
            <button class="btn-primary" @click="openCreate">Tambah SLA</button>
        </template>

        <section class="page-hero">
            <div>
                <div class="page-hero-meta">HR Panel</div>
                <h2 class="mt-4 text-2xl font-bold leading-tight md:text-3xl">SLA Pekerjaan</h2>
                <p class="mt-2 max-w-xl text-sm leading-6 text-white/78">
                    Kelola standar durasi kerja berdasarkan jabatan dan jenis pekerjaan.
                </p>
            </div>
        </section>

        <section class="dashboard-panel overflow-hidden">
            <div class="border-b border-slate-200 px-6 py-5">
                <p class="section-heading">Daftar SLA</p>
                <h3 class="mt-1 text-lg font-bold text-slate-900">{{ slas.length }} SLA terdaftar</h3>
            </div>

            <div class="p-6">
                <template v-if="store.isLoading">
                    <div class="space-y-3">
                        <Skeleton v-for="i in 5" :key="i" class="h-14 rounded-2xl" />
                    </div>
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
                <div v-else class="py-14 text-center text-sm text-slate-400">
                    Belum ada data SLA.
                </div>
            </div>
        </section>

        <!-- Form Dialog -->
        <Dialog v-model:open="showForm" :title="editMode ? 'Edit SLA' : 'Tambah SLA'" class="max-w-2xl">
            <Alert v-if="formError" variant="danger" class="mb-4">{{ formError }}</Alert>

            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                <!-- Nama Pekerjaan -->
                <div class="md:col-span-2">
                    <label class="form-label">Nama Pekerjaan <span class="text-red-500">*</span></label>
                    <Input v-model="form.nama_pekerjaan" placeholder="Contoh: Pembuatan Invoice" />
                    <p v-if="errors.nama_pekerjaan" class="mt-1 text-xs text-red-500">{{ errors.nama_pekerjaan }}</p>
                </div>

                <!-- Jabatan dropdown -->
                <div>
                    <label class="form-label">Jabatan <span class="text-red-500">*</span></label>
                    <select v-model="form.position_id" class="form-input">
                        <option :value="null">— Pilih Jabatan —</option>
                        <option v-for="p in posStore.positions" :key="p.id" :value="p.id">
                            {{ p.nama }}
                        </option>
                    </select>
                    <Input
                        v-model="form.jabatan"
                        class="mt-1.5"
                        placeholder="Atau ketik jabatan langsung..."
                        :class="form.position_id ? 'opacity-50' : ''"
                    />
                    <p v-if="errors.jabatan" class="mt-1 text-xs text-red-500">{{ errors.jabatan }}</p>
                </div>

                <!-- Durasi Jam -->
                <div>
                    <label class="form-label">Durasi Jam <span class="text-red-500">*</span></label>
                    <Input v-model="form.durasi_jam" type="number" min="1" placeholder="Contoh: 8" />
                    <p class="mt-1 text-[11px] text-slate-400">Waktu maksimal penyelesaian (dalam jam kerja)</p>
                    <p v-if="errors.durasi_jam" class="mt-1 text-xs text-red-500">{{ errors.durasi_jam }}</p>
                </div>

                <!-- Keterangan -->
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

        <!-- Delete Dialog -->
        <Dialog v-model:open="deleteState.open" title="Hapus SLA" class="max-w-lg">
            <p class="mt-3 text-sm text-slate-600">Hapus <strong>{{ deleteState.name }}</strong> dari daftar SLA?</p>
            <div class="mt-6 flex justify-end gap-3">
                <button class="btn-secondary" @click="deleteState.open = false">Batal</button>
                <button class="btn-danger" @click="confirmDelete">Ya, Hapus</button>
            </div>
        </Dialog>
    </AppLayout>
</template>
