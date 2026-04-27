<template>
  <div class="page">
    <AdminPageHeader
      :title="tenant?.tenant_name ?? 'Tenant Detail'"
      :description="tenant?.domain ?? tenant?.tenant_code"
      :show-back="true"
      back-to="/admin/tenants"
    >
      <template v-if="tenant" #actions>
        <StatusBadge :status="tenant.status" dot />
        <button class="btn-edit" @click="openEdit">✏️ Edit</button>
        <button
          v-if="tenant.status !== 'active'"
          class="btn-success"
          :disabled="toggling"
          @click="toggleStatus('activate')"
        >▶ Activate</button>
        <button
          v-else
          class="btn-warning"
          :disabled="toggling"
          @click="toggleStatus('deactivate')"
        >⏸ Deactivate</button>
      </template>
    </AdminPageHeader>

    <div v-if="loading" class="loading-state">Loading tenant…</div>
    <div v-else-if="!tenant" class="loading-state">Tenant not found.</div>
    <template v-else>
      <!-- Info cards -->
      <div class="info-grid">
        <div class="info-card">
          <p class="info-label">Tenant Code</p>
          <p class="info-value mono">{{ tenant.tenant_code }}</p>
        </div>
        <div class="info-card">
          <p class="info-label">Contact Email</p>
          <p class="info-value">{{ tenant.contact_email ?? '—' }}</p>
        </div>
        <div class="info-card">
          <p class="info-label">Phone</p>
          <p class="info-value">{{ tenant.contact_phone ?? '—' }}</p>
        </div>
        <div class="info-card">
          <p class="info-label">Domain</p>
          <p class="info-value mono">{{ tenant.domain ?? '—' }}</p>
        </div>
        <div class="info-card">
          <p class="info-label">Users</p>
          <p class="info-value">{{ tenant.users_count ?? '—' }}</p>
        </div>
        <div class="info-card">
          <p class="info-label">KPI Templates</p>
          <p class="info-value">{{ tenant.kpi_templates_count ?? '—' }}</p>
        </div>
        <div class="info-card">
          <p class="info-label">Created</p>
          <p class="info-value">{{ fmtDate(tenant.created_at) }}</p>
        </div>
        <div class="info-card">
          <p class="info-label">Address</p>
          <p class="info-value">{{ tenant.address ?? '—' }}</p>
        </div>
      </div>

      <!-- Tabs -->
      <div class="tab-bar">
        <button v-for="t in tabs" :key="t" class="tab-btn" :class="{ active: activeTab === t }" @click="activeTab = t">
          {{ t }}
        </button>
      </div>

      <!-- Users tab -->
      <div v-if="activeTab === 'Users'" class="card">
        <div class="card-toolbar">
          <span class="card-title">
            Users in this Tenant
            <span class="count-chip">{{ tenantUsers.length }}</span>
          </span>
          <button class="btn-assign" @click="openAssignDialog">+ Assign Existing User</button>
        </div>

        <div v-if="loadingUsers" class="card-loader">Loading users…</div>
        <div v-else-if="!tenantUsers.length" class="card-empty">No users in this tenant yet.</div>
        <table v-else class="mini-table">
          <thead>
            <tr><th>NIP</th><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Type</th><th></th></tr>
          </thead>
          <tbody>
            <tr v-for="u in tenantUsers" :key="u.id">
              <td class="mono">{{ u.nip }}</td>
              <td>{{ u.nama }}</td>
              <td class="muted">{{ u.email ?? '—' }}</td>
              <td><span class="role-chip">{{ formatRole(u.roles?.[0]?.name ?? u.role) }}</span></td>
              <td><StatusBadge :status="u.status_karyawan === 'Tetap' ? 'active' : 'inactive'" :label="u.status_karyawan" dot /></td>
              <td>
                <span v-if="Number(u.tenant_id) === Number(tenant.id)" class="type-badge primary">Primary</span>
                <span v-else class="type-badge rangkap">Rangkap</span>
              </td>
              <td>
                <!-- Only secondary users can be removed -->
                <button
                  v-if="Number(u.tenant_id) !== Number(tenant.id)"
                  class="btn-remove"
                  title="Remove from this tenant"
                  @click="confirmRemove(u)"
                >✕</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- KPI Templates tab -->
      <div v-else-if="activeTab === 'KPI Templates'" class="card">
        <div v-if="loadingTemplates" class="card-loader">Loading templates…</div>
        <div v-else-if="!tenantTemplates.length" class="card-empty">No KPI templates in this tenant.</div>
        <table v-else class="mini-table">
          <thead>
            <tr><th>Name</th><th>Period</th><th>Indicators</th><th>Status</th><th>Created</th></tr>
          </thead>
          <tbody>
            <tr v-for="tpl in tenantTemplates" :key="tpl.id">
              <td>{{ tpl.name }}</td>
              <td class="muted">{{ tpl.period_label ?? '—' }}</td>
              <td>{{ tpl.indicators_count ?? tpl.indicators?.length ?? '—' }}</td>
              <td><StatusBadge :status="tpl.is_active ? 'active' : 'inactive'" dot /></td>
              <td class="muted">{{ fmtDate(tpl.created_at) }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Audit Logs tab -->
      <div v-else-if="activeTab === 'Audit Logs'" class="card">
        <div v-if="loadingActivity" class="card-loader">Loading activity…</div>
        <div v-else-if="!tenantLogs.length" class="card-empty">No activity recorded.</div>
        <div v-else class="activity-list">
          <div v-for="log in tenantLogs" :key="log.id" class="activity-item">
            <span class="action-dot" :class="`dot-${log.action_name}`" />
            <div class="activity-body">
              <span class="activity-who">{{ log.user?.nama ?? 'System' }}</span>
              <span class="activity-sep"> · </span>
              <span class="activity-action">{{ log.action_name }}</span>
              <span class="activity-sep"> on </span>
              <span class="activity-entity">{{ log.entity_name }}</span>
            </div>
            <span class="activity-time">{{ fmtDate(log.created_at) }}</span>
          </div>
        </div>
      </div>
    </template>

    <!-- ── Edit Tenant Dialog ── -->
    <Teleport to="body">
      <Transition name="fade">
        <div v-if="showEdit" class="overlay" @click.self="showEdit = false">
          <div class="form-dialog edit-dialog">
            <h2 class="form-title">Edit Tenant</h2>
            <div class="edit-grid">
              <label class="form-label span-2">
                Nama Tenant / Perusahaan *
                <input v-model="editForm.tenant_name" class="form-input" placeholder="PT. Nama Perusahaan" />
                <span class="form-hint">Nama ini tampil di sidebar &amp; topbar semua user tenant ini.</span>
              </label>
              <label class="form-label">
                Domain
                <input v-model="editForm.domain" class="form-input" placeholder="app.company.com" />
              </label>
              <label class="form-label">
                Contact Email
                <input v-model="editForm.contact_email" type="email" class="form-input" />
              </label>
              <label class="form-label">
                Contact Phone
                <input v-model="editForm.contact_phone" class="form-input" />
              </label>
              <label class="form-label">
                Status
                <select v-model="editForm.status" class="form-input">
                  <option value="active">Active</option>
                  <option value="inactive">Inactive</option>
                  <option value="suspended">Suspended</option>
                </select>
              </label>
              <label class="form-label span-2">
                Address
                <textarea v-model="editForm.address" class="form-input" rows="2" />
              </label>
            </div>
            <p v-if="editError" class="form-error">{{ editError }}</p>
            <div class="form-footer">
              <button class="btn-cancel" @click="showEdit = false">Cancel</button>
              <button class="btn-primary" :disabled="editSaving" @click="saveEdit">
                <span v-if="editSaving" class="spinner-sm" />
                Save Changes
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- ── Assign User Dialog (multi-select) ── -->
    <Teleport to="body">
      <Transition name="fade">
        <div v-if="showAssign" class="overlay" @click.self="closeAssign">
          <div class="form-dialog">
            <div class="dialog-header">
              <div>
                <h2 class="form-title">Assign Users ke {{ tenant?.tenant_name }}</h2>
                <p class="assign-subtitle">
                  Pilih satu atau lebih pegawai untuk ditambahkan sebagai <strong>Rangkap</strong> di tenant ini.
                  Tenant utama mereka tidak berubah.
                </p>
              </div>
            </div>

            <!-- Search -->
            <div class="search-row">
              <input
                v-model="searchUser"
                class="form-input"
                placeholder="Cari NIP / nama..."
                @input="onSearchUser"
              />
              <span v-if="selectedUsers.length" class="selected-count">
                {{ selectedUsers.length }} dipilih
              </span>
            </div>

            <!-- Selected chips -->
            <div v-if="selectedUsers.length" class="selected-chips">
              <span
                v-for="u in selectedUsers"
                :key="u.id"
                class="chip"
              >
                {{ u.nama }}
                <button class="chip-remove" @click="toggleUser(u)">×</button>
              </span>
            </div>

            <!-- Search results -->
            <div class="search-results-wrap">
              <div v-if="searchLoading" class="search-loading">Mencari…</div>
              <div v-else-if="searchResults.length" class="search-results">
                <label
                  v-for="u in searchResults"
                  :key="u.id"
                  class="result-item"
                  :class="{
                    selected:  isSelected(u),
                    already:   isAlreadyInTenant(u),
                  }"
                >
                  <input
                    type="checkbox"
                    class="result-checkbox"
                    :checked="isSelected(u)"
                    :disabled="isAlreadyInTenant(u)"
                    @change="toggleUser(u)"
                  />
                  <span class="result-avatar">{{ initials(u.nama) }}</span>
                  <div class="result-info">
                    <div class="result-name">
                      {{ u.nama }}
                      <span v-if="isAlreadyInTenant(u)" class="already-badge">Sudah ada</span>
                    </div>
                    <div class="result-meta">{{ u.nip }} · {{ formatRole(u.roles?.[0]?.name ?? u.role) }}</div>
                  </div>
                </label>
              </div>
              <p v-else-if="searchUser.length >= 2 && !searchLoading" class="search-empty">
                Tidak ada user ditemukan.
              </p>
              <p v-else-if="!searchUser" class="search-hint">
                Ketik minimal 2 karakter untuk mencari.
              </p>
            </div>

            <!-- Role for all selected -->
            <div v-if="selectedUsers.length" class="assign-role-row">
              <label class="form-label">
                Role di Tenant Ini <span class="label-hint">(opsional — default: role utama masing-masing)</span>
                <select v-model="assignRole" class="form-input">
                  <option value="">— Sama dengan role utama —</option>
                  <option v-for="r in userStore.roles" :key="r.name" :value="r.name">{{ formatRole(r.name) }}</option>
                </select>
              </label>
            </div>

            <!-- Progress while assigning -->
            <div v-if="assigning" class="assign-progress">
              <div class="progress-bar">
                <div class="progress-fill" :style="{ width: assignProgress + '%' }" />
              </div>
              <p class="progress-label">Menyimpan {{ assignDone }}/{{ selectedUsers.length }}…</p>
            </div>

            <p v-if="assignError" class="form-error">{{ assignError }}</p>

            <div class="form-footer">
              <button class="btn-cancel" :disabled="assigning" @click="closeAssign">Cancel</button>
              <button
                class="btn-primary"
                :disabled="!selectedUsers.length || assigning"
                @click="doAssign"
              >
                <span v-if="assigning" class="spinner-sm" />
                Assign {{ selectedUsers.length ? `(${selectedUsers.length})` : '' }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- Remove Confirm -->
    <ConfirmDialog
      v-model="showRemove"
      title="Remove from Tenant"
      :message="`Hapus ${removeTarget?.nama} dari tenant ini? Tenant utama mereka tidak terpengaruh.`"
      variant="danger"
      confirm-label="Remove"
      :loading="removing"
      @confirm="doRemove"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute }               from 'vue-router'
import AdminPageHeader            from '@/components/admin/AdminPageHeader.vue'
import StatusBadge                from '@/components/admin/StatusBadge.vue'
import ConfirmDialog              from '@/components/admin/ConfirmDialog.vue'
import { useTenantStore }         from '@/stores/tenant'
import { useUserManagementStore } from '@/stores/userManagement'
import api                        from '@/services/api'

const route       = useRoute()
const tenantStore = useTenantStore()
const userStore   = useUserManagementStore()

const tenant           = ref(null)
const loading          = ref(true)
const toggling         = ref(false)
const activeTab        = ref('Users')
const tabs             = ['Users', 'KPI Templates', 'Audit Logs']
const tenantUsers      = ref([])
const tenantTemplates  = ref([])
const tenantLogs       = ref([])
const loadingUsers     = ref(false)
const loadingTemplates = ref(false)
const loadingActivity  = ref(false)

// ── Edit tenant ─────────────────────────────────
const showEdit   = ref(false)
const editForm   = ref({})
const editSaving = ref(false)
const editError  = ref('')

function openEdit() {
  editForm.value = {
    tenant_name:   tenant.value.tenant_name,
    domain:        tenant.value.domain ?? '',
    contact_email: tenant.value.contact_email ?? '',
    contact_phone: tenant.value.contact_phone ?? '',
    address:       tenant.value.address ?? '',
    status:        tenant.value.status,
  }
  editError.value = ''
  showEdit.value  = true
}

async function saveEdit() {
  editSaving.value = true
  editError.value  = ''
  try {
    await tenantStore.update(tenant.value.id, editForm.value)
    showEdit.value = false
    await loadTenant()
  } catch (e) {
    editError.value = e.response?.data?.message || e.userMessage || 'Failed to save.'
  } finally {
    editSaving.value = false
  }
}

// ── Assign dialog ───────────────────────────────
const showAssign    = ref(false)
const searchUser    = ref('')
const searchResults = ref([])
const searchLoading = ref(false)
const selectedUsers = ref([])   // array of user objects
const assignRole    = ref('')
const assigning     = ref(false)
const assignDone    = ref(0)
const assignProgress = ref(0)
const assignError   = ref('')
let searchTimer     = null

// ── Remove ──────────────────────────────────────
const showRemove   = ref(false)
const removeTarget = ref(null)
const removing     = ref(false)

// ────────────────────────────────────────────────

async function loadTenant() {
  loading.value = true
  try {
    const { data } = await api.get(`/v2/tenants/${route.params.id}`)
    tenant.value = data.data
  } finally {
    loading.value = false
  }
}

async function loadUsers() {
  loadingUsers.value = true
  try {
    // Use dedicated endpoint that returns ONLY users of this tenant (primary + pivot-assigned)
    const { data } = await api.get(`/v2/tenants/${route.params.id}/users`)
    tenantUsers.value = data.data ?? []
  } catch {
    tenantUsers.value = []
  } finally {
    loadingUsers.value = false
  }
}

async function loadTemplates() {
  loadingTemplates.value = true
  try {
    const { data } = await api.get('/v2/kpi/templates', { params: { page: 1, per_page: 50 } })
    tenantTemplates.value = data.data ?? []
  } finally {
    loadingTemplates.value = false
  }
}

async function loadActivity() {
  loadingActivity.value = true
  try {
    const { data } = await api.get('/v2/audit-logs', { params: { page: 1 } })
    tenantLogs.value = (data.data ?? []).slice(0, 20)
  } finally {
    loadingActivity.value = false
  }
}

async function toggleStatus(action) {
  toggling.value = true
  try {
    await tenantStore.setStatus(tenant.value.id, action)
    await loadTenant()
  } finally {
    toggling.value = false
  }
}

// ── Assign dialog helpers ────────────────────────

function openAssignDialog() {
  selectedUsers.value  = []
  searchUser.value     = ''
  searchResults.value  = []
  assignRole.value     = ''
  assignError.value    = ''
  showAssign.value     = true
}

function closeAssign() {
  if (assigning.value) return
  showAssign.value = false
}

function isSelected(u) {
  return selectedUsers.value.some(s => s.id === u.id)
}

function isAlreadyInTenant(u) {
  return tenantUsers.value.some(t => t.id === u.id)
}

function toggleUser(u) {
  if (isAlreadyInTenant(u)) return
  const idx = selectedUsers.value.findIndex(s => s.id === u.id)
  if (idx === -1) {
    selectedUsers.value.push(u)
  } else {
    selectedUsers.value.splice(idx, 1)
  }
}

function onSearchUser() {
  clearTimeout(searchTimer)
  if (searchUser.value.length < 2) {
    searchResults.value = []
    return
  }
  searchTimer = setTimeout(async () => {
    searchLoading.value = true
    try {
      const { data } = await api.get('/v2/users', {
        params: { search: searchUser.value, per_page: 20 },
      })
      searchResults.value = data.data ?? []
    } finally {
      searchLoading.value = false
    }
  }, 300)
}

async function doAssign() {
  if (!selectedUsers.value.length) return
  assigning.value  = true
  assignDone.value = 0
  assignProgress.value = 0
  assignError.value = ''

  const errors = []
  for (const u of selectedUsers.value) {
    try {
      await userStore.assignToTenant(u.id, {
        tenant_id:  tenant.value.id,
        role:       assignRole.value || undefined,
        is_primary: false,
      })
      assignDone.value++
      assignProgress.value = Math.round((assignDone.value / selectedUsers.value.length) * 100)
    } catch (e) {
      errors.push(`${u.nama}: ${e.response?.data?.message || 'Gagal'}`)
    }
  }

  if (errors.length) {
    assignError.value = errors.join(' | ')
  } else {
    showAssign.value = false
  }

  assigning.value = false
  await loadUsers()
}

// ── Remove ──────────────────────────────────────

function confirmRemove(u) {
  removeTarget.value = u
  showRemove.value   = true
}

async function doRemove() {
  removing.value = true
  try {
    await userStore.removeFromTenant(removeTarget.value.id, tenant.value.id)
    showRemove.value = false
    await loadUsers()
  } finally {
    removing.value = false
  }
}

// ── Utils ────────────────────────────────────────

function fmtDate(dt) {
  if (!dt) return '—'
  return new Date(dt).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
}

function formatRole(name = '') {
  const m = {
    super_admin: 'Super Admin', tenant_admin: 'Tenant Admin', hr_manager: 'HR Manager',
    direktur: 'Direktur', pegawai: 'Pegawai', dept_head: 'Dept Head',
    supervisor: 'Supervisor', employee: 'Employee',
  }
  return m[name] ?? name
}

function initials(name = '') {
  return name.split(' ').slice(0, 2).map(w => w[0]).join('').toUpperCase()
}

onMounted(async () => {
  await loadTenant()
  await Promise.all([loadUsers(), loadTemplates(), loadActivity(), userStore.fetchRoles()])
})
</script>

<style scoped>
.page { max-width: 1280px; }
.loading-state { text-align: center; padding: 60px; color: #94a3b8; font-size: 14px; }
.btn-success { background: #16a34a; color: #fff; padding: 8px 14px; border: none; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; }
.btn-success:hover { background: #15803d; }
.btn-warning { background: #ea580c; color: #fff; padding: 8px 14px; border: none; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; }
.btn-warning:hover { background: #c2410c; }
.btn-edit { background: #f1f5f9; color: #475569; padding: 8px 14px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; }
.btn-edit:hover { background: #e2e8f0; }

/* Edit dialog */
.edit-dialog { width: 580px; }
.edit-grid   { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.form-hint   { font-size: 11px; color: #94a3b8; margin-top: 2px; }

.info-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 24px; }
.info-card  { background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px 16px; }
.info-label { font-size: 11px; text-transform: uppercase; letter-spacing: .5px; color: #94a3b8; font-weight: 600; margin-bottom: 4px; }
.info-value { font-size: 14px; font-weight: 600; color: #1e293b; }
.mono { font-family: monospace; font-size: 13px; }

.tab-bar { display: flex; gap: 4px; margin-bottom: 16px; background: #f1f5f9; padding: 4px; border-radius: 10px; width: fit-content; }
.tab-btn { padding: 7px 18px; border: none; border-radius: 7px; background: none; font-size: 13px; font-weight: 500; color: #64748b; cursor: pointer; }
.tab-btn.active { background: #fff; color: #1e293b; font-weight: 600; box-shadow: 0 1px 4px rgba(0,0,0,.08); }

.card        { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; }
.card-toolbar { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; }
.card-title  { font-size: 14px; font-weight: 600; color: #1e293b; display: flex; align-items: center; gap: 8px; }
.count-chip  { background: #e0f2fe; color: #0369a1; font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 99px; }
.card-loader { text-align: center; padding: 32px; color: #94a3b8; font-size: 13px; }
.card-empty  { text-align: center; padding: 32px; color: #94a3b8; font-size: 13px; }

.btn-assign { background: #2563eb; color: #fff; padding: 7px 14px; border: none; border-radius: 7px; font-size: 13px; font-weight: 600; cursor: pointer; }
.btn-assign:hover { background: #1d4ed8; }
.btn-remove { background: none; border: 1px solid #fca5a5; color: #dc2626; border-radius: 5px; padding: 2px 7px; font-size: 11px; cursor: pointer; }
.btn-remove:hover { background: #fee2e2; }

.mini-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.mini-table th { text-align: left; font-size: 11px; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: .4px; padding-bottom: 10px; border-bottom: 1px solid #f1f5f9; }
.mini-table td { padding: 9px 0; border-bottom: 1px solid #f8fafc; color: #334155; }
.mini-table tr:last-child td { border-bottom: none; }
.muted { color: #94a3b8; font-size: 12px; }
.role-chip { background: #f1f5f9; color: #475569; padding: 2px 8px; border-radius: 6px; font-size: 11px; font-weight: 600; }

.type-badge { font-size: 10px; font-weight: 700; padding: 2px 7px; border-radius: 99px; text-transform: uppercase; }
.primary    { background: #dcfce7; color: #15803d; }
.rangkap    { background: #fef9c3; color: #a16207; }

.activity-list { display: flex; flex-direction: column; }
.activity-item { display: flex; align-items: center; gap: 10px; padding: 9px 0; border-bottom: 1px solid #f8fafc; }
.activity-item:last-child { border-bottom: none; }
.action-dot  { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; background: #94a3b8; }
.dot-create  { background: #16a34a; }
.dot-update  { background: #2563eb; }
.dot-delete  { background: #dc2626; }
.dot-login   { background: #94a3b8; }
.dot-approve { background: #16a34a; }
.dot-reject  { background: #dc2626; }
.activity-body { flex: 1; font-size: 13px; color: #334155; }
.activity-who    { font-weight: 600; }
.activity-sep    { color: #94a3b8; }
.activity-action { color: #64748b; }
.activity-entity { color: #2563eb; }
.activity-time { font-size: 11px; color: #94a3b8; white-space: nowrap; }

/* ── Assign Dialog ── */
.overlay { position: fixed; inset: 0; background: rgba(15,23,42,.5); z-index: 9998; display: flex; align-items: center; justify-content: center; padding: 20px; }
.form-dialog { background: #fff; border-radius: 14px; padding: 28px; width: 560px; max-width: 95vw; max-height: 90vh; display: flex; flex-direction: column; gap: 14px; box-shadow: 0 24px 64px rgba(0,0,0,.18); overflow: hidden; }
.dialog-header { flex-shrink: 0; }
.form-title  { font-size: 18px; font-weight: 700; color: #1e293b; margin: 0 0 4px; }
.assign-subtitle { font-size: 13px; color: #64748b; margin: 0; }

.search-row { display: flex; align-items: center; gap: 10px; flex-shrink: 0; }
.form-input { flex: 1; padding: 8px 11px; border: 1px solid #d1d5db; border-radius: 7px; font-size: 13px; outline: none; }
.form-input:focus { border-color: #2563EB; box-shadow: 0 0 0 3px rgba(37,99,235,.1); }
.selected-count { white-space: nowrap; font-size: 12px; font-weight: 700; color: #2563eb; background: #dbeafe; padding: 4px 10px; border-radius: 99px; }

/* Selected chips */
.selected-chips { display: flex; flex-wrap: wrap; gap: 6px; flex-shrink: 0; padding: 4px 0; }
.chip { display: inline-flex; align-items: center; gap: 5px; background: #eff6ff; border: 1px solid #bfdbfe; color: #1d4ed8; font-size: 12px; font-weight: 500; padding: 3px 8px 3px 10px; border-radius: 99px; }
.chip-remove { background: none; border: none; color: #93c5fd; cursor: pointer; font-size: 15px; line-height: 1; padding: 0; margin-left: 2px; }
.chip-remove:hover { color: #1d4ed8; }

/* Search results */
.search-results-wrap { flex: 1; min-height: 120px; max-height: 260px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 8px; }
.search-loading { padding: 20px; text-align: center; font-size: 13px; color: #94a3b8; }
.search-empty   { padding: 20px; text-align: center; font-size: 13px; color: #94a3b8; margin: 0; }
.search-hint    { padding: 20px; text-align: center; font-size: 13px; color: #cbd5e1; margin: 0; }
.search-results { display: flex; flex-direction: column; }
.result-item {
  display: flex; align-items: center; gap: 10px;
  padding: 10px 14px; cursor: pointer; border-bottom: 1px solid #f8fafc;
}
.result-item:last-child { border-bottom: none; }
.result-item:hover:not(.already) { background: #f8fafc; }
.result-item.selected { background: #eff6ff; }
.result-item.already  { opacity: .5; cursor: not-allowed; }
.result-checkbox { width: 16px; height: 16px; accent-color: #2563eb; flex-shrink: 0; cursor: pointer; }
.result-avatar { width: 30px; height: 30px; border-radius: 50%; background: #dbeafe; color: #1d4ed8; font-size: 11px; font-weight: 700; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.result-info { flex: 1; }
.result-name { font-size: 13px; font-weight: 600; color: #1e293b; display: flex; align-items: center; gap: 6px; }
.result-meta { font-size: 11px; color: #94a3b8; margin-top: 1px; }
.already-badge { font-size: 10px; font-weight: 700; background: #dcfce7; color: #15803d; padding: 1px 6px; border-radius: 99px; }

.assign-role-row { flex-shrink: 0; }
.form-label  { display: flex; flex-direction: column; gap: 5px; font-size: 13px; font-weight: 500; color: #374151; }
.form-label .form-input { flex: none; }
.label-hint  { font-size: 11px; color: #94a3b8; font-weight: 400; }

/* Progress */
.assign-progress { flex-shrink: 0; }
.progress-bar  { height: 6px; background: #e2e8f0; border-radius: 99px; overflow: hidden; }
.progress-fill { height: 100%; background: #2563eb; border-radius: 99px; transition: width .3s; }
.progress-label { font-size: 12px; color: #64748b; margin: 6px 0 0; text-align: center; }

.form-error  { color: #dc2626; font-size: 12px; flex-shrink: 0; }
.form-footer { display: flex; justify-content: flex-end; gap: 10px; flex-shrink: 0; padding-top: 4px; border-top: 1px solid #f1f5f9; }
.btn-primary { background: #2563EB; color: #fff; padding: 9px 18px; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
.btn-primary:hover { background: #1d4ed8; }
.btn-primary:disabled { opacity: .6; cursor: not-allowed; }
.btn-cancel  { padding: 9px 18px; border: 1px solid #e2e8f0; border-radius: 8px; background: #fff; font-size: 14px; cursor: pointer; color: #475569; }
.spinner-sm  { width: 14px; height: 14px; border: 2px solid rgba(255,255,255,.3); border-top-color: #fff; border-radius: 50%; animation: spin .6s linear infinite; display: inline-block; }
@keyframes spin { to { transform: rotate(360deg); } }
.fade-enter-active, .fade-leave-active { transition: opacity .2s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

@media (max-width: 900px) { .info-grid { grid-template-columns: repeat(2, 1fr); } }
</style>
