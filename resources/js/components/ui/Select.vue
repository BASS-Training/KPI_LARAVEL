<script setup>
import { cn } from '@/lib/utils';
import { computed } from 'vue';

const props = defineProps({
    modelValue: { type: [String, Number], default: '' },
    options: {
        type: Array,
        default: () => [],
        // format: [{ value: 'x', label: 'X' }]
    },
    placeholder: { type: String, default: 'Pilih...' },
    disabled: Boolean,
    class: { type: String, default: '' },
});

defineEmits(['update:modelValue', 'blur']);

const shouldRenderPlaceholder = computed(() => !props.options.some((option) => option.value === ''));
</script>

<template>
    <select
        :value="modelValue"
        :disabled="disabled"
        :class="cn(
            'flex h-9 w-full rounded-md border border-slate-200 bg-white px-3 py-1 text-sm text-slate-900 shadow-sm',
            'focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent',
            'disabled:cursor-not-allowed disabled:opacity-50',
            'dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100',
            $props.class
        )"
        @change="$emit('update:modelValue', $event.target.value)"
        @blur="$emit('blur', $event)"
    >
        <option v-if="shouldRenderPlaceholder" value="" disabled>{{ placeholder }}</option>
        <option v-for="opt in options" :key="opt.value" :value="opt.value">
            {{ opt.label }}
        </option>
    </select>
</template>
