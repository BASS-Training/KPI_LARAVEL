<script setup>
import { computed } from 'vue';
import Badge from '@/components/ui/Badge.vue';

const props = defineProps({
    // task fields dari TaskResource: tanggal, judul, status, ada_delay, ada_error, ada_komplain
    task: { type: Object, required: true },
});

const statusVariant = computed(() => {
    const s = props.task.status;
    if (s === 'Selesai') return 'success';
    if (s === 'Dalam Proses') return 'default';
    return 'warning'; // Pending
});

function formatDate(dateStr) {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
}
</script>

<template>
    <div class="flex items-start justify-between gap-3 py-2.5">
        <div class="min-w-0 flex-1">
            <p class="truncate text-sm text-slate-800 dark:text-slate-200">{{ task.judul }}</p>
            <p class="mt-0.5 text-xs text-slate-400">{{ formatDate(task.tanggal) }}</p>
        </div>
        <div class="flex shrink-0 items-center gap-1.5">
            <span v-if="task.ada_delay" class="text-xs text-red-500" title="Ada delay">⏱</span>
            <span v-if="task.ada_error" class="text-xs text-red-500" title="Ada error">⚠</span>
            <span v-if="task.ada_komplain" class="text-xs text-red-500" title="Ada komplain">💬</span>
            <Badge :variant="statusVariant">{{ task.status }}</Badge>
        </div>
    </div>
</template>
