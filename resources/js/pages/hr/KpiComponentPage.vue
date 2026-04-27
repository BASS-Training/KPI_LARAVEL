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
    bobot: '',
    target: '',
    satuan: '',
    tipe: '',
    kpi_type: '',
    period: 'Bulanan',
    catatan: '',
    is_active: true,
});

const form = reactive(emptyForm());
const errors = reactive({});

const components = computed(() => store.components);

const periodOptions = ['Bulanan', 'Kuartalan', 'Tahunan'];
const tipeOptions = ['Kuantitatif', 'Kualitatif'];

onMounted(async () => {
    await store.fetchComponents();
});

function resetForm() {
    Object.assign(form, emptyForm());
    formError.value = '';
    Object.keys(errors).forEach((k) => { errors[k] = ''; });
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
        bobot: item.bobot !== null && item.bobot !== undefined ? Number(item.bobot) * 100 : '',
        target: item.target ?? '',
        satuan: item.satuan ?? '',
        tipe: item.tipe ?? '',
        kpi_type: item.kpi_type ?? '',
        period: item.period ?? 'Bulanan',
        catatan: item.catatan ?? '',
        is_active: item.is_active ?? true,
    });
    showForm.value = true;
}

function validate() {
    Object.assign(errors, { objectives: '', jabatan: '', bobot: '' });
    let valid = true;

    if (!form.objectives.trim()) { errors.objectives = 'Objektif wajib diisi.'; valid = false; }
    if (!form.jabatan.trim()) { errors.jabatan = 'Jabatan wajib diisi.'; valid = false; }
    if (form.bobot === '' || Number(form.bobot) < 0 || Number(form.bobot) > 100) {
        errors.bobot = 'Bobot harus antara 0 dan 100.'; valid = false;
    }

    return valid;
}

async function submit() {
    if (!validate()) return;
    submitting.value = true;
    formError.value = '';

    try {
        const payload = {
            jabatan: form.jabatan,
            objectives: form.objectives,
            strategy: form.strategy || null,
            bobot: form.bobot !== '' ? Number(form.bobot) / 100 : null,
            target: form.target !== '' ? Number(form.target) : null,
            satuan: form.satuan || null,
            tipe: form.tipe || null,
            kpi_type: form.kpi_type || null,
            period: form.period,
            catatan: form.catatan || null,
            is_active: form.is_active,
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
            <button class="btn-primary" @click="openCreate">
                <svg class="mr-1.5 h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 5v14M5 12h14"/>
                </svg>
                Tambah Komponen
            </button>
        </template>

        <!-- Hero -->
        <section class="page-hero">
            <div>
                <div class="page-hero-meta">HR Panel · Manajemen KPI</div>
                <h2 class="mt-4 text-2xl font-bold leading-tight md:text-3xl">Komponen KPI</h2>
                <p class="mt-2 max-w-xl text-sm leading-6 text-white/78">
                    Kelola komponen KPI per jabatan beserta bobot, target, dan periode penilaian.
                </p>
            </div>
        </section>

        <!-- List -->
        <section class="dashboard-panel overflow-hidden">
            <div class="border-b border-slate-200 px-6 py-5">
                <p class="section-heading">Daftar Komponen KPI</p>
                <h3 class="mt-1 text-lg font-bold text-slate-900">{{ components.length }} komponen terdaftar</h3>
            </div>

            <div class="p-6">
                <template v-if="store.isLoading">
                    <div class="space-y-3">
                        <Skeleton v-for="i in 5" :key="i" class="h-16 rounded-2xl" />
                    </div>
                </template>

                <template v-else-if="components.length">
                    <div class="space-y-3">
                        <div
                            v-for="comp in components"
                            :key="comp.id"
                            class="data-row"
                        >
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="text-sm font-semibold text-slate-900">{{ comp.objectives }}</span>
                                    <span v-if="comp.is_active" class="badge-success !text-[10px]">Aktif</span>
                                    <span v-else class="badge-neutral !text-[10px]">Nonaktif</span>
                                    <span v-if="comp.period" class="badge-info !text-[10px]">{{ comp.period }}</span>
                                </div>
                                <div class="mt-0.5 flex flex-wrap gap-x-3 text-xs text-slate-500">
                                    <span>{{ comp.jabatan }}</span>
                                    <span v-if="comp.bobot">· Bobot {{ (Number(comp.bobot) * 100).toFixed(0) }}%</span>
                                    <span v-if="comp.target">· Target {{ comp.target }}{{ comp.satuan ? ' ' + comp.satuan : '' }}</span>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <button class="btn-secondary !px-3 !py-1.5 text-xs" @click="openEdit(comp)">Edit</button>
                                <button
                                    class="btn-danger !px-3 !py-1.5 text-xs"
                                    @click="deleteState = { open: true, id: comp.id, name: comp.objectives }"
                                >
                                    Hapus
                                </button>
                            </div>
                        </div>
                    </div>
                </template>

                <div v-else class="py-14 text-center">
                    <svg class="mx-auto mb-3 h-10 w-10 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2"/>
                    </svg>
                    <p class="text-sm text-slate-400">Belum ada komponen KPI.</p>
                </div>
            </div>
        </section>

        <!-- Form Dialog -->
        <Dialog v-model:open="showForm" :title="editMode ? 'Edit Komponen KPI' : 'Tambah Komponen KPI'" class="max-w-2xl">
            <Alert v-if="formError" variant="danger" class="mb-4">{{ formError }}</Alert>

            <div class="mt-4 space-y-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="form-label">Objektif / Nama KPI <span class="text-red-500">*</span></label>
                        <Input v-model="form.objectives" placeholder="Contoh: Ketepatan Laporan Keuangan" />
                        <p v-if="errors.objectives" class="mt-1 text-xs text-red-500">{{ errors.objectives }}</p>
                    </div>

                    <div>
                        <label class="form-label">Jabatan <span class="text-red-500">*</span></label>
                        <Input v-model="form.jabatan" placeholder="Contoh: Staf Akuntansi" />
                        <p v-if="errors.jabatan" class="mt-1 text-xs text-red-500">{{ errors.jabatan }}</p>
                    </div>

                    <div>
                        <label class="form-label">Bobot (%) <span class="text-red-500">*</span></label>
                        <Input v-model="form.bobot" type="number" min="0" max="100" step="0.01" placeholder="20" />
                        <p v-if="errors.bobot" class="mt-1 text-xs text-red-500">{{ errors.bobot }}</p>
                    </div>

                    <div>
                        <label class="form-label">Target</label>
                        <Input v-model="form.target" type="number" step="0.01" placeholder="100" />
                    </div>

                    <div>
                        <label class="form-label">Satuan</label>
                        <Input v-model="form.satuan" placeholder="Contoh: %, laporan, transaksi" />
                    </div>

                    <div>
                        <label class="form-label">Tipe</label>
                        <select v-model="form.tipe" class="form-input">
                            <option value="">— Pilih Tipe —</option>
                            <option v-for="t in tipeOptions" :key="t" :value="t">{{ t }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="form-label">Periode</label>
                        <select v-model="form.period" class="form-input">
                            <option v-for="p in periodOptions" :key="p" :value="p">{{ p }}</option>
                        </select>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="form-label">Strategi / Inisiatif</label>
                        <Input v-model="form.strategy" placeholder="Strategi untuk mencapai KPI ini..." />
                    </div>

                    <div class="sm:col-span-2">
                        <label class="form-label">Catatan</label>
                        <Textarea v-model="form.catatan" rows="2" placeholder="Catatan tambahan..." />
                    </div>

                    <div class="flex items-center gap-2 sm:col-span-2">
                        <input
                            id="is_active"
                            v-model="form.is_active"
                            type="checkbox"
                            class="h-4 w-4 rounded border-slate-300 text-blue-600"
                        />
                        <label for="is_active" class="text-sm font-medium text-slate-700">Aktif</label>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3 border-t border-slate-100 pt-4">
                <button class="btn-secondary" :disabled="submitting" @click="showForm = false">Batal</button>
                <button class="btn-primary" :disabled="submitting" @click="submit">
                    {{ submitting ? 'Menyimpan...' : editMode ? 'Perbarui Komponen' : 'Simpan Komponen' }}
                </button>
            </div>
        </Dialog>

        <!-- Delete Dialog -->
        <Dialog v-model:open="deleteState.open" title="Hapus Komponen KPI" class="max-w-md">
            <p class="mt-3 text-sm text-slate-600">
                Hapus komponen <strong>{{ deleteState.name }}</strong>? Tindakan ini tidak dapat dibatalkan.
            </p>
            <div class="mt-6 flex justify-end gap-3">
                <button class="btn-secondary" @click="deleteState.open = false">Batal</button>
                <button class="btn-danger" @click="confirmDelete">Ya, Hapus</button>
            </div>
        </Dialog>
    </AppLayout>
</template>
