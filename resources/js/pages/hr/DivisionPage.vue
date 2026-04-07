<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useDivisionStore } from '@/stores/division';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/components/layout/AppLayout.vue';
import Dialog from '@/components/ui/Dialog.vue';
import Input from '@/components/ui/Input.vue';
import Skeleton from '@/components/ui/Skeleton.vue';

const store = useDivisionStore();
const toast = useToast();

const showForm      = ref(false);
const editMode      = ref(false);
const editId        = ref(null);
const formError     = ref('');
const saving        = ref(false);
const deleteDialog  = reactive({ open: false, id: null, nama: '' });

const emptyForm = () => ({ nama: '', kode: '', deskripsi: '', is_active: true });
const form = reactive(emptyForm());

const divisions = computed(() => store.divisions);

onMounted(() => store.fetchDivisions());

function openCreate() {
    Object.assign(form, emptyForm());
    editMode.value  = false;
    editId.value    = null;
    formError.value = '';
    showForm.value  = true;
}

function openEdit(div) {
    Object.assign(form, {
        nama:      div.nama,
        kode:      div.kode,
        deskripsi: div.deskripsi ?? '',
        is_active: !!div.is_active,
    });
    editMode.value  = true;
    editId.value    = div.id;
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
            await store.updateDivision(editId.value, { ...form });
            toast.success('Divisi berhasil diperbarui.');
        } else {
            await store.createDivision({ ...form });
            toast.success('Divisi berhasil ditambahkan.');
        }
        showForm.value = false;
    } catch (e) {
        formError.value = e.response?.data?.message ?? e.userMessage ?? 'Gagal menyimpan.';
    } finally {
        saving.value = false;
    }
}

function confirmDelete(div) {
    deleteDialog.open = true;
    deleteDialog.id   = div.id;
    deleteDialog.nama = div.nama;
}

async function doDelete() {
    try {
        await store.deleteDivision(deleteDialog.id);
        toast.success('Divisi dihapus.');
    } catch (e) {
        toast.error(e.response?.data?.message ?? 'Gagal menghapus.');
    } finally {
        deleteDialog.open = false;
    }
}
</script>

<template>
    <AppLayout>
        <section class="page-hero">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <div class="page-hero-meta">Master Data</div>
                    <h2 class="mt-4 text-2xl font-bold leading-tight md:text-3xl">Manajemen Divisi</h2>
                    <p class="mt-2 max-w-xl text-sm leading-6 text-white/78">
                        Kelola divisi organisasi BASS Training Center. Divisi digunakan sebagai referensi KPI dan pengelompokan karyawan.
                    </p>
                </div>
                <button class="btn-primary shrink-0" @click="openCreate">+ Tambah Divisi</button>
            </div>
        </section>

        <section class="dashboard-panel overflow-hidden">
            <div class="border-b border-slate-200 px-6 py-4">
                <p class="section-heading">Daftar Divisi</p>
                <h3 class="mt-1 text-lg font-bold text-slate-900">{{ divisions.length }} divisi terdaftar</h3>
            </div>

            <div v-if="store.isLoading" class="space-y-3 p-6">
                <Skeleton v-for="i in 5" :key="i" class="h-14 rounded-xl" />
            </div>

            <div v-else-if="!divisions.length" class="py-16 text-center text-sm text-slate-400">
                Belum ada divisi. Klik <strong>+ Tambah Divisi</strong> untuk memulai.
            </div>

            <div v-else class="divide-y divide-slate-100">
                <div
                    v-for="div in divisions"
                    :key="div.id"
                    class="flex items-center gap-4 px-6 py-4 transition-colors hover:bg-slate-50"
                >
                    <!-- Kode badge -->
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-100 text-[11px] font-bold text-blue-700 uppercase">
                        {{ div.kode }}
                    </div>

                    <!-- Info -->
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-semibold text-slate-900">{{ div.nama }}</span>
                            <span v-if="!div.is_active" class="badge-warning text-[10px]">Nonaktif</span>
                        </div>
                        <p v-if="div.deskripsi" class="mt-0.5 truncate text-xs text-slate-500">{{ div.deskripsi }}</p>
                    </div>

                    <!-- Actions -->
                    <div class="flex shrink-0 gap-2">
                        <button class="btn-secondary !px-3 !py-1.5 text-xs" @click="openEdit(div)">Edit</button>
                        <button class="btn-danger !px-3 !py-1.5 text-xs" @click="confirmDelete(div)">Hapus</button>
                    </div>
                </div>
            </div>
        </section>

        <!-- Form Dialog -->
        <Dialog
            v-model:open="showForm"
            :title="editMode ? 'Edit Divisi' : 'Tambah Divisi Baru'"
            class="max-w-lg"
        >
            <div class="mt-4 space-y-4">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="form-label">Nama Divisi <span class="text-red-500">*</span></label>
                        <Input v-model="form.nama" placeholder="Contoh: Digital Promotion" />
                    </div>
                    <div>
                        <label class="form-label">Kode <span class="text-red-500">*</span></label>
                        <Input v-model="form.kode" placeholder="Contoh: DIGPRO" class="uppercase" />
                    </div>
                </div>

                <div>
                    <label class="form-label">Deskripsi</label>
                    <textarea
                        v-model="form.deskripsi"
                        rows="2"
                        class="form-textarea"
                        placeholder="Deskripsi singkat divisi ini..."
                    />
                </div>

                <div class="flex items-center gap-2">
                    <input id="div_active" v-model="form.is_active" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-blue-600" />
                    <label for="div_active" class="text-sm text-slate-700">Aktif</label>
                </div>

                <p v-if="formError" class="rounded-lg bg-red-50 px-3 py-2 text-xs text-red-600">{{ formError }}</p>

                <div class="flex justify-end gap-2 pt-2">
                    <button class="btn-secondary" @click="showForm = false">Batal</button>
                    <button class="btn-primary" :disabled="saving" @click="save">
                        {{ saving ? 'Menyimpan...' : editMode ? 'Simpan Perubahan' : 'Tambah Divisi' }}
                    </button>
                </div>
            </div>
        </Dialog>

        <!-- Delete Dialog -->
        <Dialog v-model:open="deleteDialog.open" title="Hapus Divisi">
            <p class="mt-3 text-sm text-slate-600">
                Yakin ingin menghapus divisi <strong>{{ deleteDialog.nama }}</strong>?
                Karyawan dan KPI yang terhubung akan kehilangan referensi divisi ini.
            </p>
            <div class="mt-5 flex justify-end gap-2">
                <button class="btn-secondary" @click="deleteDialog.open = false">Batal</button>
                <button class="btn-danger" @click="doDelete">Hapus</button>
            </div>
        </Dialog>
    </AppLayout>
</template>
