<template>
  <div class="page">
    <AdminPageHeader title="Tenant Management" description="Manage all tenants in the system.">
      <template #actions>
        <button class="btn-primary" @click="openCreate">+ New Tenant</button>
      </template>
    </AdminPageHeader>

    <!-- Stats -->
    <div class="stats-row">
      <MetricCard label="Total Tenants"  :value="store.meta.total ?? store.tenants.length" variant="blue" />
      <MetricCard label="Active"         :value="store.activeTenants.length" variant="green" />
      <MetricCard label="Inactive / Suspended" :value="(store.tenants.length - store.activeTenants.length)" variant="yellow" />
    </div>

    <!-- Table -->
    <DataTable
      :columns="columns"
      :rows="store.tenants"
      :loading="store.loading"
      :current-page="page"
      :total-pages="store.meta.last_page ?? 1"
      @search="q => { search = q; loadData() }"
      @page="p => { page = p; loadData() }"
    >
      <template #actions>
        <select v-model="filterStatus" class="filter-select" @change="loadData">
          <option value="">All Status</option>
          <option value="active">Active</option>
          <option value="inactive">Inactive</option>
          <option value="suspended">Suspended</option>
        </select>
      </template>

      <template #cell_status="{ row }">
        <StatusBadge :status="row.status" dot />
      </template>

      <template #row_actions="{ row }">
        <RouterLink class="act-btn" :to="`/admin/tenants/${row.id}`" title="View Detail">🔎</RouterLink>
        <button class="act-btn" title="Edit" @click="openEdit(row)">✏️</button>
        <button v-if="row.status !== 'active'"   class="act-btn text-green" @click="toggleStatus(row, 'activate')">▶</button>
        <button v-if="row.status === 'active'"   class="act-btn text-red"   @click="toggleStatus(row, 'deactivate')">⏸</button>
      </template>
    </DataTable>

    <!-- Create / Edit Dialog -->
    <Teleport to="body">
      <Transition name="fade">
        <div v-if="showForm" class="overlay" @click.self="showForm = false">
          <div class="form-dialog">
            <h2 class="form-title">{{ editing ? 'Edit Tenant' : 'New Tenant' }}</h2>

            <div class="form-grid">
              <label class="form-label">
                Tenant Code *
                <input v-model="form.tenant_code" class="form-input" :disabled="!!editing" placeholder="e.g. COMP-001" />
              </label>
              <label class="form-label">
                Tenant Name *
                <input v-model="form.tenant_name" class="form-input" placeholder="Nama perusahaan/organisasi" />
                <span class="form-hint">Nama ini tampil di sidebar &amp; topbar user.</span>
              </label>
              <label class="form-label">
                Domain
                <input v-model="form.domain" class="form-input" placeholder="app.company.com" />
              </label>
              <label class="form-label">
                Contact Email
                <input v-model="form.contact_email" type="email" class="form-input" />
              </label>
              <label class="form-label">
                Contact Phone
                <input v-model="form.contact_phone" class="form-input" />
              </label>
              <label class="form-label">
                Status
                <select v-model="form.status" class="form-input">
                  <option value="active">Active</option>
                  <option value="inactive">Inactive</option>
                  <option value="suspended">Suspended</option>
                </select>
              </label>
              <label class="form-label span-2">
                Address
                <textarea v-model="form.address" class="form-input" rows="2" />
              </label>
            </div>

            <p v-if="formError" class="form-error">{{ formError }}</p>

            <div class="form-footer">
              <button class="btn-cancel" @click="showForm = false">Cancel</button>
              <button class="btn-primary" :disabled="saving" @click="save">
                <span v-if="saving" class="spinner-sm" />
                {{ editing ? 'Save Changes' : 'Create Tenant' }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useTenantStore } from '@/stores/tenant'
import DataTable      from '@/components/admin/DataTable.vue'
import StatusBadge    from '@/components/admin/StatusBadge.vue'
import MetricCard     from '@/components/admin/MetricCard.vue'
import AdminPageHeader from '@/components/admin/AdminPageHeader.vue'

const store        = useTenantStore()
const page         = ref(1)
const search       = ref('')
const filterStatus = ref('')
const showForm     = ref(false)
const editing      = ref(null)
const saving       = ref(false)
const formError    = ref('')

const emptyForm = () => ({
  tenant_code: '', tenant_name: '', domain: '',
  contact_email: '', contact_phone: '', address: '', status: 'active',
})
const form = ref(emptyForm())

const columns = [
  { key: 'tenant_code', label: 'Code',    width: '120px' },
  { key: 'tenant_name', label: 'Name' },
  { key: 'domain',      label: 'Domain' },
  { key: 'contact_email', label: 'Email' },
  { key: 'status',      label: 'Status',  width: '110px' },
  { key: 'created_at',  label: 'Created', width: '130px' },
]

async function loadData() {
  await store.fetchAll({ page: page.value, search: search.value, status: filterStatus.value })
}

function openCreate() {
  editing.value = null
  form.value = emptyForm()
  formError.value = ''
  showForm.value = true
}

function openEdit(row) {
  editing.value = row
  form.value = { ...row }
  formError.value = ''
  showForm.value = true
}

async function save() {
  formError.value = ''
  saving.value = true
  try {
    if (editing.value) {
      await store.update(editing.value.id, form.value)
    } else {
      await store.create(form.value)
    }
    showForm.value = false
    await loadData()
  } catch (e) {
    formError.value = e.response?.data?.message || e.userMessage || 'Failed to save.'
  } finally {
    saving.value = false
  }
}

async function toggleStatus(row, action) {
  await store.setStatus(row.id, action)
}

onMounted(loadData)
</script>

<style scoped>
.page { max-width: 1280px; }
.stats-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 24px; }
.btn-primary { background: #2563EB; color: #fff; padding: 9px 18px; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; }
.btn-primary:hover { background: #1d4ed8; }
.btn-primary:disabled { opacity: .6; cursor: not-allowed; display: flex; align-items: center; gap: 8px; }
.filter-select { padding: 7px 10px; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 13px; background: #fff; }
.act-btn { background: none; border: none; padding: 4px 8px; cursor: pointer; font-size: 16px; border-radius: 4px; }
.act-btn:hover { background: #f1f5f9; }
.text-green { color: #16a34a; }
.text-red   { color: #dc2626; }
.overlay { position: fixed; inset: 0; background: rgba(15,23,42,.5); z-index: 9998; display: flex; align-items: center; justify-content: center; }
.form-dialog { background: #fff; border-radius: 14px; padding: 28px; width: 560px; max-width: 95vw; max-height: 90vh; overflow-y: auto; box-shadow: 0 24px 64px rgba(0,0,0,.18); }
.form-title  { font-size: 18px; font-weight: 700; color: #1e293b; margin-bottom: 20px; }
.form-grid   { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.form-label  { display: flex; flex-direction: column; gap: 5px; font-size: 13px; font-weight: 500; color: #374151; }
.form-input  { padding: 8px 11px; border: 1px solid #d1d5db; border-radius: 7px; font-size: 13px; outline: none; width: 100%; }
.form-input:focus { border-color: #2563EB; box-shadow: 0 0 0 3px rgba(37,99,235,.1); }
.form-input:disabled { background: #f8fafc; color: #94a3b8; }
.form-hint { font-size: 11px; color: #94a3b8; }
.span-2 { grid-column: span 2; }
.form-error  { color: #dc2626; font-size: 13px; margin-top: 10px; }
.form-footer { display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; padding-top: 16px; border-top: 1px solid #f1f5f9; }
.btn-cancel  { padding: 9px 18px; border: 1px solid #e2e8f0; border-radius: 8px; background: #fff; font-size: 14px; cursor: pointer; color: #475569; }
.spinner-sm  { width: 14px; height: 14px; border: 2px solid rgba(255,255,255,.3); border-top-color: #fff; border-radius: 50%; animation: spin .6s linear infinite; display: inline-block; }
@keyframes spin { to { transform: rotate(360deg); } }
.fade-enter-active, .fade-leave-active { transition: opacity .2s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
@media (max-width: 640px) { .stats-row { grid-template-columns: 1fr; } .form-grid { grid-template-columns: 1fr; } .span-2 { grid-column: span 1; } }
</style>
