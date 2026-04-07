<script setup>
import { watch } from 'vue';
import { cn } from '@/lib/utils';

const props = defineProps({
    open: { type: Boolean, default: false },
    title: String,
    description: String,
    class: { type: String, default: '' },
});

const emit = defineEmits(['update:open']);

// Blokir scroll body saat dialog terbuka
watch(
    () => props.open,
    (val) => {
        document.body.style.overflow = val ? 'hidden' : '';
    },
);

function close() {
    emit('update:open', false);
}
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center">
                <!-- Overlay -->
                <div
                    class="absolute inset-0 bg-black/40 backdrop-blur-sm"
                    @click="close"
                />

                <!-- Panel -->
                <div
                    :class="cn(
                        'relative z-10 w-full max-w-md rounded-xl border border-slate-200 bg-white p-6 shadow-xl',
                        'dark:border-slate-700 dark:bg-slate-900',
                        $props.class
                    )"
                    role="dialog"
                    aria-modal="true"
                >
                    <div v-if="title || description" class="mb-4">
                        <h2 v-if="title" class="text-sm font-medium text-slate-900 dark:text-slate-100">
                            {{ title }}
                        </h2>
                        <p v-if="description" class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                            {{ description }}
                        </p>
                    </div>

                    <slot />
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
