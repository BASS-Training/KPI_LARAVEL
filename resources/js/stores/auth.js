import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import api from '@/services/api';
import router, { defaultRouteForRole } from '@/router';
import { readStoredUser } from '@/lib/authStorage';

export const useAuthStore = defineStore('auth', () => {
    const user            = ref(readStoredUser());
    const token           = ref(localStorage.getItem('token') || null);
    const isLoading       = ref(false);
    const myTenants       = ref(JSON.parse(localStorage.getItem('my_tenants') || '[]'));
    const activeTenantId  = ref(localStorage.getItem('active_tenant_id') || null);
    const _activeRole     = ref(localStorage.getItem('active_role') || null);

    // ─── Getters ──────────────────────────────────────────────────────────
    const isLoggedIn    = computed(() => !!token.value);
    const isSuperAdmin  = computed(() => user.value?.role === 'super_admin');
    const hasMultiTenant = computed(() => myTenants.value.length > 1);
    const activeTenant  = computed(() =>
        myTenants.value.find(t => String(t.id) === String(activeTenantId.value))
        ?? myTenants.value[0]
        ?? null
    );
    // Role yang berlaku untuk tenant aktif saat ini
    const activeRole = computed(() => {
        if (isSuperAdmin.value) return 'super_admin';
        if (_activeRole.value) return _activeRole.value;
        return user.value?.role ?? null;
    });
    const isPegawai  = computed(() => activeRole.value === 'pegawai');
    const isHR       = computed(() => activeRole.value === 'hr_manager');
    const isDirektur = computed(() => activeRole.value === 'direktur');

    // ─── Actions ──────────────────────────────────────────────────────────

    async function login(nip, nama) {
        isLoading.value = true;
        try {
            const { data: resp } = await api.post('/auth/login', { nip, nama });
            const { token: rawToken, user: rawUser } = resp.data;

            token.value = rawToken;
            user.value  = rawUser;

            localStorage.setItem('token', rawToken);
            localStorage.setItem('user', JSON.stringify(rawUser));

            // Fetch tenant dulu agar activeTenantId tersedia sebelum dashboard load
            await fetchMyTenants().catch(() => {});

            await router.push(defaultRouteForRole(rawUser.role));

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
            // continue logout even if API fails
        } finally {
            clearState();
            isLoading.value = false;
            router.push('/login');
        }
    }

    async function fetchMe() {
        try {
            const { data: resp } = await api.get('/auth/me');
            user.value = resp.data;
            localStorage.setItem('user', JSON.stringify(resp.data));
        } catch {
            clearState();
        }
    }

    async function fetchMyTenants() {
        if (isSuperAdmin.value) return;
        try {
            const { data } = await api.get('/v2/my/tenants');
            myTenants.value = data.data ?? [];
            localStorage.setItem('my_tenants', JSON.stringify(myTenants.value));

            // Set default active tenant if none stored
            if (!activeTenantId.value && myTenants.value.length > 0) {
                const primary = myTenants.value.find(t => t.is_primary) ?? myTenants.value[0];
                setActiveTenant(primary.id);
            }
        } catch {
            // ignore
        }
    }

    function setActiveTenant(tenantId) {
        activeTenantId.value = String(tenantId);
        localStorage.setItem('active_tenant_id', String(tenantId));

        // Persist role for this tenant so router guard can read it
        const t = myTenants.value.find(t => String(t.id) === String(tenantId));
        const role = t?.role || user.value?.role;
        if (role) {
            _activeRole.value = role;
            localStorage.setItem('active_role', role);
        }
    }

    function clearState() {
        user.value           = null;
        token.value          = null;
        myTenants.value      = [];
        activeTenantId.value = null;
        _activeRole.value    = null;
        localStorage.removeItem('token');
        localStorage.removeItem('user');
        localStorage.removeItem('my_tenants');
        localStorage.removeItem('active_tenant_id');
        localStorage.removeItem('active_role');
    }

    return {
        user, token, isLoading, myTenants, activeTenantId, activeTenant, activeRole,
        isLoggedIn, isPegawai, isHR, isDirektur, isSuperAdmin, hasMultiTenant,
        login, logout, fetchMe, fetchMyTenants, setActiveTenant,
    };
});
