<template>
  <div v-if="auth.hasMultiTenant" class="tenant-switcher" ref="root">
    <button class="switcher-btn" @click="open = !open">
      <span class="tenant-icon">🏢</span>
      <span class="tenant-name">{{ auth.activeTenant?.tenant_name ?? 'Select Tenant' }}</span>
      <span class="chevron" :class="{ rotated: open }">▾</span>
    </button>

    <Transition name="dropdown">
      <div v-if="open" class="dropdown">
        <div class="dropdown-header">Switch Tenant</div>
        <button
          v-for="t in auth.myTenants"
          :key="t.id"
          class="dropdown-item"
          :class="{ active: String(t.id) === String(auth.activeTenantId) }"
          @click="switchTenant(t)"
        >
          <span class="item-name">{{ t.tenant_name }}</span>
          <span class="item-role">{{ formatRole(t.role) }}</span>
          <span v-if="t.is_primary" class="item-badge primary">Primary</span>
          <span v-else class="item-badge secondary">Rangkap</span>
        </button>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { defaultRouteForRole } from '@/router'

const auth   = useAuthStore()
const router = useRouter()
const open   = ref(false)
const root   = ref(null)

function switchTenant(t) {
  auth.setActiveTenant(t.id)
  open.value = false
  // Redirect to the appropriate dashboard for the role in this tenant
  const role = t.role || auth.user?.role
  const dest = defaultRouteForRole(role)
  // Use replace + reload to clear all cached API data
  router.replace(dest).then(() => window.location.reload())
}

function formatRole(name = '') {
  const m = {
    tenant_admin: 'Tenant Admin', hr_manager: 'HR Manager',
    direktur: 'Direktur', pegawai: 'Pegawai',
    dept_head: 'Dept Head', supervisor: 'Supervisor', employee: 'Employee',
  }
  return m[name] ?? name
}

function onClickOutside(e) {
  if (root.value && !root.value.contains(e.target)) open.value = false
}

onMounted(() => document.addEventListener('mousedown', onClickOutside))
onUnmounted(() => document.removeEventListener('mousedown', onClickOutside))
</script>

<style scoped>
.tenant-switcher { position: relative; }

.switcher-btn {
  display: flex; align-items: center; gap: 6px;
  padding: 5px 12px; background: #f0f9ff;
  border: 1px solid #bae6fd; border-radius: 99px;
  font-size: 13px; font-weight: 500; color: #0369a1;
  cursor: pointer; white-space: nowrap;
}
.switcher-btn:hover { background: #e0f2fe; }
.tenant-icon { font-size: 14px; }
.tenant-name { max-width: 140px; overflow: hidden; text-overflow: ellipsis; }
.chevron { font-size: 11px; transition: transform .2s; }
.chevron.rotated { transform: rotate(180deg); }

.dropdown {
  position: absolute; top: calc(100% + 6px); right: 0;
  background: #fff; border: 1px solid #e2e8f0; border-radius: 10px;
  box-shadow: 0 8px 24px rgba(0,0,0,.12); min-width: 220px; z-index: 9999;
  overflow: hidden;
}
.dropdown-header {
  padding: 10px 14px; font-size: 11px; font-weight: 700;
  text-transform: uppercase; letter-spacing: .5px; color: #94a3b8;
  border-bottom: 1px solid #f1f5f9;
}
.dropdown-item {
  display: flex; align-items: center; gap: 6px;
  width: 100%; padding: 10px 14px; background: none;
  border: none; text-align: left; cursor: pointer; font-size: 13px;
}
.dropdown-item:hover { background: #f8fafc; }
.dropdown-item.active { background: #eff6ff; }
.item-name { flex: 1; font-weight: 500; color: #1e293b; }
.item-role { font-size: 11px; color: #94a3b8; }
.item-badge {
  font-size: 10px; font-weight: 700; padding: 1px 6px; border-radius: 99px;
  text-transform: uppercase;
}
.primary   { background: #dcfce7; color: #15803d; }
.secondary { background: #fef9c3; color: #a16207; }

.dropdown-enter-active, .dropdown-leave-active { transition: opacity .15s, transform .15s; }
.dropdown-enter-from, .dropdown-leave-to { opacity: 0; transform: translateY(-4px); }
</style>
