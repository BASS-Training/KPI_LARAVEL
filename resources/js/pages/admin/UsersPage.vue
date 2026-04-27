<template>
  <div class="page">
    <AdminPageHeader title="User Management" description="Manage users and their roles across tenants.">
      <template #actions>
        <button class="btn-primary" @click="openCreate">+ New User</button>
      </template>
    </AdminPageHeader>

    <!-- Stats -->
    <div class="stats-row">
      <MetricCard label="Total Users"  :value="store.meta.total ?? store.users.length" variant="blue" />
      <MetricCard label="Active Roles" :value="store.roles.length" variant="green" />
      <MetricCard label="Showing"      :value="store.users.length" variant="yellow" />
    </div>

    <DataTable
      :columns="columns"
      :rows="store.users"
      :loading="store.loading"
      :selectable="true"
      :current-page="page"
      :total-pages="store.meta.last_page ?? 1"
      empty-text="No users found."
      @search="q => { search = q; page = 1; loadData() }"
      @page="p => { page = p; loadData() }"
    >
      <!-- Toolbar filter -->
      <template #actions>
        <select v-model="filterRole" class="filter-select" @change="page = 1; loadData()">
          <option value="">All Roles</option>
          <option v-for="r in store.roles" :key="r.name" :value="r.name">{{ formatRole(r.name) }}</option>
        </select>
      </template>

      <!-- Bulk action bar -->
      <template #bulk_actions="{ selectedIds, selectedRows }">
        <button
          v-if="auth.isSuperAdmin || auth.user?.role === 'tenant_admin'"
          class="bulk-btn bulk-btn--blue"
          @click="openBulkAssign(selectedRows)"
        >
          🔗 Link ke Tenant ({{ selectedIds.length }})
        </button>
        <button
          class="bulk-btn bulk-btn--red"
          @click="openBulkDelete(selectedRows)"
        >
          🗑️ Hapus ({{ selectedIds.length }})
        </button>
      </template>

      <!-- Cell slots -->
      <template #cell_nama="{ row }">
        <div class="user-cell">
          <span class="user-avatar-sm">{{ initials(row.nama) }}</span>
          <div>
            <div class="user-name">{{ row.nama }}</div>
            <div class="user-nip">{{ row.nip }}</div>
          </div>
        </div>
      </template>

      <template #cell_role="{ row }">
        <span class="role-badge" :class="`role-${roleColor(primaryRole(row))}`">
          {{ formatRole(primaryRole(row)) }}
        </span>
      </template>

      <template #cell_departemen="{ row }">
        {{ row.departemen ?? row.department?.name ?? '—' }}
      </template>

      <template #cell_status_karyawan="{ row }">
        <StatusBadge :status="row.status_karyawan === 'Tetap' ? 'active' : 'inactive'" :label="row.status_karyawan" dot />
      </template>

      <!-- Per-row actions -->
      <template #row_actions="{ row }">
        <button class="act-btn" title="Edit" @click="openEdit(row)">✏️</button>
        <button
          v-if="auth.isSuperAdmin || auth.user?.role === 'tenant_admin'"
          class="act-btn"
          title="Link ke Tenant Lain"
          @click="openSingleAssign(row)"
        >🔗</button>
        <button class="act-btn text-red" title="Delete" @click="confirmDelete(row)">🗑️</button>
      </template>
    </DataTable>

    <!-- ───────── Create / Edit Dialog ───────── -->
    <Teleport to="body">
      <Transition name="fade">
        <div v-if="showForm" class="overlay" @click.self="showForm = false">
          <div class="form-dialog">
            <h2 class="form-title">{{ editing ? 'Edit User' : 'New User' }}</h2>

            <div class="form-grid">
              <label class="form-label">
                NIP *
                <input v-model="form.nip" class="form-input" :disabled="!!editing" placeholder="EMP-001" />
              </label>
              <label class="form-label">
                Full Name *
                <input v-model="form.nama" class="form-input" placeholder="Budi Santoso" />
              </label>
              <label class="form-label">
                Email
                <input v-model="form.email" type="email" class="form-input" />
              </label>
              <label class="form-label">
                Phone
                <input v-model="form.no_hp" class="form-input" placeholder="+628..." />
              </label>

              <!-- Department dropdown -->
              <label class="form-label">
                Department *
                <select v-model="form.department_id" class="form-input" @change="onDeptChange">
                  <option value="">— Pilih Department —</option>
                  <option v-for="d in deptStore.departments" :key="d.id" :value="d.id">{{ d.nama }}</option>
                </select>
                <input
                  v-if="!deptStore.departments.length"
                  v-model="form.departemen"
                  class="form-input"
                  placeholder="Human Resources"
                />
              </label>

              <!-- Position dropdown (filtered by dept) -->
              <label class="form-label">
                Position / Title *
                <select v-model="form.position_id" class="form-input" @change="onPosChange">
                  <option value="">— Pilih Jabatan —</option>
                  <option v-for="p in filteredPositions" :key="p.id" :value="p.id">{{ p.nama }}</option>
                </select>
                <input
                  v-if="!filteredPositions.length"
                  v-model="form.jabatan"
                  class="form-input"
                  placeholder="HR Manager"
                />
              </label>

              <label class="form-label">
                Employment Status *
                <select v-model="form.status_karyawan" class="form-input">
                  <option value="Tetap">Tetap</option>
                  <option value="Kontrak">Kontrak</option>
                  <option value="Magang">Magang</option>
                  <option value="Outsourcing">Outsourcing</option>
                </select>
              </label>
              <label class="form-label">
                Join Date *
                <input v-model="form.tanggal_masuk" type="date" class="form-input" />
              </label>
              <label class="form-label">
                Role *
                <select v-model="form.role" class="form-input">
                  <option value="">— Select role —</option>
                  <option v-for="r in store.roles" :key="r.name" :value="r.name">{{ formatRole(r.name) }}</option>
                </select>
              </label>
              <label class="form-label">
                Password {{ editing ? '(leave blank to keep)' : '*' }}
                <input v-model="form.password" type="password" class="form-input" :placeholder="editing ? 'unchanged' : 'min 8 chars'" autocomplete="new-password" />
              </label>

              <!-- Tenant selector — Super Admin only, create mode -->
              <label v-if="auth.isSuperAdmin && !editing" class="form-label span-2">
                Assign to Tenant
                <select v-model="form.tenant_id" class="form-input" @change="onTenantChange">
                  <option value="">— Auto (current context) —</option>
                  <option v-for="t in tenantStore.tenants" :key="t.id" :value="t.id">
                    {{ t.tenant_name }} ({{ t.tenant_code }})
                  </option>
                </select>
              </label>
            </div>

            <div v-if="fieldErrors.length" class="field-errors">
              <p v-for="(e, i) in fieldErrors" :key="i" class="field-error-item">⚠ {{ e }}</p>
            </div>
            <p v-else-if="formError" class="form-error">{{ formError }}</p>

            <div class="form-footer">
              <button class="btn-cancel" @click="showForm = false">Cancel</button>
              <button class="btn-primary" :disabled="saving" @click="save">
                <span v-if="saving" class="spinner-sm" />
                {{ editing ? 'Save Changes' : 'Create User' }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- ───────── Assign to Tenant Dialog (single OR bulk) ───────── -->
    <Teleport to="body">
      <Transition name="fade">
        <div v-if="showAssign" class="overlay" @click.self="!assigning && (showAssign = false)">
          <div class="form-dialog form-dialog--sm">
            <h2 class="form-title">🔗 Link ke Tenant</h2>
            <p class="assign-subtitle">
              <template v-if="assignTargets.length === 1">
                User <strong>{{ assignTargets[0].nama }}</strong> akan mendapat akses rangkap di tenant lain.
              </template>
              <template v-else>
                <strong>{{ assignTargets.length }} user</strong> akan mendapat akses rangkap di tenant yang dipilih.
              </template>
            </p>

            <!-- Selected user chips (bulk) -->
            <div v-if="assignTargets.length > 1" class="selected-chips">
              <span v-for="u in assignTargets" :key="u.id" class="chip">{{ u.nama }}</span>
            </div>

            <div class="form-grid form-grid--1">
              <label class="form-label">
                Tenant *
                <select v-model="assignForm.tenant_id" class="form-input">
                  <option value="">— Select tenant —</option>
                  <option v-for="t in tenantStore.activeTenants" :key="t.id" :value="t.id">
                    {{ t.tenant_name }} ({{ t.tenant_code }})
                  </option>
                </select>
              </label>
              <label class="form-label">
                Role di Tenant Ini
                <select v-model="assignForm.role" class="form-input">
                  <option value="">— Sama dengan role utama —</option>
                  <option v-for="r in store.roles" :key="r.name" :value="r.name">{{ formatRole(r.name) }}</option>
                </select>
              </label>
            </div>

            <!-- Progress (bulk) -->
            <div v-if="assigning && assignTargets.length > 1" class="assign-progress">
              <div class="progress-bar">
                <div class="progress-fill" :style="{ width: assignProgress + '%' }" />
              </div>
              <p class="progress-label">Menyimpan {{ assignDone }}/{{ assignTargets.length }}…</p>
            </div>

            <p v-if="assignError" class="form-error">{{ assignError }}</p>

            <div class="form-footer">
              <button class="btn-cancel" :disabled="assigning" @click="showAssign = false">Cancel</button>
              <button class="btn-primary" :disabled="!assignForm.tenant_id || assigning" @click="doAssign">
                <span v-if="assigning" class="spinner-sm" />
                Assign{{ assignTargets.length > 1 ? ` (${assignTargets.length})` : '' }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- ───────── Delete Confirm (single) ───────── -->
    <ConfirmDialog
      v-model="showDelete"
      title="Delete User"
      :message="`Hapus ${deleteTarget?.nama}? Tindakan ini tidak bisa dibatalkan.`"
      variant="danger"
      confirm-label="Delete"
      :loading="deleting"
      @confirm="doDelete"
    />

    <!-- ───────── Bulk Delete Confirm ───────── -->
    <ConfirmDialog
      v-model="showBulkDelete"
      title="Hapus Users"
      :message="`Hapus ${bulkDeleteTargets.length} user yang dipilih? Tindakan ini tidak bisa dibatalkan.`"
      variant="danger"
      confirm-label="Hapus Semua"
      :loading="bulkDeleting"
      @confirm="doBulkDelete"
    />
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useUserManagementStore } from '@/stores/userManagement'
import { useTenantStore }         from '@/stores/tenant'
import { useDepartmentStore }     from '@/stores/department'
import { usePositionStore }       from '@/stores/position'
import { useAuthStore }           from '@/stores/auth'
import DataTable       from '@/components/admin/DataTable.vue'
import StatusBadge     from '@/components/admin/StatusBadge.vue'
import MetricCard      from '@/components/admin/MetricCard.vue'
import ConfirmDialog   from '@/components/admin/ConfirmDialog.vue'
import AdminPageHeader from '@/components/admin/AdminPageHeader.vue'

const store       = useUserManagementStore()
const tenantStore = useTenantStore()
const deptStore   = useDepartmentStore()
const posStore    = usePositionStore()
const auth        = useAuthStore()

const page       = ref(1)
const search     = ref('')
const filterRole = ref('')

// ── Create / Edit ─────────────────────────────────
const showForm    = ref(false)
const editing     = ref(null)
const saving      = ref(false)
const formError   = ref('')
const fieldErrors = ref([])

// ── Single delete ──────────────────────────────────
const showDelete   = ref(false)
const deleteTarget = ref(null)
const deleting     = ref(false)

// ── Bulk delete ────────────────────────────────────
const showBulkDelete     = ref(false)
const bulkDeleteTargets  = ref([])
const bulkDeleting       = ref(false)

// ── Assign to tenant (single + bulk) ─────────────
const showAssign     = ref(false)
const assignTargets  = ref([])   // array of user objects
const assigning      = ref(false)
const assignDone     = ref(0)
const assignProgress = ref(0)
const assignError    = ref('')
const assignForm     = ref({ tenant_id: '', role: '' })

// ── Form data ──────────────────────────────────────
const emptyForm = () => ({
  nip: '', nama: '', email: '', no_hp: '',
  jabatan: '', departemen: '',
  department_id: '', position_id: '',
  status_karyawan: 'Tetap', tanggal_masuk: '',
  role: '', password: '', tenant_id: '',
})
const form = ref(emptyForm())

const columns = [
  { key: 'nama',            label: 'User',       width: '220px' },
  { key: 'email',           label: 'Email' },
  { key: 'departemen',      label: 'Department', width: '150px' },
  { key: 'role',            label: 'Role',       width: '130px' },
  { key: 'status_karyawan', label: 'Status',     width: '110px' },
]

// Positions filtered by selected department
const filteredPositions = computed(() => {
  if (!form.value.department_id) return posStore.positions
  return posStore.byDepartment(Number(form.value.department_id))
})

// Reload depts/positions when super_admin picks a target tenant
watch(() => form.value.tenant_id, async (tenantId) => {
  form.value.department_id = ''
  form.value.position_id   = ''
  form.value.departemen    = ''
  form.value.jabatan       = ''
  const params = tenantId ? { tenant_id: tenantId } : {}
  await Promise.all([
    deptStore.fetchDepartments(params),
    posStore.fetchPositions(params),
  ])
})

function onDeptChange() {
  const dept = deptStore.departments.find(d => d.id == form.value.department_id)
  form.value.departemen  = dept?.nama ?? ''
  form.value.position_id = ''
  form.value.jabatan     = ''
}

function onPosChange() {
  const pos = posStore.positions.find(p => p.id == form.value.position_id)
  form.value.jabatan = pos?.nama ?? ''
}

function onTenantChange() {}

// ── Data loading ───────────────────────────────────
async function loadData() {
  await store.fetchAll({ page: page.value, search: search.value, role: filterRole.value })
}

// ── Create / Edit ──────────────────────────────────
function openCreate() {
  editing.value     = null
  form.value        = emptyForm()
  formError.value   = ''
  fieldErrors.value = []
  showForm.value    = true
}

function openEdit(row) {
  editing.value = row
  form.value = {
    ...emptyForm(),
    ...row,
    role:          primaryRole(row),
    password:      '',
    tanggal_masuk: row.tanggal_masuk ? row.tanggal_masuk.split('T')[0] : '',
    department_id: row.department_id ?? '',
    position_id:   row.position_id ?? '',
  }
  formError.value   = ''
  fieldErrors.value = []
  showForm.value    = true
}

async function save() {
  formError.value   = ''
  fieldErrors.value = []
  saving.value      = true
  try {
    const payload = { ...form.value }
    if (editing.value && !payload.password) delete payload.password
    if (!payload.tenant_id)     delete payload.tenant_id
    if (!payload.department_id) delete payload.department_id
    if (!payload.position_id)   delete payload.position_id
    if (!payload.departemen && payload.department_id) {
      const d = deptStore.departments.find(x => x.id == payload.department_id)
      if (d) payload.departemen = d.nama
    }
    if (!payload.jabatan && payload.position_id) {
      const p = posStore.positions.find(x => x.id == payload.position_id)
      if (p) payload.jabatan = p.nama
    }

    editing.value
      ? await store.update(editing.value.id, payload)
      : await store.create(payload)

    showForm.value = false
    await loadData()
  } catch (e) {
    const errors = e.response?.data?.errors
    if (errors) {
      fieldErrors.value = Object.entries(errors).map(([f, msgs]) => `${fieldLabel(f)}: ${msgs[0]}`)
    } else {
      formError.value = e.response?.data?.message || e.userMessage || 'Failed to save.'
    }
  } finally {
    saving.value = false
  }
}

// ── Single delete ──────────────────────────────────
function confirmDelete(row) {
  deleteTarget.value = row
  showDelete.value   = true
}

async function doDelete() {
  deleting.value = true
  try {
    await store.remove(deleteTarget.value.id)
    showDelete.value = false
    await loadData()
  } finally {
    deleting.value = false
  }
}

// ── Bulk delete ────────────────────────────────────
function openBulkDelete(rows) {
  bulkDeleteTargets.value = rows
  showBulkDelete.value    = true
}

async function doBulkDelete() {
  bulkDeleting.value = true
  try {
    await Promise.all(bulkDeleteTargets.value.map(u => store.remove(u.id)))
    showBulkDelete.value = false
    await loadData()
  } finally {
    bulkDeleting.value = false
  }
}

// ── Assign (single) ────────────────────────────────
function openSingleAssign(row) {
  assignTargets.value  = [row]
  assignForm.value     = { tenant_id: '', role: '' }
  assignError.value    = ''
  assignDone.value     = 0
  assignProgress.value = 0
  showAssign.value     = true
}

// ── Assign (bulk) ──────────────────────────────────
function openBulkAssign(rows) {
  assignTargets.value  = rows
  assignForm.value     = { tenant_id: '', role: '' }
  assignError.value    = ''
  assignDone.value     = 0
  assignProgress.value = 0
  showAssign.value     = true
}

async function doAssign() {
  if (!assignForm.value.tenant_id) {
    assignError.value = 'Pilih tenant terlebih dahulu.'
    return
  }
  assigning.value      = true
  assignError.value    = ''
  assignDone.value     = 0
  assignProgress.value = 0
  const errors = []

  for (const u of assignTargets.value) {
    try {
      await store.assignToTenant(u.id, {
        tenant_id:  assignForm.value.tenant_id,
        role:       assignForm.value.role || undefined,
        is_primary: false,
      })
      assignDone.value++
      assignProgress.value = Math.round((assignDone.value / assignTargets.value.length) * 100)
    } catch (e) {
      errors.push(`${u.nama}: ${e.response?.data?.message || 'Gagal'}`)
    }
  }

  assigning.value = false
  if (errors.length) {
    assignError.value = errors.join(' | ')
  } else {
    showAssign.value = false
  }
}

// ── Helpers ────────────────────────────────────────
function primaryRole(row) {
  return row.role || row.roles?.[0]?.name || ''
}

function formatRole(name) {
  const map = {
    super_admin: 'Super Admin', tenant_admin: 'Tenant Admin',
    hr_manager: 'HR Manager', direktur: 'Direktur',
    pegawai: 'Pegawai', dept_head: 'Dept Head',
    supervisor: 'Supervisor', employee: 'Employee',
  }
  return map[name] ?? name
}

function fieldLabel(field) {
  const map = {
    nip: 'NIP', nama: 'Full Name', email: 'Email', jabatan: 'Position',
    departemen: 'Department', status_karyawan: 'Employment Status',
    tanggal_masuk: 'Join Date', role: 'Role', password: 'Password',
    tenant_id: 'Tenant', department_id: 'Department', position_id: 'Position',
  }
  return map[field] ?? field
}

function roleColor(name) {
  const map = {
    super_admin: 'red', tenant_admin: 'purple', hr_manager: 'blue',
    direktur: 'indigo', pegawai: 'gray', dept_head: 'teal',
    supervisor: 'orange', employee: 'gray',
  }
  return map[name] ?? 'gray'
}

function initials(name = '') {
  return name.split(' ').slice(0, 2).map(w => w[0]).join('').toUpperCase()
}

onMounted(async () => {
  const tasks = [loadData(), store.fetchRoles(), deptStore.fetchDepartments(), posStore.fetchPositions()]
  if (auth.isSuperAdmin) tasks.push(tenantStore.fetchAll({ per_page: 100 }))
  await Promise.all(tasks)
})
</script>

<style scoped>
.page { max-width: 1280px; }
.stats-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 24px; }
.filter-select { padding: 7px 10px; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 13px; background: #fff; }

/* Bulk action buttons (inside bulk bar slot) */
.bulk-btn { padding: 5px 13px; border: none; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 5px; }
.bulk-btn--blue { background: #dbeafe; color: #1d4ed8; }
.bulk-btn--blue:hover { background: #bfdbfe; }
.bulk-btn--red  { background: #fee2e2; color: #b91c1c; }
.bulk-btn--red:hover  { background: #fecaca; }

/* Primary button */
.btn-primary { background: #2563EB; color: #fff; padding: 9px 18px; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
.btn-primary:hover { background: #1d4ed8; }
.btn-primary:disabled { opacity: .6; cursor: not-allowed; }

/* User cell */
.user-cell { display: flex; align-items: center; gap: 10px; }
.user-avatar-sm { width: 32px; height: 32px; border-radius: 50%; background: #dbeafe; color: #1d4ed8; font-size: 12px; font-weight: 700; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.user-name { font-size: 13px; font-weight: 600; color: #1e293b; }
.user-nip  { font-size: 11px; color: #94a3b8; margin-top: 1px; }

/* Role badges */
.role-badge { padding: 2px 9px; border-radius: 99px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
.role-red    { background: #fee2e2; color: #b91c1c; }
.role-purple { background: #f3e8ff; color: #7e22ce; }
.role-blue   { background: #dbeafe; color: #1d4ed8; }
.role-indigo { background: #e0e7ff; color: #4338ca; }
.role-teal   { background: #ccfbf1; color: #0f766e; }
.role-orange { background: #ffedd5; color: #c2410c; }
.role-gray   { background: #f1f5f9; color: #475569; }

/* Row actions */
.act-btn { background: none; border: none; padding: 4px 8px; cursor: pointer; font-size: 16px; border-radius: 4px; }
.act-btn:hover { background: #f1f5f9; }
.text-red { color: #dc2626; }

/* Dialog */
.overlay { position: fixed; inset: 0; background: rgba(15,23,42,.5); z-index: 9998; display: flex; align-items: center; justify-content: center; padding: 20px; }
.form-dialog { background: #fff; border-radius: 14px; padding: 28px; width: 640px; max-width: 95vw; max-height: 90vh; overflow-y: auto; box-shadow: 0 24px 64px rgba(0,0,0,.18); }
.form-dialog--sm { width: 460px; }
.form-title  { font-size: 18px; font-weight: 700; color: #1e293b; margin-bottom: 8px; }
.assign-subtitle { font-size: 13px; color: #64748b; margin-bottom: 14px; }

/* Selected chips */
.selected-chips { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 14px; max-height: 96px; overflow-y: auto; }
.chip { display: inline-flex; align-items: center; background: #eff6ff; border: 1px solid #bfdbfe; color: #1d4ed8; font-size: 11px; font-weight: 500; padding: 3px 9px; border-radius: 99px; }

/* Form */
.form-grid   { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.form-grid--1 { grid-template-columns: 1fr; }
.span-2      { grid-column: span 2; }
.form-label  { display: flex; flex-direction: column; gap: 5px; font-size: 13px; font-weight: 500; color: #374151; }
.form-input  { padding: 8px 11px; border: 1px solid #d1d5db; border-radius: 7px; font-size: 13px; outline: none; }
.form-input:focus { border-color: #2563EB; box-shadow: 0 0 0 3px rgba(37,99,235,.1); }
.form-input:disabled { background: #f8fafc; color: #94a3b8; }
.form-label .form-input + .form-input { margin-top: 6px; }

/* Errors */
.field-errors { margin-top: 12px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 10px 14px; }
.field-error-item { font-size: 12px; color: #b91c1c; margin: 0; padding: 2px 0; }
.form-error  { color: #dc2626; font-size: 13px; margin-top: 10px; }

/* Progress */
.assign-progress { margin: 10px 0; }
.progress-bar  { height: 6px; background: #e2e8f0; border-radius: 99px; overflow: hidden; }
.progress-fill { height: 100%; background: #2563eb; border-radius: 99px; transition: width .3s; }
.progress-label { font-size: 12px; color: #64748b; margin: 5px 0 0; text-align: center; }

.form-footer { display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; padding-top: 16px; border-top: 1px solid #f1f5f9; }
.btn-cancel  { padding: 9px 18px; border: 1px solid #e2e8f0; border-radius: 8px; background: #fff; font-size: 14px; cursor: pointer; color: #475569; }
.spinner-sm  { width: 14px; height: 14px; border: 2px solid rgba(255,255,255,.3); border-top-color: #fff; border-radius: 50%; animation: spin .6s linear infinite; display: inline-block; }
@keyframes spin { to { transform: rotate(360deg); } }
.fade-enter-active, .fade-leave-active { transition: opacity .2s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
@media (max-width: 640px) { .stats-row { grid-template-columns: 1fr; } .form-grid { grid-template-columns: 1fr; } .span-2 { grid-column: span 1; } }
</style>
