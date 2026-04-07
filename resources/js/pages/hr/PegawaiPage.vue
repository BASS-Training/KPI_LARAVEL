<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import AppLayout from '@/components/layout/AppLayout.vue';
import Dialog from '@/components/ui/Dialog.vue';
import Input from '@/components/ui/Input.vue';
import Button from '@/components/ui/Button.vue';
import Alert from '@/components/ui/Alert.vue';
import Skeleton from '@/components/ui/Skeleton.vue';
import Avatar from '@/components/ui/Avatar.vue';
import { useEmployeeStore } from '@/stores/employee';
import { useToast } from '@/composables/useToast';

const store = useEmployeeStore();
const toast = useToast();

const employees = computed(() => store.employees);
const showForm = ref(false);
const editMode = ref(false);
const selectedId = ref(null);
const submitting = ref(false);
const formError = ref('');
const deleteState = ref({ open: false, id: null, name: '' });
const deleting = ref(false);

const emptyForm = () => ({
    nip: '',
    nama: '',
    jabatan: '',
    departemen: '',
    status_karyawan: 'Tetap',
    tanggal_masuk: '',
    no_hp: '',
    email: '',
    role: 'pegawai',
});

const form = reactive(emptyForm());
const errors = reactive({});

onMounted(() => store.fetchEmployees());

function resetForm() {
    Object.assign(form, emptyForm());
    formError.value = '';
    Object.keys(errors).forEach((key) => { errors[key] = ''; });
}

function openCreate() {
    editMode.value = false;
    selectedId.value = null;
    resetForm();
    showForm.value = true;
}

function openEdit(employee) {
    editMode.value = true;
    selectedId.value = employee.id;
    resetForm();
    Object.assign(form, {
        nip: employee.nip ?? '',
        nama: employee.nama ?? '',
        jabatan: employee.jabatan ?? '',
        departemen: employee.departemen ?? '',
        status_karyawan: employee.status_karyawan ?? 'Tetap',
        tanggal_masuk: employee.tanggal_masuk ?? '',
        no_hp: employee.no_hp ?? '',
        email: employee.email ?? '',
        role: employee.role ?? 'pegawai',
    });
    showForm.value = true;
}

function validate() {
    Object.assign(errors, {
        nip: '',
        nama: '',
        jabatan: '',
        departemen: '',
        tanggal_masuk: '',
        role: '',
    });

    let valid = true;

    ['nip', 'nama', 'jabatan', 'departemen', 'tanggal_masuk', 'role'].forEach((field) => {
        if (!String(form[field] ?? '').trim()) {
            errors[field] = 'Field wajib diisi.';
            valid = false;
        }
    });

    return valid;
}

async function submit() {
    if (!validate()) return;

    submitting.value = true;
    formError.value = '';

    try {
        if (editMode.value && selectedId.value) {
            await store.updateEmployee(selectedId.value, { ...form });
            toast.success('Data pegawai berhasil diperbarui.');
        } else {
            await store.createEmployee({ ...form });
            toast.success('Pegawai berhasil ditambahkan.');
        }

        showForm.value = false;
        await store.fetchEmployees();
    } catch (err) {
        formError.value = err.response?.data?.message || 'Gagal menyimpan data pegawai.';
    } finally {
        submitting.value = false;
    }
}

function askDelete(employee) {
    deleteState.value = { open: true, id: employee.id, name: employee.nama };
}

async function confirmDelete() {
    deleting.value = true;
    try {
        await store.deleteEmployee(deleteState.value.id);
        toast.success('Pegawai berhasil dihapus.');
        deleteState.value.open = false;
    } catch (err) {
        toast.error(err.response?.data?.message || 'Gagal menghapus pegawai.');
    } finally {
        deleting.value = false;
    }
}
</script>

<template>
    <AppLayout>
        <template #topbar-actions>
            <button class="btn-primary" @click="openCreate">Tambah Pegawai</button>
        </template>

        <section class="dashboard-panel overflow-hidden">
            <div class="border-b border-slate-200 px-6 py-5">
                <p class="section-heading">HR Panel</p>
                <h2 class="mt-2 text-xl font-bold text-slate-900">Manajemen Pegawai</h2>
                <p class="mt-1 text-sm text-slate-500">Kelola data user login, jabatan, dan role akses aplikasi.</p>
            </div>

            <div class="p-6">
                <template v-if="store.isLoading">
                    <div class="space-y-3">
                        <Skeleton v-for="i in 6" :key="i" class="h-14 rounded-2xl" />
                    </div>
                </template>

                <template v-else-if="employees.length">
                    <div class="space-y-3">
                        <div
                            v-for="employee in employees"
                            :key="employee.id"
                            class="data-row"
                        >
                            <div class="flex min-w-0 flex-1 items-center gap-3">
                                <Avatar :name="employee.nama" size="sm" />
                                <div class="min-w-0">
                                    <div class="truncate text-sm font-semibold text-slate-900">{{ employee.nama }}</div>
                                    <div class="mt-1 truncate text-xs text-slate-500">
                                        {{ employee.nip }} · {{ employee.jabatan }} · {{ employee.departemen }}
                                    </div>
                                </div>
                            </div>

                            <div class="hidden min-w-[180px] text-sm text-slate-600 md:block">
                                {{ employee.email || '-' }}
                            </div>

                            <div class="hidden min-w-[120px] md:block">
                                <span class="badge-info">{{ employee.role }}</span>
                            </div>

                            <div class="flex items-center gap-2">
                                <button class="btn-secondary !px-3 !py-2 text-xs" @click="openEdit(employee)">Edit</button>
                                <button class="btn-secondary !border-red-200 !text-red-600 !px-3 !py-2 text-xs hover:!bg-red-50" @click="askDelete(employee)">Hapus</button>
                            </div>
                        </div>
                    </div>
                </template>

                <div v-else class="py-14 text-center text-sm text-slate-400">
                    Belum ada data pegawai.
                </div>
            </div>
        </section>

        <Dialog v-model:open="showForm" :title="editMode ? 'Edit Pegawai' : 'Tambah Pegawai'" class="max-w-3xl">
            <Alert v-if="formError" variant="danger" class="mb-4">{{ formError }}</Alert>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="form-label">NIP</label>
                    <Input v-model="form.nip" class="!rounded-xl !border-slate-200 !px-4 !py-3" />
                    <p v-if="errors.nip" class="mt-1 text-xs text-red-500">{{ errors.nip }}</p>
                </div>
                <div>
                    <label class="form-label">Nama Lengkap</label>
                    <Input v-model="form.nama" class="!rounded-xl !border-slate-200 !px-4 !py-3" />
                    <p v-if="errors.nama" class="mt-1 text-xs text-red-500">{{ errors.nama }}</p>
                </div>
                <div>
                    <label class="form-label">Jabatan</label>
                    <Input v-model="form.jabatan" class="!rounded-xl !border-slate-200 !px-4 !py-3" />
                    <p v-if="errors.jabatan" class="mt-1 text-xs text-red-500">{{ errors.jabatan }}</p>
                </div>
                <div>
                    <label class="form-label">Departemen</label>
                    <Input v-model="form.departemen" class="!rounded-xl !border-slate-200 !px-4 !py-3" />
                    <p v-if="errors.departemen" class="mt-1 text-xs text-red-500">{{ errors.departemen }}</p>
                </div>
                <div>
                    <label class="form-label">Status Karyawan</label>
                    <select v-model="form.status_karyawan" class="form-input">
                        <option value="Tetap">Tetap</option>
                        <option value="Kontrak">Kontrak</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Tanggal Masuk</label>
                    <input v-model="form.tanggal_masuk" type="date" class="form-input" />
                    <p v-if="errors.tanggal_masuk" class="mt-1 text-xs text-red-500">{{ errors.tanggal_masuk }}</p>
                </div>
                <div>
                    <label class="form-label">No. HP</label>
                    <Input v-model="form.no_hp" class="!rounded-xl !border-slate-200 !px-4 !py-3" />
                </div>
                <div>
                    <label class="form-label">Email</label>
                    <Input v-model="form.email" type="email" class="!rounded-xl !border-slate-200 !px-4 !py-3" />
                </div>
                <div class="md:col-span-2">
                    <label class="form-label">Role</label>
                    <select v-model="form.role" class="form-input">
                        <option value="pegawai">pegawai</option>
                        <option value="hr_manager">hr_manager</option>
                        <option value="direktur">direktur</option>
                    </select>
                    <p v-if="errors.role" class="mt-1 text-xs text-red-500">{{ errors.role }}</p>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <button class="btn-secondary" :disabled="submitting" @click="showForm = false">Batal</button>
                <button class="btn-primary" :disabled="submitting" @click="submit">
                    {{ submitting ? 'Menyimpan...' : editMode ? 'Perbarui Pegawai' : 'Simpan Pegawai' }}
                </button>
            </div>
        </Dialog>

        <Dialog v-model:open="deleteState.open" title="Hapus Pegawai" class="max-w-lg">
            <p class="text-sm text-slate-600">
                Hapus <strong>{{ deleteState.name }}</strong> dari sistem?
            </p>
            <div class="mt-6 flex justify-end gap-3">
                <button class="btn-secondary" :disabled="deleting" @click="deleteState.open = false">Batal</button>
                <button class="btn-primary" :disabled="deleting" style="background: linear-gradient(135deg, #dc2626, #b91c1c);" @click="confirmDelete">
                    {{ deleting ? 'Menghapus...' : 'Ya, Hapus' }}
                </button>
            </div>
        </Dialog>
    </AppLayout>
</template>
