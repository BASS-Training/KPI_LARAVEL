<script setup>
import { ref, reactive, onMounted, computed } from 'vue';
import { useTaskStore } from '@/stores/task';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/components/layout/AppLayout.vue';
import Dialog from '@/components/ui/Dialog.vue';
import Skeleton from '@/components/ui/Skeleton.vue';

const taskStore = useTaskStore();
const toast = useToast();

// ─── Form state ─────────────────────────────────────────────────────────────
const showForm = ref(false);
const editMode = ref(false);
const editId = ref(null);
const formLoading = ref(false);
const formError = ref('');

const emptyForm = () => ({
    judul: '',
    tanggal: '',
    jenis_pekerjaan: '',
    waktu_mulai: '',
    waktu_selesai: '',
    status: '',
    ada_delay: false,
    ada_error: false,
    ada_komplain: false,
    deskripsi: '',
});

const form = reactive(emptyForm());
const formErrors = reactive({});

// ─── Konfirmasi hapus ────────────────────────────────────────────────────────
const deleteDialog = ref({ open: false, taskId: null, taskTitle: '' });
const deleteLoading = ref(false);

// ─── Options ──────────────────────────────────────────────────────────────
const jobTypeOptions = [
    'Administratif', 'Teknis', 'Pelayanan', 'Koordinasi', 'Lainnya',
];

const statusOptions = [
    { value: 'Selesai', label: 'Selesai' },
    { value: 'Dalam Proses', label: 'Dalam Proses' },
    { value: 'Pending', label: 'Pending' },
];

const currentPage = computed(() => taskStore.pagination.currentPage);
const lastPage = computed(() => taskStore.pagination.lastPage);
const tasks = computed(() => taskStore.tasks);

onMounted(() => taskStore.fetchTasks());

// ─── Validasi ───────────────────────────────────────────────────────────────
function validate() {
    Object.assign(formErrors, { judul: '', tanggal: '', jenis_pekerjaan: '', status: '', waktu_selesai: '' });
    let valid = true;

    if (!form.judul.trim()) { formErrors.judul = 'Judul wajib diisi.'; valid = false; }
    if (!form.tanggal) { formErrors.tanggal = 'Tanggal wajib diisi.'; valid = false; }
    if (!form.jenis_pekerjaan) { formErrors.jenis_pekerjaan = 'Jenis pekerjaan wajib dipilih.'; valid = false; }
    if (!form.status) { formErrors.status = 'Status wajib dipilih.'; valid = false; }
    if (form.waktu_mulai && form.waktu_selesai && form.waktu_mulai >= form.waktu_selesai) {
        formErrors.waktu_selesai = 'Waktu selesai harus setelah waktu mulai.';
        valid = false;
    }
    return valid;
}

function openCreate() {
    editMode.value = false;
    editId.value = null;
    Object.assign(form, emptyForm());
    formError.value = '';
    Object.assign(formErrors, {});
    showForm.value = true;
}

function openEdit(task) {
    editMode.value = true;
    editId.value = task.id;
    Object.assign(form, {
        judul: task.judul || '',
        tanggal: task.tanggal || '',
        jenis_pekerjaan: task.jenis_pekerjaan || '',
        waktu_mulai: task.waktu_mulai || '',
        waktu_selesai: task.waktu_selesai || '',
        status: task.status || '',
        ada_delay: Boolean(task.ada_delay),
        ada_error: Boolean(task.ada_error),
        ada_komplain: Boolean(task.ada_komplain),
        deskripsi: task.deskripsi || '',
    });
    formError.value = '';
    Object.assign(formErrors, {});
    showForm.value = true;
}

async function submitForm() {
    if (!validate()) return;
    formLoading.value = true;
    formError.value = '';
    try {
        if (editMode.value && editId.value) {
            await taskStore.updateTask(editId.value, { ...form });
            toast.success('Pekerjaan berhasil diperbarui.');
        } else {
            await taskStore.createTask({ ...form });
            toast.success('Pekerjaan berhasil disimpan.');
        }
        showForm.value = false;
        await taskStore.fetchTasks();
    } catch (err) {
        formError.value = err.userMessage || 'Gagal menyimpan pekerjaan.';
    } finally {
        formLoading.value = false;
    }
}

function cancelForm() {
    showForm.value = false;
    formError.value = '';
}

// ─── Hapus ──────────────────────────────────────────────────────────────────
function openDeleteDialog(task) {
    deleteDialog.value = { open: true, taskId: task.id, taskTitle: task.judul };
}

async function confirmDelete() {
    deleteLoading.value = true;
    try {
        await taskStore.deleteTask(deleteDialog.value.taskId);
        toast.success('Pekerjaan berhasil dihapus.');
        deleteDialog.value.open = false;
    } catch (err) {
        toast.error(err.userMessage || 'Gagal menghapus pekerjaan.');
    } finally {
        deleteLoading.value = false;
    }
}

async function changePage(page) {
    taskStore.setPage(page);
    await taskStore.fetchTasks();
}

function formatDate(d) {
    if (!d) return '-';
    return new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
}

const statusBadgeMap = {
    'Selesai': 'badge-success',
    'Dalam Proses': 'badge-info',
    'Pending': 'badge-warning',
};
</script>

<template>
    <AppLayout>
        <template #topbar-actions>
            <button class="btn-primary" @click="openCreate">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 5v14M5 12h14"/>
                </svg>
                Input Pekerjaan
            </button>
        </template>

        <!-- ─── Tabel Pekerjaan ───────────────────────────────────────────── -->
        <div class="card">
            <div class="mb-5 flex items-center justify-between">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Riwayat Pekerjaan Bulan Ini</h2>
                    <p class="mt-0.5 text-xs text-slate-500">
                        Total {{ taskStore.pagination.total }} pekerjaan tercatat
                    </p>
                </div>
            </div>

            <!-- Loading -->
            <template v-if="taskStore.isLoading">
                <div class="space-y-3">
                    <Skeleton v-for="i in 5" :key="i" class="h-14 rounded-2xl" />
                </div>
            </template>

            <!-- Data -->
            <template v-else-if="tasks.length">
                <!-- Desktop table -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-100">
                                <th class="stat-label py-2 text-left font-semibold pr-4">Tanggal</th>
                                <th class="stat-label py-2 text-left font-semibold pr-4">Judul Pekerjaan</th>
                                <th class="stat-label py-2 text-left font-semibold pr-4">Jenis</th>
                                <th class="stat-label py-2 text-left font-semibold pr-4">Waktu</th>
                                <th class="stat-label py-2 text-left font-semibold pr-4">Status</th>
                                <th class="stat-label py-2 text-left font-semibold pr-4">Flag</th>
                                <th class="stat-label py-2 text-left font-semibold">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <tr
                                v-for="task in tasks"
                                :key="task.id"
                                class="hover:bg-slate-50 transition-colors"
                            >
                                <td class="py-3 pr-4 whitespace-nowrap text-slate-600">{{ formatDate(task.tanggal) }}</td>
                                <td class="py-3 pr-4 max-w-[220px]">
                                    <div class="truncate font-medium text-slate-900">{{ task.judul }}</div>
                                    <div v-if="task.deskripsi" class="mt-0.5 truncate text-xs text-slate-400">{{ task.deskripsi }}</div>
                                </td>
                                <td class="py-3 pr-4 text-slate-600">{{ task.jenis_pekerjaan }}</td>
                                <td class="py-3 pr-4 whitespace-nowrap text-slate-600">
                                    {{ task.waktu_mulai || '-' }} – {{ task.waktu_selesai || '-' }}
                                </td>
                                <td class="py-3 pr-4">
                                    <span :class="statusBadgeMap[task.status] || 'badge-neutral'">
                                        {{ task.status }}
                                    </span>
                                </td>
                                <td class="py-3 pr-4">
                                    <div class="flex gap-1">
                                        <span v-if="task.ada_delay" class="badge-danger text-[10px]">Delay</span>
                                        <span v-if="task.ada_error" class="badge-danger text-[10px]">Error</span>
                                        <span v-if="task.ada_komplain" class="badge-warning text-[10px]">Komplain</span>
                                        <span v-if="!task.ada_delay && !task.ada_error && !task.ada_komplain" class="text-xs text-slate-400">-</span>
                                    </div>
                                </td>
                                <td class="py-3">
                                    <div class="flex items-center gap-1">
                                        <button
                                            type="button"
                                            class="flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 hover:text-blue-600 transition-colors"
                                            title="Edit"
                                            @click="openEdit(task)"
                                        >
                                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                            </svg>
                                        </button>
                                        <button
                                            type="button"
                                            class="flex h-8 w-8 items-center justify-center rounded-lg border border-red-100 bg-red-50 text-red-500 hover:bg-red-100 transition-colors"
                                            title="Hapus"
                                            @click="openDeleteDialog(task)"
                                        >
                                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                                <path d="M3 6h18M19 6l-1 14H6L5 6M10 11v6M14 11v6M9 6V4h6v2"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile: card list -->
                <div class="space-y-3 md:hidden">
                    <div
                        v-for="task in tasks"
                        :key="task.id"
                        class="data-row flex-col items-start gap-2"
                    >
                        <div class="flex w-full items-start justify-between gap-2">
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-semibold text-slate-900">{{ task.judul }}</p>
                                <p class="mt-0.5 text-xs text-slate-500">
                                    {{ formatDate(task.tanggal) }} · {{ task.jenis_pekerjaan }}
                                </p>
                            </div>
                            <span :class="statusBadgeMap[task.status] || 'badge-neutral'" class="shrink-0">
                                {{ task.status }}
                            </span>
                        </div>
                        <div class="flex w-full items-center justify-between">
                            <div class="flex gap-1">
                                <span v-if="task.ada_delay" class="badge-danger text-[10px]">Delay</span>
                                <span v-if="task.ada_error" class="badge-danger text-[10px]">Error</span>
                                <span v-if="task.ada_komplain" class="badge-warning text-[10px]">Komplain</span>
                            </div>
                            <div class="flex gap-2">
                                <button
                                    type="button"
                                    class="text-xs font-semibold text-blue-600 hover:text-blue-800"
                                    @click="openEdit(task)"
                                >Edit</button>
                                <button
                                    type="button"
                                    class="text-xs font-semibold text-red-600 hover:text-red-800"
                                    @click="openDeleteDialog(task)"
                                >Hapus</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="lastPage > 1" class="mt-4 flex items-center justify-center gap-3 border-t border-slate-100 pt-4">
                    <button
                        class="btn-secondary text-xs px-3 py-2"
                        :disabled="currentPage === 1"
                        @click="changePage(currentPage - 1)"
                    >← Sebelumnya</button>
                    <span class="text-xs text-slate-500 font-medium">{{ currentPage }} / {{ lastPage }}</span>
                    <button
                        class="btn-secondary text-xs px-3 py-2"
                        :disabled="currentPage === lastPage"
                        @click="changePage(currentPage + 1)"
                    >Berikutnya →</button>
                </div>
            </template>

            <!-- Empty state -->
            <div v-else class="py-16 text-center">
                <div class="mb-4 text-5xl">📋</div>
                <p class="font-semibold text-slate-700">Belum ada pekerjaan bulan ini</p>
                <p class="mt-1 text-sm text-slate-400">Klik tombol "Input Pekerjaan" untuk mulai mencatat.</p>
                <button class="btn-primary mt-4" @click="openCreate">Input Pekerjaan Pertama</button>
            </div>
        </div>

        <!-- ─── Dialog Form (Create / Edit) ──────────────────────────────────── -->
        <Dialog
            v-model:open="showForm"
            :title="editMode ? 'Edit Pekerjaan' : 'Input Pekerjaan Baru'"
            class="max-w-2xl"
        >
            <!-- Error -->
            <div v-if="formError" class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                {{ formError }}
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <!-- Judul -->
                <div class="md:col-span-2">
                    <label class="form-label">Judul Pekerjaan</label>
                    <input
                        v-model="form.judul"
                        type="text"
                        class="form-input"
                        placeholder="Contoh: Pembuatan laporan bulanan"
                    />
                    <p v-if="formErrors.judul" class="mt-1 text-xs text-red-500">{{ formErrors.judul }}</p>
                </div>

                <!-- Tanggal -->
                <div>
                    <label class="form-label">Tanggal</label>
                    <input v-model="form.tanggal" type="date" class="form-input" />
                    <p v-if="formErrors.tanggal" class="mt-1 text-xs text-red-500">{{ formErrors.tanggal }}</p>
                </div>

                <!-- Jenis Pekerjaan -->
                <div>
                    <label class="form-label">Jenis Pekerjaan</label>
                    <select v-model="form.jenis_pekerjaan" class="form-input">
                        <option value="">Pilih jenis...</option>
                        <option v-for="opt in jobTypeOptions" :key="opt" :value="opt">{{ opt }}</option>
                    </select>
                    <p v-if="formErrors.jenis_pekerjaan" class="mt-1 text-xs text-red-500">{{ formErrors.jenis_pekerjaan }}</p>
                </div>

                <!-- Waktu Mulai -->
                <div>
                    <label class="form-label">Waktu Mulai</label>
                    <input v-model="form.waktu_mulai" type="time" class="form-input" />
                </div>

                <!-- Waktu Selesai -->
                <div>
                    <label class="form-label">Waktu Selesai</label>
                    <input v-model="form.waktu_selesai" type="time" class="form-input" />
                    <p v-if="formErrors.waktu_selesai" class="mt-1 text-xs text-red-500">{{ formErrors.waktu_selesai }}</p>
                </div>

                <!-- Status -->
                <div class="md:col-span-2">
                    <label class="form-label">Status Pekerjaan</label>
                    <select v-model="form.status" class="form-input">
                        <option value="">Pilih status...</option>
                        <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                    </select>
                    <p v-if="formErrors.status" class="mt-1 text-xs text-red-500">{{ formErrors.status }}</p>
                </div>

                <!-- Flag checkbox -->
                <div class="md:col-span-2">
                    <label class="form-label">Indikator Masalah</label>
                    <div class="flex flex-wrap gap-4">
                        <label
                            v-for="flag in [
                                { key: 'ada_delay', label: 'Ada Delay' },
                                { key: 'ada_error', label: 'Ada Error' },
                                { key: 'ada_komplain', label: 'Ada Komplain' },
                            ]"
                            :key="flag.key"
                            class="flex cursor-pointer items-center gap-2 rounded-xl border px-4 py-2.5 text-sm font-medium transition-colors"
                            :class="form[flag.key]
                                ? 'border-red-300 bg-red-50 text-red-700'
                                : 'border-slate-200 bg-slate-50 text-slate-600 hover:bg-slate-100'"
                        >
                            <input
                                type="checkbox"
                                v-model="form[flag.key]"
                                class="h-4 w-4 rounded border-slate-300"
                            />
                            {{ flag.label }}
                        </label>
                    </div>
                </div>

                <!-- Deskripsi -->
                <div class="md:col-span-2">
                    <label class="form-label">Deskripsi (Opsional)</label>
                    <textarea
                        v-model="form.deskripsi"
                        class="form-input"
                        placeholder="Keterangan tambahan..."
                        rows="3"
                        style="resize: vertical;"
                    />
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <button class="btn-secondary" :disabled="formLoading" @click="cancelForm">Batal</button>
                <button class="btn-primary" :disabled="formLoading" @click="submitForm">
                    <svg v-if="formLoading" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4m12 0h4M4.93 19.07l2.83-2.83m8.48-8.48 2.83-2.83"/>
                    </svg>
                    {{ formLoading ? 'Menyimpan...' : editMode ? 'Perbarui Pekerjaan' : 'Simpan Pekerjaan' }}
                </button>
            </div>
        </Dialog>

        <!-- ─── Dialog Konfirmasi Hapus ───────────────────────────────────────── -->
        <Dialog
            v-model:open="deleteDialog.open"
            title="Hapus Pekerjaan"
            :description="`Apakah kamu yakin ingin menghapus &quot;${deleteDialog.taskTitle}&quot;? Aksi ini tidak dapat dibatalkan.`"
        >
            <div class="mt-4 flex justify-end gap-3">
                <button class="btn-secondary" :disabled="deleteLoading" @click="deleteDialog.open = false">
                    Batal
                </button>
                <button
                    class="btn-primary"
                    style="background: linear-gradient(135deg, #dc2626, #b91c1c); box-shadow: 0 4px 12px -2px rgba(220, 38, 38, 0.4);"
                    :disabled="deleteLoading"
                    @click="confirmDelete"
                >
                    {{ deleteLoading ? 'Menghapus...' : 'Ya, Hapus' }}
                </button>
            </div>
        </Dialog>
    </AppLayout>
</template>
