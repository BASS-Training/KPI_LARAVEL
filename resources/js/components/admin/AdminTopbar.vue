<template>
  <header class="topbar">
    <div class="topbar-left">
      <nav class="breadcrumb" aria-label="breadcrumb">
        <RouterLink to="/admin/tenants" class="bc-link">Super Admin</RouterLink>
        <span class="bc-sep">/</span>
        <span class="bc-current">{{ currentPageLabel }}</span>
      </nav>
    </div>
    <div class="topbar-right">
      <TenantSwitcher />
      <span class="user-chip">
        <span class="user-avatar">{{ initials(auth.user?.nama) }}</span>
        <span class="user-name">{{ auth.user?.nama ?? 'Super Admin' }}</span>
      </span>
    </div>
  </header>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import TenantSwitcher from '@/components/admin/TenantSwitcher.vue'

const route = useRoute()
const auth  = useAuthStore()

function initials(name = '') {
  return name.split(' ').slice(0, 2).map(w => w[0]).join('').toUpperCase() || 'SA'
}

const pageLabels = {
  '/admin':             'Dashboard',
  '/admin/dashboard':   'Dashboard',
  '/admin/tenants':         'Tenants',
  '/admin/users':           'Users',
  '/admin/kpi/templates':   'KPI Templates',
  '/admin/kpi/assignments': 'KPI Assignments',
  '/admin/reports':         'Reports',
  '/admin/audit-logs':      'Audit Logs',
}

const currentPageLabel = computed(() => pageLabels[route.path] ?? 'Panel')
</script>

<style scoped>
.topbar {
  height: 56px;
  background: #fff;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 24px;
  flex-shrink: 0;
}
.breadcrumb { display: flex; align-items: center; gap: 6px; }
.bc-link { color: #2563eb; text-decoration: none; font-size: 13px; font-weight: 500; }
.bc-link:hover { text-decoration: underline; }
.bc-sep { color: #cbd5e1; font-size: 13px; }
.bc-current { color: #1e293b; font-size: 13px; font-weight: 600; }
.topbar-right { display: flex; align-items: center; gap: 12px; }
.user-chip { display: flex; align-items: center; gap: 8px; padding: 5px 12px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 99px; }
.user-avatar { width: 26px; height: 26px; border-radius: 50%; background: #2563eb; color: #fff; font-size: 11px; font-weight: 700; display: flex; align-items: center; justify-content: center; }
.user-name { font-size: 13px; font-weight: 500; color: #334155; max-width: 160px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
</style>
