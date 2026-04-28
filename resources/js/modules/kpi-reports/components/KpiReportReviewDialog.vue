<script setup>
import { computed, reactive, watch } from 'vue';
import Dialog from '@/components/ui/Dialog.vue';
import Textarea from '@/components/ui/Textarea.vue';
import { formatPercentage, progressTone } from '@/modules/kpi-reports/utils';

const props = defineProps({
    open: Boolean,
    action: {
        type: String,
        default: 'approved',
    },
    report: {
        type: Object,
        default: null,
    },
    isSubmitting: Boolean,
});

const emit = defineEmits(['update:open', 'submit']);

const state = reactive({
    review_note: '',
    error: '',
});

const isApprove = computed(() => props.action === 'approved');

watch(
    () => props.open,
    (isOpen) => {
        if (!isOpen) return;
        state.review_note = props.report?.review_note ?? '';
        state.error = '';
    },
    { immediate: true }
);

function handleSubmit() {
    if (!isApprove.value && !state.review_note.trim()) {
        state.error = 'Catatan wajib diisi saat menolak laporan.';
        return;
    }

    emit('submit', {
        id: props.report?.id,
        payload: {
            status: props.action,
            review_note: state.review_note.trim() || null,
        },
    });
}
</script>

<template>
    <Dialog
        :open="open"
        class="max-w-lg !p-0 overflow-hidden"
        @update:open="$emit('update:open', $event)"
    >
        <!-- ── Coloured header banner ────────────────────────────────────── -->
        <div
            :class="[
                'flex items-center gap-4 px-6 py-5',
                isApprove
                    ? 'bg-emerald-50 border-b border-emerald-100'
                    : 'bg-rose-50 border-b border-rose-100',
            ]"
        >
            <!-- Icon circle -->
            <div
                :class="[
                    'flex h-11 w-11 shrink-0 items-center justify-center rounded-full',
                    isApprove ? 'bg-emerald-100' : 'bg-rose-100',
                ]"
            >
                <!-- Checkmark for approve -->
                <svg
                    v-if="isApprove"
                    class="h-5 w-5 text-emerald-600"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2.5"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >
                    <path d="M20 6 9 17l-5-5"/>
                </svg>
                <!-- X for reject -->
                <svg
                    v-else
                    class="h-5 w-5 text-rose-600"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2.5"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >
                    <path d="M18 6 6 18M6 6l12 12"/>
                </svg>
            </div>

            <!-- Title + subtitle -->
            <div class="min-w-0">
                <h2
                    :class="[
                        'text-base font-bold leading-tight',
                        isApprove ? 'text-emerald-900' : 'text-rose-900',
                    ]"
                >
                    {{ isApprove ? 'Setujui Laporan KPI' : 'Tolak Laporan KPI' }}
                </h2>
                <p
                    :class="[
                        'mt-0.5 text-xs',
                        isApprove ? 'text-emerald-700/80' : 'text-rose-700/80',
                    ]"
                >
                    {{ isApprove
                        ? 'Laporan akan diverifikasi dan nilai KPI pegawai diperbarui.'
                        : 'Laporan dikembalikan ke pegawai untuk diperbaiki.' }}
                </p>
            </div>

            <!-- Close button -->
            <button
                type="button"
                class="ml-auto flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:bg-black/5 hover:text-slate-600"
                @click="$emit('update:open', false)"
            >
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 6 6 18M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- ── Body ──────────────────────────────────────────────────────── -->
        <div class="space-y-5 p-6">

            <!-- Report context card -->
            <div v-if="report" class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <div class="flex items-start gap-3">
                    <!-- Avatar initials -->
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-200 text-xs font-bold text-slate-600">
                        {{ (report.user?.nama || '?').slice(0, 1).toUpperCase() }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold text-slate-900">{{ report.user?.nama || '-' }}</p>
                        <p class="mt-0.5 truncate text-xs text-slate-500">
                            {{ report.user?.jabatan || '-' }}
                            <span v-if="report.period_label"> · {{ report.period_label }}</span>
                        </p>
                    </div>
                </div>

                <div class="mt-3 border-t border-slate-200 pt-3">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Komponen KPI</p>
                    <p class="mt-1 text-sm font-semibold text-slate-900 leading-snug">
                        {{ report.kpi_indicator?.name || '-' }}
                    </p>
                </div>

                <!-- Target / Aktual / Capaian -->
                <div class="mt-3 grid grid-cols-3 gap-3">
                    <div class="rounded-xl bg-white p-2.5 text-center border border-slate-100">
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Target</p>
                        <p class="mt-1 text-sm font-bold text-slate-800">{{ report.nilai_target ?? '-' }}</p>
                    </div>
                    <div class="rounded-xl bg-white p-2.5 text-center border border-slate-100">
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Aktual</p>
                        <p class="mt-1 text-sm font-bold text-slate-800">{{ report.nilai_aktual ?? '-' }}</p>
                    </div>
                    <div
                        :class="[
                            'rounded-xl p-2.5 text-center border',
                            progressTone(report.persentase).surface,
                        ]"
                    >
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Capaian</p>
                        <p :class="['mt-1 text-sm font-bold', progressTone(report.persentase).text]">
                            {{ formatPercentage(report.persentase) }}
                        </p>
                    </div>
                </div>

                <!-- Progress bar -->
                <div class="mt-3">
                    <div class="h-1.5 w-full overflow-hidden rounded-full bg-slate-200">
                        <div
                            :class="['h-full rounded-full transition-all duration-500', progressTone(report.persentase).bar]"
                            :style="{ width: `${Math.min(100, Number(report.persentase || 0))}%` }"
                        />
                    </div>
                </div>
            </div>

            <!-- Note textarea -->
            <div>
                <label class="form-label">
                    Catatan Reviewer
                    <span v-if="!isApprove" class="ml-0.5 text-rose-500">*</span>
                    <span v-else class="ml-1 text-[11px] font-normal text-slate-400">(opsional)</span>
                </label>
                <Textarea
                    v-model="state.review_note"
                    rows="3"
                    :placeholder="isApprove
                        ? 'Tambahkan arahan singkat untuk pegawai jika diperlukan...'
                        : 'Jelaskan alasan penolakan dan apa yang perlu diperbaiki...'"
                    :class="[
                        'mt-1.5 resize-none',
                        !isApprove && state.error ? '!border-rose-300 focus:!ring-rose-200' : '',
                    ]"
                />
                <!-- Error -->
                <p v-if="state.error" class="mt-1.5 flex items-center gap-1.5 text-xs text-rose-600">
                    <svg class="h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="9"/><path d="M12 8v4M12 16h.01"/>
                    </svg>
                    {{ state.error }}
                </p>
            </div>
        </div>

        <!-- ── Footer actions ────────────────────────────────────────────── -->
        <div class="flex items-center justify-end gap-3 border-t border-slate-100 bg-slate-50/60 px-6 py-4">
            <button
                type="button"
                class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50 hover:text-slate-900 disabled:opacity-50"
                :disabled="isSubmitting"
                @click="$emit('update:open', false)"
            >
                Batal
            </button>

            <!-- Approve button -->
            <button
                v-if="isApprove"
                type="button"
                :disabled="isSubmitting"
                class="flex items-center gap-2 rounded-xl bg-emerald-600 px-5 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700 active:scale-[0.98] disabled:opacity-60"
                @click="handleSubmit"
            >
                <svg v-if="!isSubmitting" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path d="M20 6 9 17l-5-5"/>
                </svg>
                <svg v-else class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 12a9 9 0 1 1-6.219-8.56"/>
                </svg>
                {{ isSubmitting ? 'Memproses...' : 'Setujui Laporan' }}
            </button>

            <!-- Reject button -->
            <button
                v-else
                type="button"
                :disabled="isSubmitting"
                class="flex items-center gap-2 rounded-xl bg-rose-600 px-5 py-2 text-sm font-semibold text-white transition hover:bg-rose-700 active:scale-[0.98] disabled:opacity-60"
                @click="handleSubmit"
            >
                <svg v-if="!isSubmitting" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path d="M18 6 6 18M6 6l12 12"/>
                </svg>
                <svg v-else class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 12a9 9 0 1 1-6.219-8.56"/>
                </svg>
                {{ isSubmitting ? 'Memproses...' : 'Tolak Laporan' }}
            </button>
        </div>
    </Dialog>
</template>
