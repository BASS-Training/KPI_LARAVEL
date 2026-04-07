<script setup>
import { ref, provide } from 'vue';
import { cn } from '@/lib/utils';

// State toast global
const toasts = ref([]);

let nextId = 0;

function toast({ message, variant = 'success', duration = 3000 }) {
    const id = ++nextId;
    toasts.value.push({ id, message, variant });

    setTimeout(() => {
        toasts.value = toasts.value.filter((t) => t.id !== id);
    }, duration);
}

// Expose ke child via provide
provide('toast', toast);

// Export agar bisa dipanggil dari luar
defineExpose({ toast });

const variantClasses = {
    success: 'bg-green-600 text-white',
    error: 'bg-red-600 text-white',
    warning: 'bg-amber-500 text-white',
    info: 'bg-blue-600 text-white',
};
</script>

<template>
    <!-- Teleport ke body agar selalu di atas konten -->
    <Teleport to="body">
        <div class="fixed bottom-4 right-4 z-[100] flex flex-col gap-2">
            <TransitionGroup
                tag="div"
                enter-active-class="transition duration-300"
                enter-from-class="opacity-0 translate-y-4"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition duration-200"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
                class="flex flex-col gap-2"
            >
                <div
                    v-for="t in toasts"
                    :key="t.id"
                    :class="cn(
                        'flex min-w-[280px] items-center gap-3 rounded-lg px-4 py-3 text-sm shadow-lg',
                        variantClasses[t.variant] || variantClasses.info
                    )"
                >
                    {{ t.message }}
                </div>
            </TransitionGroup>
        </div>
    </Teleport>
    <slot />
</template>
