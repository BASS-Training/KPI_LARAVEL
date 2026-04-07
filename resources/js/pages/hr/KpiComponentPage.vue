<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import AppLayout from '@/components/layout/AppLayout.vue';
import Dialog from '@/components/ui/Dialog.vue';
import Input from '@/components/ui/Input.vue';
import Textarea from '@/components/ui/Textarea.vue';
import Alert from '@/components/ui/Alert.vue';
import Skeleton from '@/components/ui/Skeleton.vue';
import { useToast } from '@/composables/useToast';
import { useKpiComponentStore } from '@/stores/kpiComponent';

const store = useKpiComponentStore();
const toast = useToast();
const components = computed(() => store.components);
const showForm = ref(false);
const editMode = ref(false);
const selectedId = ref(null);
const submitting = ref(false);
const formError = ref('');
const deleteState = ref({ open: false, id: null, name: '' });

const emptyForm = () => ({
    jabatan: '',
    objectives: '',
    strategy: '',
    bobot: 0,
    target: '',
    tipe: 'achievement',
    catatan: '',
    is_active: true,
});

const form = reactive(emptyForm());
const errors = reactive({});

onMounted(() => store.fetchComponents());

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

function openEdit(item) {
    editMode.value = true;
    selectedId.value = item.id;
    resetForm();
    Object.assign(form, {
        jabatan: item.jabatan ?? '',
        objectives: item.objectives ?? '',
        strategy: item.strategy ?? '',
        bobot: item.bobot ?? 0,
        target: item.target ?? '',
        tipe: item.tipe ?? 'achievement',
        catatan: item.catatan ?? '',
        is_active: Boolean(item.is_active),
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
    formError.value = '';
    try {
        const payload = {
            ...form,
            bobot: Number(form.bobot),
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
</script>

<template>
    <AppLayout>
        <template #topbar-actions>
            <button class="btn-primary" @click="openCreate">Tambah Komponen</button>
        </template>

        <section class="dashboard-panel overflow-hidden">
            <div class="border-b border-slate-200 px-6 py-5">
                <p class="section-heading">HR Panel</p>
                <h2 class="mt-2 text-xl font-bold text-slate-900">Komponen KPI</h2>
                <p class="mt-1 text-sm text-slate-500">Kelola objective, bobot, target, dan tipe komponen KPI per jabatan.</p>
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
                                <div class="truncate text-sm font-semibold text-slate-900">{{ item.objectives }}</div>
                                <div class="mt-1 truncate text-xs text-slate-500">
                                    {{ item.jabatan }} · {{ item.tipe }} · Bobot {{ item.bobot }}
                                </div>
                            </div>
                            <div class="hidden min-w-[220px] text-sm text-slate-600 md:block truncate">{{ item.strategy }}</div>
                            <div class="flex items-center gap-2">
                                <button class="btn-secondary !px-3 !py-2 text-xs" @click="openEdit(item)">Edit</button>
                                <button class="btn-secondary !border-red-200 !text-red-600 !px-3 !py-2 text-xs hover:!bg-red-50" @click="deleteState = { open: true, id: item.id, name: item.objectives }">Hapus</button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </section>

        <Dialog v-model:open="showForm" :title="editMode ? 'Edit Komponen KPI' : 'Tambah Komponen KPI'" class="max-w-3xl">
            <Alert v-if="formError" variant="danger" class="mb-4">{{ formError }}</Alert>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="form-label">Jabatan</label>
                    <Input v-model="form.jabatan" class="!rounded-xl !border-slate-200 !px-4 !py-3" />
                    <p v-if="errors.jabatan" class="mt-1 text-xs text-red-500">{{ errors.jabatan }}</p>
                </div>
                <div>
                    <label class="form-label">Objectives</label>
                    <Input v-model="form.objectives" class="!rounded-xl !border-slate-200 !px-4 !py-3" />
                    <p v-if="errors.objectives" class="mt-1 text-xs text-red-500">{{ errors.objectives }}</p>
                </div>
                <div>
                    <label class="form-label">Bobot</label>
                    <Input v-model="form.bobot" type="number" min="0" max="1" step="0.01" class="!rounded-xl !border-slate-200 !px-4 !py-3" />
                    <p v-if="errors.bobot" class="mt-1 text-xs text-red-500">{{ errors.bobot }}</p>
                </div>
                <div>
                    <label class="form-label">Target</label>
                    <Input v-model="form.target" type="number" step="0.01" class="!rounded-xl !border-slate-200 !px-4 !py-3" />
                </div>
                <div class="md:col-span-2">
                    <label class="form-label">Strategy</label>
                    <Textarea v-model="form.strategy" rows="3" class="!rounded-xl !border-slate-200 !px-4 !py-3" />
                    <p v-if="errors.strategy" class="mt-1 text-xs text-red-500">{{ errors.strategy }}</p>
                </div>
                <div>
                    <label class="form-label">Tipe</label>
                    <select v-model="form.tipe" class="form-input">
                        <option value="achievement">achievement</option>
                        <option value="csi">csi</option>
                        <option value="zero_delay">zero_delay</option>
                        <option value="zero_error">zero_error</option>
                        <option value="zero_complaint">zero_complaint</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Status Aktif</label>
                    <select v-model="form.is_active" class="form-input">
                        <option :value="true">Aktif</option>
                        <option :value="false">Nonaktif</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="form-label">Catatan</label>
                    <Textarea v-model="form.catatan" rows="3" class="!rounded-xl !border-slate-200 !px-4 !py-3" />
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button class="btn-secondary" :disabled="submitting" @click="showForm = false">Batal</button>
                <button class="btn-primary" :disabled="submitting" @click="submit">
                    {{ submitting ? 'Menyimpan...' : editMode ? 'Perbarui Komponen' : 'Simpan Komponen' }}
                </button>
            </div>
        </Dialog>

        <Dialog v-model:open="deleteState.open" title="Hapus Komponen KPI" class="max-w-lg">
            <p class="text-sm text-slate-600">Hapus <strong>{{ deleteState.name }}</strong> dari master komponen KPI?</p>
            <div class="mt-6 flex justify-end gap-3">
                <button class="btn-secondary" @click="deleteState.open = false">Batal</button>
                <button class="btn-primary" style="background: linear-gradient(135deg, #dc2626, #b91c1c);" @click="confirmDelete">Ya, Hapus</button>
            </div>
        </Dialog>
    </AppLayout>
</template>
