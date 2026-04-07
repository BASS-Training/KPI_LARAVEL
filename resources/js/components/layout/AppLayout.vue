<script setup>
import { ref } from 'vue';
import AppSidebar from './AppSidebar.vue';
import AppTopbar from './AppTopbar.vue';

const mobileMenuOpen = ref(false);

function closeMobile() {
    mobileMenuOpen.value = false;
}
</script>

<template>
    <div class="flex h-screen bg-slate-50">
        <!-- Sidebar desktop (240px, fixed) -->
        <aside class="app-sidebar-panel hidden lg:flex">
            <AppSidebar />
        </aside>

        <!-- Mobile sidebar drawer -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="mobileMenuOpen" class="fixed inset-0 z-40 lg:hidden">
                <!-- Overlay -->
                <div class="sidebar-overlay" @click="closeMobile" />

                <!-- Panel -->
                <div class="absolute inset-y-0 left-0 z-50 w-[240px] flex flex-col">
                    <AppSidebar mobile @close="closeMobile" />
                </div>
            </div>
        </Transition>

        <!-- Area utama (dengan margin kiri di desktop) -->
        <div class="flex flex-1 flex-col overflow-hidden lg:ml-[240px]">
            <AppTopbar @open-sidebar="mobileMenuOpen = true">
                <template #actions>
                    <slot name="topbar-actions" />
                </template>
            </AppTopbar>

            <!-- Konten halaman -->
            <main class="flex-1 overflow-y-auto page-shell">
                <slot />
            </main>
        </div>
    </div>
</template>
