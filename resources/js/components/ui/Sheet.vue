<script setup>
import { watch } from 'vue';
import { cn } from '@/lib/utils';

const props = defineProps({
    open: { type: Boolean, default: false },
    side: { type: String, default: 'left' }, // left | right
    class: { type: String, default: '' },
});

const emit = defineEmits(['update:open']);

watch(
    () => props.open,
    (val) => {
        document.body.style.overflow = val ? 'hidden' : '';
    },
);

function close() {
    emit('update:open', false);
}

const sideClasses = {
    left: 'left-0 h-full w-[280px]',
    right: 'right-0 h-full w-[280px]',
};

const enterFrom = {
    left: '-translate-x-full',
    right: 'translate-x-full',
};
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-300"
            leave-active-class="transition duration-200"
        >
            <div v-if="open" class="fixed inset-0 z-50 flex">
                <!-- Overlay -->
                <div class="absolute inset-0 bg-black/40" @click="close" />

                <!-- Panel -->
                <Transition
                    :enter-from-class="enterFrom[side]"
                    enter-active-class="transition duration-300 ease-out"
                    :leave-to-class="enterFrom[side]"
                    leave-active-class="transition duration-200 ease-in"
                >
                    <div
                        v-if="open"
                        :class="cn(
                            'absolute z-10 flex flex-col border-slate-200 bg-white shadow-xl',
                            'dark:border-slate-700 dark:bg-slate-900',
                            sideClasses[side],
                            $props.class
                        )"
                    >
                        <slot />
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
