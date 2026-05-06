<script setup>
import { computed, nextTick, onMounted, reactive, ref, watch } from 'vue';
import AppLayout from '@/components/layout/AppLayout.vue';
import Dialog from '@/components/ui/Dialog.vue';
import Input from '@/components/ui/Input.vue';
import Alert from '@/components/ui/Alert.vue';
import Skeleton from '@/components/ui/Skeleton.vue';
import { useToast } from '@/composables/useToast';
import { usePositionStore } from '@/stores/position';
import { useDepartmentStore } from '@/stores/department';
import { useAuthStore } from '@/stores/auth';

const posStore  = usePositionStore();
const deptStore = useDepartmentStore();
const auth      = useAuthStore();
const toast     = useToast();

const syncingHierarchy = ref(false);
const showForm    = ref(false);
const editMode    = ref(false);
const selectedId  = ref(null);
const submitting  = ref(false);
const formError   = ref('');
const deleteState = ref({ open: false, id: null, name: '' });
const deleting    = ref(false);

const filterDept = ref('');
const search     = ref('');
const activeTenantName = computed(() => auth.activeTenant?.tenant_name || 'Tenant aktif');

const filteredPositions = computed(() => {
    let list = posStore.positions;
    if (filterDept.value) {
        list = list.filter(p => p.department_id === filterDept.value);
    }
    if (search.value.trim()) {
        const q = search.value.toLowerCase();
        list = list.filter(p => p.nama?.toLowerCase().includes(q) || p.kode?.toLowerCase().includes(q));
    }
    return list;
});

const emptyForm = () => ({
    nama:          '',
    kode:          '',
    department_id: null,
    level:         '',
    is_active:     true,
});

const form   = reactive(emptyForm());
const errors = reactive({});

onMounted(async () => {
    if (!auth.myTenants?.length) {
        await auth.fetchMyTenants().catch(() => {});
    }

    await Promise.all([
        posStore.fetchPositions(),
        deptStore.fetchDepartments(),
    ]);
});

watch(() => auth.activeTenantId, async (tenantId, oldTenantId) => {
    if (!tenantId || tenantId === oldTenantId) return;

    await Promise.all([
        posStore.fetchPositions(),
        deptStore.fetchDepartments(),
    ]);
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

function openEdit(pos) {
    syncingHierarchy.value = true;
    editMode.value   = true;
    selectedId.value = pos.id;
    resetForm();
    Object.assign(form, {
        nama:          pos.nama ?? '',
        kode:          pos.kode ?? '',
        department_id: pos.department_id ?? null,
        level:         pos.level ?? '',
        is_active:     Boolean(pos.is_active),
    });
    nextTick(() => { syncingHierarchy.value = false; });
    showForm.value = true;
}

function validate() {
    Object.assign(errors, { nama: '', department_id: '' });
    let valid = true;
    if (!form.nama.trim())      { errors.nama = 'Nama jabatan wajib diisi.'; valid = false; }
    if (!form.department_id)    { errors.department_id = 'Departemen wajib dipilih.'; valid = false; }
    return valid;
}

async function submit() {
    if (!validate()) return;
    submitting.value = true;
    formError.value  = '';
    try {
        const payload = {
            nama:          form.nama.trim(),
            kode:          form.kode.trim() || null,
            department_id: form.department_id,
            level:         form.level.trim() || null,
            is_active:     form.is_active,
        };
        if (editMode.value) {
            await posStore.updatePosition(selectedId.value, payload);
            toast.success('Jabatan berhasil diperbarui.');
        } else {
            await posStore.createPosition(payload);
            toast.success('Jabatan berhasil ditambahkan.');
        }
        showForm.value = false;
        await posStore.fetchPositions();
    } catch (err) {
        formError.value = err.response?.data?.message || 'Gagal menyimpan jabatan.';
    } finally {
        submitting.value = false;
    }
}

async function confirmDelete() {
    deleting.value = true;
    try {
        await posStore.deletePosition(deleteState.value.id);
        toast.success('Jabatan berhasil dihapus.');
        deleteState.value.open = false;
    } catch (err) {
        toast.error(err.response?.data?.message || 'Gagal menghapus jabatan.');
    } finally {
        deleting.value = false;
    }
}
</script>

<template>
    <AppLayout>
        <template #topbar-actions>
            <button class="btn-primary" @click="openCreate">+ Tambah Jabatan</button>
        </template>

        <section class="page-hero">
            <div>
                <div class="page-hero-meta">HR Panel</div>
                <h2 class="mt-4 text-2xl font-bold leading-tight md:text-3xl">Manajemen Jabatan</h2>
                <p class="mt-2 max-w-xl text-sm leading-6 text-white/78">
                    Kelola daftar jabatan berdasarkan departemen untuk tenant yang sedang aktif.
                </p>
                <p class="mt-3 inline-flex rounded-full bg-white/14 px-3 py-1 text-xs font-semibold tracking-wide text-white/90">
                    Tenant aktif: {{ activeTenantName }}
                </p>
            </div>
        </section>

        <!-- Filters -->
        <div class="flex flex-wrap items-center gap-3">
            <input v-model="search" type="search" placeholder="Cari jabatan..." class="form-input !w-auto min-w-[180px]" />
            <select v-model="filterDept" class="form-input !w-auto min-w-[160px]">
                <option value="">Semua Departemen</option>
                <option v-for="d in deptStore.departments" :key="d.id" :value="d.id">{{ d.nama }}</option>
            </select>
            <div class="ml-auto text-xs text-slate-400">{{ filteredPositions.length }} jabatan</div>
        </div>

        <!-- Table -->
        <section class="dashboard-panel overflow-hidden">
            <div class="border-b border-slate-200 px-6 py-5">
                <p class="section-heading">Daftar Jabatan</p>
                <h3 class="mt-1 text-lg font-bold text-slate-900">{{ posStore.positions.length }} jabatan terdaftar</h3>
            </div>

            <div class="p-6">
                <template v-if="posStore.isLoading">
                    <div class="space-y-3">
                        <Skeleton v-for="i in 6" :key="i" class="h-14 rounded-2xl" />
                    </div>
                </template>

                <template v-else-if="filteredPositions.length">
                    <div class="space-y-2">
                        <div v-for="pos in filteredPositions" :key="pos.id" class="data-row">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-semibold text-slate-900">{{ pos.nama }}</span>
                                    <span v-if="pos.kode" class="badge-neutral text-[10px]">{{ pos.kode }}</span>
                                    <span v-if="!pos.is_active" class="badge-warning text-[10px]">Nonaktif</span>
                                </div>
                                <div class="mt-0.5 flex flex-wrap gap-x-2 text-xs text-slate-500">
                                    <span v-if="pos.department?.nama" class="badge-neutral !text-[10px]">{{ pos.department.nama }}</span>
                                    <span v-if="pos.level">· Level: {{ pos.level }}</span>
                                </div>
                            </div>
                            <div class="flex shrink-0 items-center gap-2">
                                <button class="btn-secondary !px-3 !py-1.5 text-xs" @click="openEdit(pos)">Edit</button>
                                <button class="btn-danger !px-3 !py-1.5 text-xs" @click="deleteState = { open: true, id: pos.id, name: pos.nama }">Hapus</button>
                            </div>
                        </div>
                    </div>
                </template>

                <div v-else class="py-14 text-center text-sm text-slate-400">
                    Belum ada jabatan.
                </div>
            </div>
        </section>

        <!-- Form Dialog -->
        <Dialog v-model:open="showForm" :title="editMode ? 'Edit Jabatan' : 'Tambah Jabatan'" class="w-full max-w-sm sm:max-w-lg">
            <Alert v-if="formError" variant="danger" class="mb-4">{{ formError }}</Alert>

            <div class="mt-4 space-y-4">
                <div>
                    <label class="form-label">Nama Jabatan <span class="text-red-500">*</span></label>
                    <Input v-model="form.nama" placeholder="Contoh: Software Engineer" />
                    <p v-if="errors.nama" class="mt-1 text-xs text-red-500">{{ errors.nama }}</p>
                </div>

                <div>
                    <label class="form-label">Kode Jabatan</label>
                    <Input v-model="form.kode" placeholder="Contoh: SE-01" />
                </div>

                <div>
                    <label class="form-label">Departemen <span class="text-red-500">*</span></label>
                    <select v-model="form.department_id" class="form-input">
                        <option :value="null">— Pilih Departemen —</option>
                        <option v-for="d in deptStore.departments" :key="d.id" :value="d.id">{{ d.nama }}</option>
                    </select>
                    <p v-if="errors.department_id" class="mt-1 text-xs text-red-500">{{ errors.department_id }}</p>
                </div>

                <div>
                    <label class="form-label">Level / Grade</label>
                    <Input v-model="form.level" placeholder="Contoh: Senior, Junior, Manager" />
                </div>

                <div>
                    <label class="form-label">Status</label>
                    <select v-model="form.is_active" class="form-input">
                        <option :value="true">Aktif</option>
                        <option :value="false">Nonaktif</option>
                    </select>
                </div>
            </div>

            <div class="flex flex-col-reverse gap-2 border-t border-slate-100 pt-4 sm:flex-row sm:justify-end sm:gap-3">
                <button class="btn-secondary w-full sm:w-auto" :disabled="submitting" @click="showForm = false">Batal</button>
                <button class="btn-primary w-full sm:w-auto" :disabled="submitting" @click="submit">
                    {{ submitting ? 'Menyimpan...' : editMode ? 'Perbarui Jabatan' : 'Simpan Jabatan' }}
                </button>
            </div>
        </Dialog>

        <!-- Delete Dialog -->
        <Dialog v-model:open="deleteState.open" title="Hapus Jabatan" class="w-full max-w-sm sm:max-w-lg">
            <p class="mt-3 text-sm text-slate-600">
                Hapus jabatan <strong>{{ deleteState.name }}</strong>?
                Pastikan tidak ada pegawai atau komponen KPI yang menggunakan jabatan ini.
            </p>
            <div class="flex flex-col-reverse gap-2 border-t border-slate-100 pt-4 sm:flex-row sm:justify-end sm:gap-3">
                <button class="btn-secondary w-full sm:w-auto" :disabled="deleting" @click="deleteState.open = false">Batal</button>
                <button class="btn-danger w-full sm:w-auto" :disabled="deleting" @click="confirmDelete">
                    {{ deleting ? 'Menghapus...' : 'Ya, Hapus' }}
                </button>
            </div>
        </Dialog>
    </AppLayout>
</template>
