<script setup>
import { useRouter } from 'vue-router';
import Button from '@/components/ui/Button.vue';

const router = useRouter();
</script>

<template>
    <div class="flex min-h-screen flex-col items-center justify-center gap-4 text-center p-4">
        <p class="text-5xl font-medium text-slate-200 dark:text-slate-700">403</p>
        <h1 class="text-base font-medium text-slate-800 dark:text-slate-200">Akses ditolak</h1>
        <p class="text-sm text-slate-400">Kamu tidak memiliki izin untuk mengakses halaman ini.</p>
        <Button variant="outline" size="sm" @click="router.back()">Kembali</Button>
    </div>
</template>
