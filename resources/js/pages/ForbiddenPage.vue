<script setup>
import { computed } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { defaultRouteForRole } from '@/router';

const router = useRouter();
const auth   = useAuthStore();

const homeRoute = computed(() => {
    return defaultRouteForRole(auth.user?.role);
});

function goBack() {
    if (window.history.length > 1) {
        router.back();
        return;
    }

    router.push(homeRoute.value);
}
</script>

<template>
    <div class="relative flex min-h-screen flex-col items-center justify-center overflow-hidden bg-gradient-to-br from-slate-950 via-slate-900 to-blue-950 p-6">

        <!-- Background decoration -->
        <div class="pointer-events-none absolute inset-0 overflow-hidden">
            <div class="absolute -left-32 -top-32 h-96 w-96 rounded-full bg-blue-600/10 blur-3xl" />
            <div class="absolute -bottom-32 -right-32 h-96 w-96 rounded-full bg-violet-600/10 blur-3xl" />
            <div class="absolute left-1/2 top-1/2 h-[600px] w-[600px] -translate-x-1/2 -translate-y-1/2 rounded-full bg-slate-800/30 blur-3xl" />
        </div>

        <!-- Card -->
        <div class="relative z-10 w-full max-w-md text-center">

            <!-- Icon -->
            <div class="mx-auto mb-6 flex h-24 w-24 items-center justify-center rounded-2xl border border-red-500/30 bg-red-500/10 shadow-lg shadow-red-500/10">
                <svg class="h-12 w-12 text-red-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                </svg>
            </div>

            <!-- Code -->
            <p class="mb-2 bg-gradient-to-r from-red-400 to-orange-400 bg-clip-text text-7xl font-black tracking-tight text-transparent">
                403
            </p>

            <!-- Title -->
            <h1 class="mb-3 text-2xl font-bold text-white">
                Akses Ditolak
            </h1>

            <!-- Description -->
            <p class="mb-8 text-sm leading-6 text-slate-400">
                Anda tidak memiliki izin untuk mengakses halaman ini.<br>
                Silakan kembali ke halaman yang sesuai dengan peran Anda.
            </p>

            <!-- Info box -->
            <div class="mb-8 rounded-xl border border-slate-700 bg-slate-800/50 p-4 text-left backdrop-blur-sm">
                <div class="flex items-start gap-3">
                    <svg class="mt-0.5 h-4 w-4 shrink-0 text-blue-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/><path d="M12 16v-4m0-4h.01"/>
                    </svg>
                    <div class="text-xs text-slate-400">
                        <p class="mb-1 font-medium text-slate-300">Kenapa ini terjadi?</p>
                        <p>Halaman ini membutuhkan role atau hak akses tertentu. Jika Anda merasa ini adalah kesalahan, hubungi administrator sistem.</p>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex flex-col gap-3 sm:flex-row sm:justify-center">
                <button
                    class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-700 bg-slate-800 px-5 py-2.5 text-sm font-medium text-slate-200 transition hover:bg-slate-700 hover:text-white"
                    @click="goBack"
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 12H5M12 5l-7 7 7 7"/>
                    </svg>
                    Kembali
                </button>
                <button
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-500"
                    @click="router.push(homeRoute)"
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 10.75L12 4l9 6.75V20a1 1 0 0 1-1 1h-5.5v-6.25h-5V21H4a1 1 0 0 1-1-1v-9.25Z"/>
                    </svg>
                    Ke Dashboard
                </button>
            </div>
        </div>

        <!-- Footer -->
        <p class="absolute bottom-6 text-[11px] text-slate-600">BASS KPI System</p>
    </div>
</template>
