<script setup>
import Avatar from '@/components/ui/Avatar.vue';
import Button from '@/components/ui/Button.vue';
import Dialog from '@/components/ui/Dialog.vue';
import { formatDate, formatDateTime, formatPercentage, scoreClass, scoreLabel, statusClass, statusLabel } from '@/modules/kpi-reports/utils';

defineProps({
    open: Boolean,
    report: {
        type: Object,
        default: null,
    },
    showEmployee: Boolean,
});

defineEmits(['update:open', 'evidence']);
</script>

<template>
    <Dialog :open="open" title="Detail laporan KPI" class="max-w-4xl rounded-[28px] p-0" @update:open="$emit('update:open', $event)">
        <div v-if="report" class="space-y-6 p-6">
            <div class="flex items-start gap-4 rounded-3xl bg-slate-50 p-4">
                <Avatar v-if="showEmployee" :name="report.user?.nama || '?'" size="sm" />
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <span v-if="showEmployee" class="font-semibold text-slate-950">{{ report.user?.nama || '-' }}</span>
                        <span :class="['rounded-full border px-2.5 py-1 text-[11px] font-semibold', statusClass(report.status)]">
                            {{ statusLabel(report.status) }}
                        </span>
                        <span
                            v-if="report.score_label"
                            :class="['rounded-full border px-2.5 py-1 text-[11px] font-semibold', scoreClass(report.score_label)]"
                        >
                            {{ scoreLabel(report.score_label) }}
                        </span>
                    </div>
                    <h3 class="mt-3 text-xl font-semibold text-slate-950">{{ report.kpi_indicator?.name || '-' }}</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-500">
                        {{ report.user?.jabatan || 'Pegawai' }} | {{ report.period_label || '-' }} | {{ report.period_type || '-' }}
                    </p>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-3xl border border-slate-200 p-4">
                    <p class="text-xs uppercase tracking-[0.14em] text-slate-500">Nilai target</p>
                    <p class="mt-2 text-xl font-semibold text-slate-950">{{ report.nilai_target ?? '-' }}</p>
                </div>
                <div class="rounded-3xl border border-slate-200 p-4">
                    <p class="text-xs uppercase tracking-[0.14em] text-slate-500">Nilai aktual</p>
                    <p class="mt-2 text-xl font-semibold text-slate-950">{{ report.nilai_aktual ?? '-' }}</p>
                </div>
                <div class="rounded-3xl border border-slate-200 p-4">
                    <p class="text-xs uppercase tracking-[0.14em] text-slate-500">Persentase</p>
                    <p class="mt-2 text-xl font-semibold text-slate-950">{{ formatPercentage(report.persentase) }}</p>
                </div>
                <div class="rounded-3xl border border-slate-200 p-4">
                    <p class="text-xs uppercase tracking-[0.14em] text-slate-500">Tanggal</p>
                    <p class="mt-2 text-xl font-semibold text-slate-950">{{ formatDate(report.tanggal) }}</p>
                </div>
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                <div class="rounded-3xl border border-slate-200 p-4">
                    <p class="text-xs uppercase tracking-[0.14em] text-slate-500">Catatan pegawai</p>
                    <p class="mt-3 text-sm leading-6 text-slate-600">{{ report.catatan || 'Belum ada catatan tambahan.' }}</p>
                </div>
                <div class="rounded-3xl border border-slate-200 p-4">
                    <p class="text-xs uppercase tracking-[0.14em] text-slate-500">Catatan reviewer</p>
                    <p class="mt-3 text-sm leading-6 text-slate-600">{{ report.review_note || 'Belum ada catatan reviewer.' }}</p>
                </div>
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                <div class="rounded-3xl border border-slate-200 p-4 text-sm text-slate-600">
                    <p><span class="font-semibold text-slate-900">Dibuat:</span> {{ formatDateTime(report.created_at) }}</p>
                    <p class="mt-2"><span class="font-semibold text-slate-900">Diajukan:</span> {{ formatDateTime(report.submitted_at) }}</p>
                    <p class="mt-2"><span class="font-semibold text-slate-900">Direview:</span> {{ formatDateTime(report.reviewed_at) }}</p>
                </div>
                <div class="rounded-3xl border border-slate-200 p-4">
                    <p class="text-xs uppercase tracking-[0.14em] text-slate-500">Evidence</p>
                    <div class="mt-3 flex items-center gap-3">
                        <Button v-if="report.file_evidence_url" variant="outline" @click="$emit('evidence', report)">Lihat evidence</Button>
                        <p v-else class="text-sm text-slate-500">Belum ada file evidence.</p>
                    </div>
                </div>
            </div>
        </div>
    </Dialog>
</template>
