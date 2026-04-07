<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useDepartmentStore } from '@/stores/department';
import { useDivisionStore } from '@/stores/division';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/components/layout/AppLayout.vue';
import Dialog from '@/components/ui/Dialog.vue';
import Input from '@/components/ui/Input.vue';
import Skeleton from '@/components/ui/Skeleton.vue';

const store    = useDepartmentStore();
const divStore = useDivisionStore();
const toast    = useToast();

const showForm     = ref(false);
const editMode     = ref(false);
const editId       = ref(null);
const formError    = ref('');
const saving       = ref(false);
const deleteDialog = reactive({ open: false, id: null, nama: '' });

const emptyForm = () => ({ nama: '', kode: '', division_id: null, deskripsi: '', is_active: true });
const form = reactive(emptyForm());

const departments = computed(() => store.departments);

onMounted(() => {
    store.fetchDepartments();
    divStore.fetchDivisions();
});

function openCreate() {
    Object.assign(form, emptyForm());
    editMode.value  = false;
    editId.value    = null;
    formError.value = '';
    showForm.value  = true;
}

function openEdit(dept) {
    Object.assign(form, {
        nama:        dept.nama,
        kode:        dept.kode,
        division_id: dept.division_id ?? null,
        deskripsi:   dept.deskripsi ?? '',
        is_active:   !!dept.is_active,
    });
    editMode.value  = true;
    editId.value    = dept.id;
    formError.value = '';
    showForm.value  = true;
}

async function save() {
    formError.value = '';
    if (!form.nama.trim() || !form.kode.trim()) {
        formError.value = 'Nama dan Kode wajib diisi.';
        return;
    }
    saving.value = true;
    try {
        if (editMode.value) {
            await store.updateDepartment(editId.value, { ...form });
            toast.success('Departemen berhasil diperbarui.');
        } else {
            await store.createDepartment({ ...form });
            toast.success('Departemen berhasil ditambahkan.');
        }
        showForm.value = false;
    } catch (e) {
        formError.value = e.response?.data?.message ?? 'Gagal menyimpan.';
    } finally {
        saving.value = false;
    }
}

function confirmDelete(dept) {
    deleteDialog.open = true;
    deleteDialog.id   = dept.id;
    deleteDialog.nama = dept.nama;
}

async function doDelete() {
    try {
        await store.deleteDepartment(deleteDialog.id);
        toast.success('Departemen dihapus.');
    } catch (e) {
        toast.error(e.response?.data?.message ?? 'Gagal menghapus.');
    } finally {
        deleteDialog.open = false;
    }
}

function divisionName(id) {
    return divStore.divisions.find(d => d.id === id)?.nama ?? '—';
}
function divisionKode(id) {
    return divStore.divisions.find(d => d.id === id)?.kode ?? '';
}
</script>

<template>
    <AppLayout>
        <section class="page-hero">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <div class="page-hero-meta">Master Data</div>
                    <h2 class="mt-4 text-2xl font-bold leading-tight md:text-3xl">Manajemen Departemen</h2>
                    <p class="mt-2 max-w-xl text-sm leading-6 text-white/78">
                        Kelola departemen di bawah setiap divisi organisasi. Departemen digunakan sebagai referensi jabatan dan pengelompokan karyawan.
                    </p>
                </div>
                <button class="btn-primary shrink-0" @click="openCreate">+ Tambah Departemen</button>
            </div>
        </section>

        <section class="dashboard-panel overflow-hidden">
            <div class="border-b border-slate-200 px-6 py-4">
                <p class="section-heading">Daftar Departemen</p>
                <h3 class="mt-1 text-lg font-bold text-slate-900">{{ departments.length }} departemen terdaftar</h3>
            </div>

            <div v-if="store.isLoading" class="space-y-3 p-6">
                <Skeleton v-for="i in 6" :key="i" class="h-14 rounded-xl" />
            </div>

            <div v-else-if="!departments.length" class="py-16 text-center text-sm text-slate-400">
                Belum ada departemen. Klik <strong>+ Tambah Departemen</strong> untuk memulai.
            </div>

            <div v-else class="divide-y divide-slate-100">
                <div
                    v-for="dept in departments"
                    :key="dept.id"
                    class="flex items-center gap-4 px-6 py-4 transition-colors hover:bg-slate-50"
                >
                    <!-- Kode badge -->
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-indigo-100 text-[10px] font-bold text-indigo-700 uppercase">
                        {{ dept.kode.length > 6 ? dept.kode.slice(0, 5) + '…' : dept.kode }}
                    </div>

                    <!-- Info -->
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-sm font-semibold text-slate-900">{{ dept.nama }}</span>
                            <span v-if="!dept.is_active" class="badge-warning text-[10px]">Nonaktif</span>
                        </div>
                        <div class="mt-0.5 flex items-center gap-2 text-xs text-slate-500">
                            <template v-if="dept.division_id">
                                <span class="inline-flex items-center gap-1 rounded-md bg-blue-50 px-1.5 py-0.5 text-[10px] font-medium text-blue-700">
                                    {{ divisionKode(dept.division_id) }}
                                </span>
                                {{ divisionName(dept.division_id) }}
                            </template>
                            <span v-else class="text-slate-400">Tanpa divisi</span>
                            <span v-if="dept.deskripsi" class="text-slate-300">·</span>
                            <span v-if="dept.deskripsi" class="truncate">{{ dept.deskripsi }}</span>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex shrink-0 gap-2">
                        <button class="btn-secondary !px-3 !py-1.5 text-xs" @click="openEdit(dept)">Edit</button>
                        <button class="btn-danger !px-3 !py-1.5 text-xs" @click="confirmDelete(dept)">Hapus</button>
                    </div>
                </div>
            </div>
        </section>

        <!-- Form Dialog -->
        <Dialog
            v-model:open="showForm"
            :title="editMode ? 'Edit Departemen' : 'Tambah Departemen Baru'"
            class="max-w-lg"
        >
            <div class="mt-4 space-y-4">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="form-label">Nama Departemen <span class="text-red-500">*</span></label>
                        <Input v-model="form.nama" placeholder="Contoh: IT Department" />
                    </div>
                    <div>
                        <label class="form-label">Kode <span class="text-red-500">*</span></label>
                        <Input v-model="form.kode" placeholder="Contoh: IT_DEP" class="uppercase" />
                    </div>
                </div>

                <div>
                    <label class="form-label">Divisi</label>
                    <select v-model="form.division_id" class="form-input">
                        <option :value="null">— Tanpa Divisi —</option>
                        <option v-for="d in divStore.divisions" :key="d.id" :value="d.id">
                            {{ d.nama }} ({{ d.kode }})
                        </option>
                    </select>
                </div>

                <div>
                    <label class="form-label">Deskripsi</label>
                    <textarea
                        v-model="form.deskripsi"
                        rows="2"
                        class="form-textarea"
                        placeholder="Deskripsi singkat departemen ini..."
                    />
                </div>

                <div class="flex items-center gap-2">
                    <input id="dept_active" v-model="form.is_active" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-blue-600" />
                    <label for="dept_active" class="text-sm text-slate-700">Aktif</label>
                </div>

                <p v-if="formError" class="rounded-lg bg-red-50 px-3 py-2 text-xs text-red-600">{{ formError }}</p>

                <div class="flex justify-end gap-2 pt-2">
                    <button class="btn-secondary" @click="showForm = false">Batal</button>
                    <button class="btn-primary" :disabled="saving" @click="save">
                        {{ saving ? 'Menyimpan...' : editMode ? 'Simpan Perubahan' : 'Tambah Departemen' }}
                    </button>
                </div>
            </div>
        </Dialog>

        <!-- Delete Dialog -->
        <Dialog v-model:open="deleteDialog.open" title="Hapus Departemen">
            <p class="mt-3 text-sm text-slate-600">
                Yakin ingin menghapus departemen <strong>{{ deleteDialog.nama }}</strong>?
                Jabatan dan karyawan yang terhubung akan kehilangan referensi departemen ini.
            </p>
            <div class="mt-5 flex justify-end gap-2">
                <button class="btn-secondary" @click="deleteDialog.open = false">Batal</button>
                <button class="btn-danger" @click="doDelete">Hapus</button>
            </div>
        </Dialog>
    </AppLayout>
</template>
