<script setup>
import { onMounted } from 'vue';
import Toaster from '@/components/ui/Toaster.vue';
import { useAuthStore } from '@/stores/auth';

const auth = useAuthStore();

onMounted(() => {
    // Jika sudah login (ada token tersimpan), refresh tenant list
    if (auth.isLoggedIn && !auth.isSuperAdmin) {
        auth.fetchMyTenants().catch(() => {});
    }
});
</script>

<template>
    <Toaster>
        <RouterView />
    </Toaster>
</template>
