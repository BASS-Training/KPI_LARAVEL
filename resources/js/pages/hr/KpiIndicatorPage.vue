<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import AppLayout from '@/components/layout/AppLayout.vue';
import Dialog from '@/components/ui/Dialog.vue';
import Input from '@/components/ui/Input.vue';
import Textarea from '@/components/ui/Textarea.vue';
import Alert from '@/components/ui/Alert.vue';
import Skeleton from '@/components/ui/Skeleton.vue';
import { useToast } from '@/composables/useToast';
import { useKpiIndicatorStore } from '@/stores/kpiIndicator';

const store = useKpiIndicatorStore();
const toast = useToast();

const indicators = computed(() => store.indicators);
const filterDeptId = ref('');

const showForm = ref(false);
const editMode = ref(false);
const selectedId = ref(null);
const submitting = ref(false);
const formError = ref('');
const deleteState = ref({ open: false, id: null, name: '' });

// ─── Threshold builder ────────────────────────────────────────────────────────
const emptyThreshold = () => ({ min_pct: 0, score_pct: 0 });

const emptyForm = () => ({
    name: '',
    description: '',
    weight: '',
    default_target_value: '',
    department_id: null,
    role_id: null,
    formula_type: 'percentage',
    formula_score: '',          // flat type
    thresholds: [               // threshold type
        { min_pct: 100, score_pct: 100 },
        { min_pct: 90,  score_pct: 80 },
        { min_pct: 70,  score_pct: 60 },
        { min_pct: 50,  score_pct: 40 },
        { min_pct: 0,   score_pct: 0  },
    ],
});

const form = reactive(emptyForm());
const errors = reactive({});

const filteredIndicators = computed(() => {
    if (!filterDeptId.value) return indicators.value;
    return indicators.value.filter((i) => i.department_id === Number(filterDeptId.value));
});

// Group by department
const grouped = computed(() => {
    const groups = {};
    for (const ind of filteredIndicators.value) {
        const key = ind.department?.nama ?? ind.role?.name ?? 'Tanpa Departemen';
        if (!groups[key]) groups[key] = [];
        groups[key].push(ind);
    }
    return groups;
});

watch(filterDeptId, () => {
    store.fetchIndicators(filterDeptId.value ? { department_id: filterDeptId.value } : {});
});

onMounted(async () => {
    await Promise.all([store.fetchIndicators(), store.fetchMeta()]);
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

    const fType = item.formula?.type ?? 'percentage';
    Object.assign(form, {
        name: item.name ?? '',
        description: item.description ?? '',
        weight: item.weight ?? '',
        default_target_value: item.default_target_value ?? '',
        department_id: item.department_id ?? null,
        role_id: item.role_id ?? null,
        formula_type: fType,
        formula_score: item.formula?.score ?? '',
        thresholds: item.formula?.thresholds
            ? item.formula.thresholds.map((t) => ({ ...t }))
            : emptyForm().thresholds,
    });
    showForm.value = true;
}

function addThreshold() {
    form.thresholds.push(emptyThreshold());
}

function removeThreshold(idx) {
    form.thresholds.splice(idx, 1);
}

function buildFormulaPayload() {
    switch (form.formula_type) {
        case 'threshold':
            return {
                type: 'threshold',
                thresholds: form.thresholds.map((t) => ({
                    min_pct: Number(t.min_pct),
                    score_pct: Number(t.score_pct),
                })),
            };
        case 'flat':
            return { type: 'flat', score: Number(form.formula_score) };
        default:
            return { type: form.formula_type };
    }
}

function validate() {
    Object.assign(errors, { name: '', weight: '', scope: '' });
    let valid = true;

    if (!form.name.trim()) { errors.name = 'Nama indikator wajib diisi.'; valid = false; }
    if (form.weight === '' || Number(form.weight) < 0 || Number(form.weight) > 100) {
        errors.weight = 'Bobot harus antara 0 dan 100.'; valid = false;
    }
    if (!form.department_id && !form.role_id) {
        errors.scope = 'Pilih departemen atau role.'; valid = false;
    }
    return valid;
}

async function submit() {
    if (!validate()) return;
    submitting.value = true;
    formError.value = '';

    try {
        const payload = {
            name: form.name,
            description: form.description || null,
            weight: Number(form.weight),
            default_target_value: form.default_target_value !== '' ? Number(form.default_target_value) : null,
            department_id: form.department_id || null,
            role_id: form.role_id || null,
            formula: buildFormulaPayload(),
        };

        if (editMode.value && selectedId.value) {
            await store.updateIndicator(selectedId.value, payload);
            toast.success('Indikator KPI berhasil diperbarui.');
        } else {
            await store.createIndicator(payload);
            toast.success('Indikator KPI berhasil ditambahkan.');
        }

        showForm.value = false;
        await store.fetchIndicators(filterDeptId.value ? { department_id: filterDeptId.value } : {});
    } catch (err) {
        formError.value = err.response?.data?.message || 'Gagal menyimpan indikator KPI.';
    } finally {
        submitting.value = false;
    }
}

async function confirmDelete() {
    try {
        await store.deleteIndicator(deleteState.value.id);
        toast.success('Indikator KPI berhasil dihapus.');
        deleteState.value.open = false;
    } catch (err) {
        toast.error(err.response?.data?.message || 'Gagal menghapus indikator KPI.');
    }
}

const formulaTypeLabels = {
    percentage:   'Persentase',
    conditional:  'Kondisional',
    threshold:    'Bertahap',
    zero_penalty: 'Zero Penalty',
    flat:         'Tetap',
};

function formulaBadgeClass(type) {
    const map = {
        percentage:   'badge-info',
        conditional:  'badge-success',
        threshold:    'badge-warning',
        zero_penalty: 'badge-danger',
        flat:         'badge-neutral',
    };
    return map[type] ?? 'badge-neutral';
}
</script>

<template>
    <AppLayout>
        <template #topbar-actions>
            <button class="btn-primary" @click="openCreate">
                <svg class="mr-1.5 h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 5v14M5 12h14"/>
                </svg>
                Tambah Indikator
            </button>
        </template>

        <!-- Hero -->
        <section class="page-hero">
            <div>
                <div class="page-hero-meta">HR Panel · Manajemen KPI</div>
                <h2 class="mt-4 text-2xl font-bold leading-tight md:text-3xl">Indikator KPI</h2>
                <p class="mt-2 max-w-xl text-sm leading-6 text-white/78">
                    Kelola indikator KPI per departemen beserta formula penilaian yang digunakan.
                </p>
            </div>
        </section>

        <!-- Filter -->
        <section class="dashboard-panel overflow-hidden">
            <div class="border-b border-slate-200 px-6 py-4">
                <p class="section-heading">Filter</p>
            </div>
            <div class="flex flex-wrap items-center gap-4 p-6">
                <div class="w-full sm:w-72">
                    <label class="form-label">Departemen</label>
                    <select v-model="filterDeptId" class="form-input">
                        <option value="">— Semua Departemen —</option>
                        <option
                            v-for="dept in store.meta.departments"
                            :key="dept.id"
                            :value="dept.id"
                        >
                            {{ dept.nama }} ({{ dept.kode }})
                        </option>
                    </select>
                </div>
            </div>
        </section>

        <!-- List -->
        <section class="dashboard-panel overflow-hidden">
            <div class="border-b border-slate-200 px-6 py-5">
                <p class="section-heading">Daftar Indikator</p>
                <h3 class="mt-1 text-lg font-bold text-slate-900">
                    {{ filteredIndicators.length }} indikator
                </h3>
            </div>

            <div class="p-6">
                <template v-if="store.isLoading">
                    <div class="space-y-3">
                        <Skeleton v-for="i in 5" :key="i" class="h-16 rounded-2xl" />
                    </div>
                </template>

                <template v-else-if="filteredIndicators.length">
                    <div v-for="(items, groupName) in grouped" :key="groupName" class="mb-6">
                        <h4 class="mb-3 flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-slate-400">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M3 9h18M9 21V9m6 12V9M3 3h18v18H3z"/>
                            </svg>
                            {{ groupName }}
                        </h4>

                        <div class="space-y-2">
                            <div
                                v-for="ind in items"
                                :key="ind.id"
                                class="data-row"
                            >
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="text-sm font-semibold text-slate-900">{{ ind.name }}</span>
                                        <span :class="[formulaBadgeClass(ind.formula?.type), '!text-[10px]']">
                                            {{ formulaTypeLabels[ind.formula?.type] ?? ind.formula?.type ?? '-' }}
                                        </span>
                                    </div>
                                    <div class="mt-0.5 flex flex-wrap gap-x-3 text-xs text-slate-500">
                                        <span>Bobot <strong>{{ ind.weight }}%</strong></span>
                                        <span v-if="ind.default_target_value">· Target default {{ ind.default_target_value }}</span>
                                        <span v-if="ind.description" class="hidden sm:inline">· {{ ind.description }}</span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button class="btn-secondary !px-3 !py-1.5 text-xs" @click="openEdit(ind)">Edit</button>
                                    <button
                                        class="btn-danger !px-3 !py-1.5 text-xs"
                                        @click="deleteState = { open: true, id: ind.id, name: ind.name }"
                                    >
                                        Hapus
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <div v-else class="py-14 text-center">
                    <svg class="mx-auto mb-3 h-10 w-10 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <circle cx="12" cy="12" r="9"/><path d="M9 9h.01M15 9h.01M9 15h6"/>
                    </svg>
                    <p class="text-sm text-slate-400">Belum ada indikator KPI.</p>
                </div>
            </div>
        </section>

        <!-- Form Dialog -->
        <Dialog v-model:open="showForm" :title="editMode ? 'Edit Indikator KPI' : 'Tambah Indikator KPI'" class="max-w-2xl">
            <Alert v-if="formError" variant="danger" class="mb-4">{{ formError }}</Alert>

            <div class="mt-4 space-y-5">
                <!-- Section 1: Identitas -->
                <div class="rounded-xl border border-slate-200 p-4">
                    <p class="mb-3 flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-slate-500">
                        <span class="flex h-5 w-5 items-center justify-center rounded-full bg-blue-100 text-[10px] font-bold text-blue-600">1</span>
                        Identitas Indikator
                    </p>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label class="form-label">Nama Indikator <span class="text-red-500">*</span></label>
                            <Input v-model="form.name" placeholder="Contoh: Invoice Accuracy Rate" />
                            <p v-if="errors.name" class="mt-1 text-xs text-red-500">{{ errors.name }}</p>
                        </div>

                        <div>
                            <label class="form-label">Bobot (%) <span class="text-red-500">*</span></label>
                            <Input v-model="form.weight" type="number" min="0" max="100" step="0.01" placeholder="35" />
                            <p v-if="errors.weight" class="mt-1 text-xs text-red-500">{{ errors.weight }}</p>
                        </div>

                        <div>
                            <label class="form-label">Target Default</label>
                            <Input v-model="form.default_target_value" type="number" step="0.01" placeholder="100" />
                        </div>

                        <div class="sm:col-span-2">
                            <label class="form-label">Deskripsi</label>
                            <Textarea v-model="form.description" rows="2" placeholder="Penjelasan singkat indikator ini..." />
                        </div>
                    </div>
                </div>

                <!-- Section 2: Scope -->
                <div class="rounded-xl border border-slate-200 p-4">
                    <p class="mb-3 flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-slate-500">
                        <span class="flex h-5 w-5 items-center justify-center rounded-full bg-blue-100 text-[10px] font-bold text-blue-600">2</span>
                        Scope (Departemen atau Role)
                    </p>
                    <p v-if="errors.scope" class="mb-2 text-xs text-red-500">{{ errors.scope }}</p>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="form-label">Departemen</label>
                            <select v-model="form.department_id" class="form-input">
                                <option :value="null">— Tidak ada —</option>
                                <option v-for="dept in store.meta.departments" :key="dept.id" :value="dept.id">
                                    {{ dept.nama }} ({{ dept.kode }})
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="form-label">Role / Jabatan</label>
                            <select v-model="form.role_id" class="form-input">
                                <option :value="null">— Tidak ada —</option>
                                <option v-for="role in store.meta.roles" :key="role.id" :value="role.id">
                                    {{ role.name }}
                                </option>
                            </select>
                        </div>
                    </div>
                    <p class="mt-2 text-[11px] text-slate-400">
                        Pilih salah satu. Departemen lebih prioritas saat penghitungan.
                    </p>
                </div>

                <!-- Section 3: Formula -->
                <div class="rounded-xl border border-slate-200 p-4">
                    <p class="mb-3 flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-slate-500">
                        <span class="flex h-5 w-5 items-center justify-center rounded-full bg-blue-100 text-[10px] font-bold text-blue-600">3</span>
                        Formula Penilaian
                    </p>

                    <div class="mb-4">
                        <label class="form-label">Tipe Formula</label>
                        <select v-model="form.formula_type" class="form-input">
                            <option v-for="ft in store.meta.formula_types" :key="ft.value" :value="ft.value">
                                {{ ft.label }}
                            </option>
                        </select>
                    </div>

                    <!-- Flat: score -->
                    <div v-if="form.formula_type === 'flat'">
                        <label class="form-label">Skor Tetap (0–1)</label>
                        <Input v-model="form.formula_score" type="number" min="0" max="1" step="0.01" placeholder="0.75" />
                        <p class="mt-1 text-[11px] text-slate-400">Nilai 0.75 = selalu dapat 75% dari bobot.</p>
                    </div>

                    <!-- Threshold: thresholds table -->
                    <div v-if="form.formula_type === 'threshold'">
                        <label class="form-label">Tabel Rentang Skor</label>
                        <div class="mb-2 grid grid-cols-[1fr_1fr_auto] gap-2 text-[11px] font-semibold text-slate-500">
                            <span>Min Pencapaian (%)</span>
                            <span>Skor Diberikan (%)</span>
                            <span></span>
                        </div>
                        <div v-for="(row, idx) in form.thresholds" :key="idx" class="mb-2 grid grid-cols-[1fr_1fr_auto] gap-2">
                            <Input v-model="row.min_pct" type="number" min="0" max="100" step="1" placeholder="90" />
                            <Input v-model="row.score_pct" type="number" min="0" max="100" step="1" placeholder="80" />
                            <button
                                type="button"
                                class="flex h-9 w-9 items-center justify-center rounded-lg border border-red-100 bg-red-50 text-red-500 hover:bg-red-100"
                                @click="removeThreshold(idx)"
                            >
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M18 6 6 18M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                        <button type="button" class="btn-secondary mt-2 !text-xs" @click="addThreshold">
                            + Tambah Rentang
                        </button>
                    </div>

                    <!-- Info for other types -->
                    <div v-if="['percentage', 'conditional', 'zero_penalty'].includes(form.formula_type)" class="rounded-lg bg-slate-50 p-3 text-xs text-slate-500">
                        <template v-if="form.formula_type === 'percentage'">
                            <strong>Persentase:</strong> Skor = (aktual / target) × bobot. Tidak ada konfigurasi tambahan.
                        </template>
                        <template v-else-if="form.formula_type === 'conditional'">
                            <strong>Kondisional:</strong> Skor penuh jika aktual ≥ target, skor nol jika tidak. Tidak ada konfigurasi tambahan.
                        </template>
                        <template v-else-if="form.formula_type === 'zero_penalty'">
                            <strong>Zero Penalty:</strong> Skor penuh jika aktual = 0 (nol pelanggaran/error). Tidak ada konfigurasi tambahan.
                        </template>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3 border-t border-slate-100 pt-4">
                <button class="btn-secondary" :disabled="submitting" @click="showForm = false">Batal</button>
                <button class="btn-primary" :disabled="submitting" @click="submit">
                    {{ submitting ? 'Menyimpan...' : editMode ? 'Perbarui Indikator' : 'Simpan Indikator' }}
                </button>
            </div>
        </Dialog>

        <!-- Delete Dialog -->
        <Dialog v-model:open="deleteState.open" title="Hapus Indikator KPI" class="max-w-md">
            <p class="mt-3 text-sm text-slate-600">
                Hapus indikator <strong>{{ deleteState.name }}</strong>? Tindakan ini tidak dapat dibatalkan.
            </p>
            <div class="mt-6 flex justify-end gap-3">
                <button class="btn-secondary" @click="deleteState.open = false">Batal</button>
                <button class="btn-danger" @click="confirmDelete">Ya, Hapus</button>
            </div>
        </Dialog>
    </AppLayout>
</template>
