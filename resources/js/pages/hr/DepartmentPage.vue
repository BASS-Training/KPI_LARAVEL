<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useDepartmentStore } from '@/stores/department';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/components/layout/AppLayout.vue';
import Dialog from '@/components/ui/Dialog.vue';
import Input from '@/components/ui/Input.vue';
import PageHeader from '@/components/shared/PageHeader.vue';
import EmptyState from '@/components/shared/EmptyState.vue';
import LoadingRows from '@/components/shared/LoadingRows.vue';

const store = useDepartmentStore();
const toast = useToast();

const showForm     = ref(false);
const editMode     = ref(false);
const editId       = ref(null);
const formError    = ref('');
const saving       = ref(false);
const deleteDialog = reactive({ open: false, id: null, nama: '' });

const emptyForm = () => ({ nama: '', kode: '', deskripsi: '', is_active: true });
const form = reactive(emptyForm());

const departments = computed(() => store.departments);

onMounted(() => {
    store.fetchDepartments();
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
        nama:      dept.nama,
        kode:      dept.kode,
        deskripsi: dept.deskripsi ?? '',
        is_active: !!dept.is_active,
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
</script>

<template>
    <AppLayout>
        <PageHeader
            eyebrow="Master Data"
            title="Manajemen Departemen"
            description="Kelola departemen organisasi sebagai referensi jabatan, pegawai, KPI, dan laporan performa."
        >
            <template #actions>
                <button class="btn-primary shrink-0" @click="openCreate">+ Tambah Departemen</button>
            </template>
        </PageHeader>

        <section class="dashboard-panel overflow-hidden">
            <div class="border-b border-slate-200 px-6 py-4">
                <p class="section-heading">Daftar Departemen</p>
                <h3 class="mt-1 text-lg font-bold text-slate-900">{{ departments.length }} departemen terdaftar</h3>
            </div>

            <LoadingRows v-if="store.isLoading" class="p-6" :rows="6" height="h-14" />

            <EmptyState
                v-else-if="!departments.length"
                title="Belum ada departemen"
                description="Tambahkan departemen untuk mengelompokkan pegawai, jabatan, dan indikator KPI."
                action-label="Tambah Departemen"
                @action="openCreate"
            />

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
                        <div v-if="dept.deskripsi" class="mt-0.5 truncate text-xs text-slate-500">
                            {{ dept.deskripsi }}
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
                        <Input v-model="form.nama" placeholder="Contoh: Information Technology" />
                    </div>
                    <div>
                        <label class="form-label">Kode <span class="text-red-500">*</span></label>
                        <Input v-model="form.kode" placeholder="Contoh: ITD" class="uppercase" />
                    </div>
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
