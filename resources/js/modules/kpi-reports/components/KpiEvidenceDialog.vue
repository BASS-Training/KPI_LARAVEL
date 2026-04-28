<script setup>
import Dialog from '@/components/ui/Dialog.vue';
import { computed } from 'vue';

const props = defineProps({
    open: Boolean,
    report: {
        type: Object,
        default: null,
    },
});

defineEmits(['update:open']);

const evidenceUrl = computed(() => props.report?.file_evidence_url || '');

const evidenceExtension = computed(() => {
    const source = props.report?.file_evidence || evidenceUrl.value;

    if (!source) {
        return '';
    }

    const sanitized = source.split('?')[0].toLowerCase();
    const extension = sanitized.split('.').pop();

    return extension || '';
});

const isImageEvidence = computed(() => ['png', 'jpg', 'jpeg', 'webp'].includes(evidenceExtension.value));
const isPdfEvidence = computed(() => evidenceExtension.value === 'pdf');
</script>

<template>
    <Dialog :open="open" title="Evidence Laporan KPI" class="max-w-4xl rounded-[28px] p-0" @update:open="$emit('update:open', $event)">
        <div class="p-6 space-y-4">

            <!-- Evidence context -->
            <div v-if="report?.kpi_indicator?.name" class="flex items-center gap-3 rounded-2xl border border-slate-100 bg-slate-50/80 px-4 py-3 dark:border-slate-800 dark:bg-slate-800/40">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-white shadow-sm">
                    <svg class="h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                        <polyline points="14 2 14 8 20 8" />
                    </svg>
                </div>
                <p class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ report.kpi_indicator.name }}</p>
            </div>

            <!-- Evidence viewer -->
            <div class="overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-700">
                <img
                    v-if="isImageEvidence && evidenceUrl"
                    :src="evidenceUrl"
                    :alt="report?.kpi_indicator?.name || 'Evidence'"
                    class="max-h-[65vh] w-full object-contain bg-slate-50"
                >
                <iframe
                    v-else-if="isPdfEvidence && evidenceUrl"
                    :src="evidenceUrl"
                    title="Evidence PDF"
                    class="h-[65vh] w-full"
                />
                <div v-else-if="evidenceUrl" class="flex min-h-[280px] flex-col items-center justify-center gap-4 bg-slate-50 dark:bg-slate-800/40">
                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white shadow-sm dark:bg-slate-700">
                        <svg class="h-7 w-7 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                            <polyline points="14 2 14 8 20 8" />
                        </svg>
                    </div>
                    <div class="text-center">
                        <p class="text-sm font-semibold text-slate-700 dark:text-slate-300">File tidak dapat dipratinjau</p>
                        <p class="mt-1 text-xs text-slate-400">Unduh file untuk membukanya di aplikasi yang sesuai.</p>
                    </div>
                    <a :href="evidenceUrl" target="_blank" rel="noreferrer" class="btn-primary">Unduh evidence</a>
                </div>
                <div v-else class="flex min-h-[280px] flex-col items-center justify-center gap-3 bg-slate-50 px-6 text-center dark:bg-slate-800/40">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl border border-dashed border-slate-300 dark:border-slate-600">
                        <svg class="h-6 w-6 text-slate-300 dark:text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                            <polyline points="14 2 14 8 20 8" />
                            <line x1="12" y1="18" x2="12" y2="12" />
                            <line x1="9" y1="15" x2="15" y2="15" />
                        </svg>
                    </div>
                    <p class="text-sm text-slate-400">File evidence belum tersedia untuk laporan ini.</p>
                </div>
            </div>

            <!-- Actions -->
            <div v-if="evidenceUrl" class="flex items-center justify-between border-t border-slate-100 pt-3 dark:border-slate-800">
                <p class="text-xs text-slate-400">
                    Format: <span class="font-semibold uppercase">{{ evidenceExtension || '—' }}</span>
                </p>
                <div class="flex gap-2">
                    <a
                        :href="evidenceUrl"
                        :download="true"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 active:scale-95 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                            <polyline points="7 10 12 15 17 10" />
                            <line x1="12" y1="15" x2="12" y2="3" />
                        </svg>
                        Unduh
                    </a>
                    <a
                        :href="evidenceUrl"
                        target="_blank"
                        rel="noreferrer"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 active:scale-95 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6" />
                            <polyline points="15 3 21 3 21 9" />
                            <line x1="10" y1="14" x2="21" y2="3" />
                        </svg>
                        Buka tab baru
                    </a>
                </div>
            </div>
        </div>
    </Dialog>
</template>
