import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import api from '@/services/api';
import router from '@/router';
import { readStoredUser } from '@/lib/authStorage';
import { useNotification } from '@/composables/useNotification';

export const useAuthStore = defineStore('auth', () => {
    const user = ref(readStoredUser());
    const token = ref(localStorage.getItem('token') || null);
    const isLoading = ref(false);

    // ─── Getters ──────────────────────────────────────────────────────────
    const isLoggedIn = computed(() => !!token.value);
    const isPegawai = computed(() => user.value?.role === 'pegawai');
    const isHR = computed(() => user.value?.role === 'hr_manager');
    const isDirektur = computed(() => user.value?.role === 'direktur');

    // ─── Actions ──────────────────────────────────────────────────────────

    async function login(nip, nama) {
        isLoading.value = true;
        try {
            // API response: { success, data: { token, user }, message }
            const { data: resp } = await api.post('/auth/login', { nip, nama });
            const { token: rawToken, user: rawUser } = resp.data;

            token.value = rawToken;
            user.value = rawUser;

            localStorage.setItem('token', rawToken);
            localStorage.setItem('user', JSON.stringify(rawUser));

            // Update Echo auth header so private channels work after login
            if (window.Echo) {
                window.Echo.options.auth.headers.Authorization = `Bearer ${rawToken}`;
            }

            useNotification().init(rawUser.id);

            // Arahkan berdasarkan role
            const role = rawUser.role;
            if (role === 'hr_manager') router.push('/hr/dashboard');
            else if (role === 'direktur') router.push('/direktur/dashboard');
            else router.push('/dashboard');

            return resp;
        } finally {
            isLoading.value = false;
        }
    }

    async function logout() {
        isLoading.value = true;
        try {
            await api.post('/auth/logout');
        } catch {
            // Tetap lanjutkan logout meskipun API gagal
        } finally {
            clearState();
            isLoading.value = false;
            router.push('/login');
        }
    }

    async function fetchMe() {
        try {
            // API response: { success, data: { ...user }, message }
            const { data: resp } = await api.get('/auth/me');
            user.value = resp.data;
            localStorage.setItem('user', JSON.stringify(resp.data));
        } catch {
            clearState();
        }
    }

    function clearState() {
        useNotification().cleanup();
        user.value = null;
        token.value = null;
        localStorage.removeItem('token');
        localStorage.removeItem('user');
    }

    return { user, token, isLoading, isLoggedIn, isPegawai, isHR, isDirektur, login, logout, fetchMe };
});
