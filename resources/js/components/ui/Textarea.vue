<script setup>
import { cn } from '@/lib/utils';

defineProps({
    modelValue: { type: String, default: '' },
    placeholder: String,
    rows: { type: Number, default: 3 },
    disabled: Boolean,
    class: { type: String, default: '' },
});

defineEmits(['update:modelValue']);
</script>

<template>
    <textarea
        :value="modelValue"
        :placeholder="placeholder"
        :rows="rows"
        :disabled="disabled"
        :class="cn(
            'flex w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm transition-colors',
            'placeholder:text-slate-400 resize-none',
            'focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent',
            'disabled:cursor-not-allowed disabled:opacity-50',
            'dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100',
            $props.class
        )"
        @input="$emit('update:modelValue', $event.target.value)"
    />
</template>
