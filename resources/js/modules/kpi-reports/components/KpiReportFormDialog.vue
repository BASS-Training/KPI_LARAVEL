<script setup>
import { computed, reactive, watch } from 'vue';
import Alert from '@/components/ui/Alert.vue';
import Button from '@/components/ui/Button.vue';
import Dialog from '@/components/ui/Dialog.vue';
import Input from '@/components/ui/Input.vue';
import Select from '@/components/ui/Select.vue';
import Textarea from '@/components/ui/Textarea.vue';
import { useFormValidation } from '@/composables/useFormValidation';
import { periodTypeOptions } from '@/modules/kpi-reports/constants';
import { buildPeriodLabel } from '@/modules/kpi-reports/utils';

const props = defineProps({
    open: Boolean,
    report: {
        type: Object,
        default: null,
    },
    componentOptions: {
        type: Array,
        default: () => [],
    },
    components: {
        type: Array,
        default: () => [],
    },
    isSaving: Boolean,
    isUploadingEvidence: Boolean,
    errorMessage: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['update:open', 'submit']);

const form = reactive({
    kpi_component_id: '',
    period_type: 'monthly',
    tanggal: new Date().toISOString().slice(0, 10),
    period_label: '',
    nilai_target: '',
    nilai_aktual: '',
    catatan: '',
    evidence: null,
});

const { errors, touched, touchField, validateField, validateForm, resetValidation } = useFormValidation({
    kpi_component_id: (value) => (!value ? 'Komponen KPI wajib dipilih.' : ''),
    tanggal: (value) => (!value ? 'Tanggal wajib diisi.' : ''),
    nilai_aktual: (value) => (value === '' ? 'Nilai aktual wajib diisi.' : ''),
});

const selectedComponent = computed(() =>
    props.components.find((component) => String(component.id) === String(form.kpi_component_id))
);

const estimatedPercentage = computed(() => {
    const actual = Number(form.nilai_aktual);
    const target = Number(form.nilai_target || selectedComponent.value?.target || 0);

    if (!Number.isFinite(actual)) {
        return 0;
    }

    if (!target) {
        return actual > 0 ? 100 : 0;
    }

    return Math.round((actual / target) * 100);
});

watch(
    () => props.open,
    (isOpen) => {
        if (!isOpen) {
            return;
        }

        Object.assign(form, {
            kpi_component_id: props.report ? String(props.report.kpi_component_id) : '',
            period_type: props.report?.period_type ?? 'monthly',
            tanggal: props.report?.tanggal ?? new Date().toISOString().slice(0, 10),
            period_label: props.report?.period_label ?? '',
            nilai_target: props.report?.nilai_target ?? '',
            nilai_aktual: props.report?.nilai_aktual ?? '',
            catatan: props.report?.catatan ?? '',
            evidence: null,
        });

        if (!form.period_label) {
            form.period_label = buildPeriodLabel(form.period_type, form.tanggal);
        }

        resetValidation();
    },
    { immediate: true }
);

watch(
    () => [form.period_type, form.tanggal],
    () => {
        form.period_label = buildPeriodLabel(form.period_type, form.tanggal);
    }
);

watch(
    () => form.kpi_component_id,
    () => {
        if (!form.nilai_target && selectedComponent.value?.target != null) {
            form.nilai_target = selectedComponent.value.target;
        }
    }
);

function onBlur(field) {
    touchField(field);
    validateField(field, form[field], form);
}

function onFileChange(event) {
    form.evidence = event.target.files?.[0] ?? null;
}

function handleSubmit(status = 'draft') {
    const valid = validateForm(form);

    Object.keys(touched).forEach((field) => {
        touched[field] = true;
    });

    if (!valid) {
        return;
    }

    emit('submit', {
        id: props.report?.id,
        payload: {
            kpi_component_id: Number(form.kpi_component_id),
            period_type: form.period_type,
            tanggal: form.tanggal,
            period_label: form.period_label,
            nilai_target: form.nilai_target === '' ? null : Number(form.nilai_target),
            nilai_aktual: Number(form.nilai_aktual),
            catatan: form.catatan || null,
            status,
        },
        evidence: form.evidence,
    });
}
</script>

<template>
    <Dialog
        :open="open"
        :title="report ? 'Perbarui Laporan KPI' : 'Buat Laporan KPI'"
        class="max-w-2xl rounded-[28px] p-0"
        @update:open="$emit('update:open', $event)"
    >
        <div class="p-6 space-y-6">

            <!-- Section: Komponen & Periode -->
            <div class="space-y-4">
                <div class="flex items-center gap-2">
                    <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-blue-100 text-[11px] font-bold text-blue-600">1</div>
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Komponen & Periode</p>
                </div>
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label class="form-label">Komponen KPI</label>
                        <Select
                            v-model="form.kpi_component_id"
                            :options="componentOptions"
                            placeholder="Pilih komponen KPI"
                            @blur="onBlur('kpi_component_id')"
                        />
                        <p v-if="touched.kpi_component_id && errors.kpi_component_id" class="mt-1 text-xs text-rose-600">{{ errors.kpi_component_id }}</p>
                    </div>

                    <div>
                        <label class="form-label">Periode</label>
                        <Select v-model="form.period_type" :options="periodTypeOptions" />
                    </div>
                    <div>
                        <label class="form-label">Tanggal</label>
                        <Input v-model="form.tanggal" type="date" @blur="onBlur('tanggal')" />
                        <p v-if="touched.tanggal && errors.tanggal" class="mt-1 text-xs text-rose-600">{{ errors.tanggal }}</p>
                    </div>
                </div>
            </div>

            <div class="border-t border-slate-100 dark:border-slate-800" />

            <!-- Section: Realisasi -->
            <div class="space-y-4">
                <div class="flex items-center gap-2">
                    <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-blue-100 text-[11px] font-bold text-blue-600">2</div>
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Nilai Realisasi</p>
                </div>
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="form-label">Nilai Target</label>
                        <Input v-model="form.nilai_target" type="number" placeholder="Otomatis dari komponen" />
                    </div>
                    <div>
                        <label class="form-label">Nilai Aktual</label>
                        <Input v-model="form.nilai_aktual" type="number" placeholder="Masukkan realisasi" @blur="onBlur('nilai_aktual')" />
                        <p v-if="touched.nilai_aktual && errors.nilai_aktual" class="mt-1 text-xs text-rose-600">{{ errors.nilai_aktual }}</p>
                    </div>
                </div>

                <!-- Progress Estimator -->
                <div class="rounded-2xl border border-slate-100 bg-slate-50/80 p-4 dark:border-slate-800 dark:bg-slate-800/40">
                    <div class="flex items-center justify-between text-sm">
                        <span class="font-medium text-slate-600 dark:text-slate-400">Estimasi capaian</span>
                        <span
                            class="text-lg font-bold tabular-nums"
                            :class="estimatedPercentage >= 100 ? 'text-emerald-600' : estimatedPercentage >= 80 ? 'text-sky-600' : estimatedPercentage >= 50 ? 'text-amber-600' : 'text-rose-600'"
                        >
                            {{ estimatedPercentage }}%
                        </span>
                    </div>
                    <div class="mt-3 h-2 overflow-hidden rounded-full bg-white dark:bg-slate-700">
                        <div
                            class="h-2 rounded-full transition-all duration-500"
                            :class="estimatedPercentage >= 100 ? 'bg-emerald-500' : estimatedPercentage >= 80 ? 'bg-sky-500' : estimatedPercentage >= 50 ? 'bg-amber-500' : 'bg-rose-500'"
                            :style="{ width: `${Math.min(100, estimatedPercentage)}%` }"
                        />
                    </div>
                    <p class="mt-2 text-[11px] text-slate-400 dark:text-slate-500">
                        {{ estimatedPercentage >= 100 ? 'Target tercapai' : estimatedPercentage >= 80 ? 'Hampir tercapai' : estimatedPercentage >= 50 ? 'Di bawah target' : 'Perlu perhatian' }}
                    </p>
                </div>
            </div>

            <div class="border-t border-slate-100 dark:border-slate-800" />

            <!-- Section: Catatan & Evidence -->
            <div class="space-y-4">
                <div class="flex items-center gap-2">
                    <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-blue-100 text-[11px] font-bold text-blue-600">3</div>
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Catatan & Evidence</p>
                </div>

                <div>
                    <label class="form-label">Catatan</label>
                    <Textarea v-model="form.catatan" rows="3" placeholder="Tambahkan konteks hasil kerja, kendala, atau highlight penting." />
                </div>

                <div>
                    <label class="form-label">Upload Evidence</label>
                    <label
                        class="group flex cursor-pointer flex-col items-center justify-center gap-2 rounded-2xl border-2 border-dashed border-slate-200 bg-slate-50/80 px-6 py-6 transition hover:border-blue-300 hover:bg-blue-50/50 dark:border-slate-700 dark:bg-slate-800/40 dark:hover:border-blue-700 dark:hover:bg-blue-950/20"
                    >
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-white shadow-sm transition group-hover:bg-blue-50 dark:bg-slate-700">
                            <svg class="h-5 w-5 text-slate-400 transition group-hover:text-blue-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                                <polyline points="17 8 12 3 7 8" />
                                <line x1="12" y1="3" x2="12" y2="15" />
                            </svg>
                        </div>
                        <div class="text-center">
                            <p class="text-sm font-medium text-slate-700 dark:text-slate-300">
                                {{ form.evidence ? form.evidence.name : 'Klik untuk upload evidence' }}
                            </p>
                            <p class="mt-0.5 text-xs text-slate-400">PDF, PNG, JPG, DOC, XLSX — max 10 MB</p>
                        </div>
                        <input
                            type="file"
                            accept=".pdf,.png,.jpg,.jpeg,.doc,.docx,.xlsx"
                            class="sr-only"
                            @change="onFileChange"
                        >
                    </label>
                </div>
            </div>

            <Alert v-if="errorMessage" variant="danger">{{ errorMessage }}</Alert>

            <div class="flex justify-end gap-3 border-t border-slate-100 pt-4 dark:border-slate-800">
                <Button variant="outline" @click="$emit('update:open', false)">Batal</Button>
                <Button variant="outline" :disabled="isSaving || isUploadingEvidence" @click="handleSubmit('draft')">
                    {{ isSaving ? 'Menyimpan...' : isUploadingEvidence ? 'Mengunggah...' : 'Simpan draft' }}
                </Button>
                <Button :disabled="isSaving || isUploadingEvidence" @click="handleSubmit('submitted')">
                    {{ isSaving ? 'Menyimpan...' : isUploadingEvidence ? 'Mengunggah...' : report ? 'Submit perubahan' : 'Submit ke HR' }}
                </Button>
            </div>
        </div>
    </Dialog>
</template>
