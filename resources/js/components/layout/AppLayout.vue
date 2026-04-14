<script setup>
import { provide, ref } from 'vue';
import AppSidebar from './AppSidebar.vue';
import AppTopbar from './AppTopbar.vue';

const mobileMenuOpen = ref(false);
const sidebarCollapsed = ref(false);

// Provide so nested components can read sidebar state if needed
provide('sidebarCollapsed', sidebarCollapsed);

function toggleSidebar() {
    sidebarCollapsed.value = !sidebarCollapsed.value;
}
</script>

<template>
    <div class="flex min-h-screen overflow-hidden bg-muted/40 dark:bg-[#0a0f1e]">

        <!-- ── Desktop sidebar ──────────────────────────────────────────── -->
        <aside
            :class="[
                'app-sidebar-panel hidden lg:flex flex-col transition-[width] duration-300 ease-in-out',
                sidebarCollapsed ? 'w-[64px]' : 'w-[240px]',
            ]"
        >
            <AppSidebar :collapsed="sidebarCollapsed" @toggle="toggleSidebar" />
        </aside>

        <!-- ── Mobile sidebar drawer ────────────────────────────────────── -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="mobileMenuOpen" class="fixed inset-0 z-40 lg:hidden">
                <div class="sidebar-overlay" @click="mobileMenuOpen = false" />
                <div class="absolute inset-y-0 left-0 z-50 flex w-[240px] flex-col">
                    <AppSidebar :collapsed="false" mobile @close="mobileMenuOpen = false" />
                </div>
            </div>
        </Transition>

        <!-- ── Main content ─────────────────────────────────────────────── -->
        <div
            :class="[
                'flex flex-1 flex-col overflow-hidden transition-[margin] duration-300 ease-in-out',
                sidebarCollapsed ? 'lg:ml-[64px]' : 'lg:ml-[240px]',
            ]"
        >
            <AppTopbar @open-sidebar="mobileMenuOpen = true" @toggle-sidebar="toggleSidebar">
                <template #actions>
                    <slot name="topbar-actions" />
                </template>
            </AppTopbar>

            <main class="flex-1 overflow-y-auto">
                <div class="page-shell">
                    <slot />
                </div>
            </main>
        </div>
    </div>
</template>
