<template>
  <div class="data-table-wrapper">
    <!-- Search + Actions bar -->
    <div class="dt-toolbar">
      <div class="dt-search" v-if="searchable">
        <svg class="dt-search-icon" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/>
        </svg>
        <input
          v-model="searchQuery"
          :placeholder="searchPlaceholder"
          class="dt-search-input"
          @input="$emit('search', searchQuery)"
        />
      </div>
      <div class="dt-actions">
        <slot name="actions" />
      </div>
    </div>

    <!-- Bulk action bar -->
    <Transition name="bulk-bar">
      <div v-if="selectable && selectedIds.size > 0" class="bulk-bar">
        <span class="bulk-count">{{ selectedIds.size }} dipilih</span>
        <slot name="bulk_actions" :selected-ids="[...selectedIds]" :selected-rows="selectedRows" />
        <button class="bulk-clear" @click="clearSelection">✕ Batalkan</button>
      </div>
    </Transition>

    <!-- Table -->
    <div class="dt-scroll">
      <table class="dt-table">
        <thead>
          <tr>
            <th v-if="selectable" class="dt-check-col">
              <input
                type="checkbox"
                class="dt-checkbox"
                :checked="allSelected"
                :indeterminate="someSelected"
                @change="toggleAll"
              />
            </th>
            <th v-for="col in columns" :key="col.key" :style="col.width ? { width: col.width } : {}">
              {{ col.label }}
            </th>
            <th v-if="$slots.row_actions" class="dt-actions-col">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading">
            <td :colspan="colSpan" class="dt-loading">
              <div class="dt-spinner" />
              <span>Loading...</span>
            </td>
          </tr>
          <tr v-else-if="!rows.length">
            <td :colspan="colSpan" class="dt-empty">{{ emptyText }}</td>
          </tr>
          <tr
            v-else
            v-for="row in rows"
            :key="row.id ?? row._key"
            class="dt-row"
            :class="{ 'dt-row--selected': selectable && selectedIds.has(row.id) }"
          >
            <td v-if="selectable" class="dt-check-col" @click.stop>
              <input
                type="checkbox"
                class="dt-checkbox"
                :checked="selectedIds.has(row.id)"
                @change="toggleRow(row)"
              />
            </td>
            <td v-for="col in columns" :key="col.key">
              <slot :name="`cell_${col.key}`" :row="row" :value="getVal(row, col.key)">
                {{ getVal(row, col.key) ?? '—' }}
              </slot>
            </td>
            <td v-if="$slots.row_actions" class="dt-row-actions">
              <slot name="row_actions" :row="row" />
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div v-if="totalPages > 1" class="dt-pagination">
      <span class="dt-page-info">Page {{ currentPage }} of {{ totalPages }}</span>
      <div class="dt-page-btns">
        <button class="dt-page-btn" :disabled="currentPage <= 1" @click="$emit('page', currentPage - 1)">&lsaquo;</button>
        <button
          v-for="p in visiblePages" :key="p"
          class="dt-page-btn"
          :class="{ active: p === currentPage }"
          @click="$emit('page', p)"
        >{{ p }}</button>
        <button class="dt-page-btn" :disabled="currentPage >= totalPages" @click="$emit('page', currentPage + 1)">&rsaquo;</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  columns:           { type: Array,   required: true },
  rows:              { type: Array,   default: () => [] },
  loading:           { type: Boolean, default: false },
  selectable:        { type: Boolean, default: false },
  searchable:        { type: Boolean, default: true },
  searchPlaceholder: { type: String,  default: 'Search...' },
  emptyText:         { type: String,  default: 'No records found.' },
  currentPage:       { type: Number,  default: 1 },
  totalPages:        { type: Number,  default: 1 },
})

defineEmits(['search', 'page'])

const searchQuery = ref('')
const selectedIds = ref(new Set())

const selectedRows = computed(() =>
  props.rows.filter(r => selectedIds.value.has(r.id))
)

const allSelected  = computed(() => props.rows.length > 0 && props.rows.every(r => selectedIds.value.has(r.id)))
const someSelected = computed(() => props.rows.some(r => selectedIds.value.has(r.id)) && !allSelected.value)

const colSpan = computed(() =>
  props.columns.length
  + (props.selectable ? 1 : 0)
  + 1  // actions column always counts
)

function toggleRow(row) {
  const s = new Set(selectedIds.value)
  if (s.has(row.id)) s.delete(row.id)
  else s.add(row.id)
  selectedIds.value = s
}

function toggleAll() {
  if (allSelected.value) {
    selectedIds.value = new Set()
  } else {
    selectedIds.value = new Set(props.rows.map(r => r.id))
  }
}

function clearSelection() {
  selectedIds.value = new Set()
}

function getVal(row, key) {
  return key.split('.').reduce((obj, k) => obj?.[k], row)
}

const visiblePages = computed(() => {
  const pages = []
  const start = Math.max(1, props.currentPage - 2)
  const end   = Math.min(props.totalPages, start + 4)
  for (let i = start; i <= end; i++) pages.push(i)
  return pages
})
</script>

<style scoped>
.data-table-wrapper { background: #fff; border-radius: 10px; box-shadow: 0 1px 4px rgba(0,0,0,.08); overflow: hidden; }

/* Toolbar */
.dt-toolbar { display: flex; align-items: center; justify-content: space-between; padding: 14px 16px; border-bottom: 1px solid #f1f5f9; gap: 12px; }
.dt-search { position: relative; flex: 1; max-width: 320px; }
.dt-search-icon { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); width: 16px; height: 16px; color: #94a3b8; }
.dt-search-input { width: 100%; padding: 8px 12px 8px 32px; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 13px; outline: none; box-sizing: border-box; }
.dt-search-input:focus { border-color: #2563EB; box-shadow: 0 0 0 3px rgba(37,99,235,.1); }
.dt-actions { display: flex; gap: 8px; align-items: center; }

/* Bulk bar */
.bulk-bar {
  display: flex; align-items: center; gap: 10px;
  padding: 10px 16px; background: #eff6ff;
  border-bottom: 1px solid #bfdbfe;
}
.bulk-count  { font-size: 13px; font-weight: 700; color: #1d4ed8; white-space: nowrap; }
.bulk-clear  { margin-left: auto; background: none; border: none; font-size: 12px; color: #64748b; cursor: pointer; padding: 4px 8px; border-radius: 5px; }
.bulk-clear:hover { background: #dbeafe; color: #1d4ed8; }
.bulk-bar-enter-active, .bulk-bar-leave-active { transition: all .2s; overflow: hidden; }
.bulk-bar-enter-from, .bulk-bar-leave-to { max-height: 0; opacity: 0; padding-top: 0; padding-bottom: 0; }
.bulk-bar-enter-to, .bulk-bar-leave-from { max-height: 60px; opacity: 1; }

/* Table */
.dt-scroll { overflow-x: auto; }
.dt-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.dt-table thead th { background: #f8fafc; padding: 10px 14px; text-align: left; font-weight: 600; color: #475569; font-size: 12px; text-transform: uppercase; letter-spacing: .4px; white-space: nowrap; border-bottom: 1px solid #e2e8f0; }
.dt-table td { padding: 11px 14px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }
.dt-row:hover td { background: #f8fafc; }
.dt-row--selected td { background: #eff6ff; }
.dt-row--selected:hover td { background: #e0f2fe; }

/* Checkbox column */
.dt-check-col { width: 40px; padding-left: 14px !important; padding-right: 4px !important; }
.dt-checkbox { width: 15px; height: 15px; accent-color: #2563eb; cursor: pointer; }

/* Empty / loading */
.dt-loading, .dt-empty { text-align: center; padding: 40px 20px; color: #94a3b8; }
.dt-loading { display: flex; align-items: center; justify-content: center; gap: 10px; }
.dt-spinner { width: 18px; height: 18px; border: 2px solid #e2e8f0; border-top-color: #2563EB; border-radius: 50%; animation: spin .7s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

/* Actions column */
.dt-actions-col, .dt-row-actions { text-align: right; white-space: nowrap; }

/* Pagination */
.dt-pagination { display: flex; align-items: center; justify-content: space-between; padding: 12px 16px; border-top: 1px solid #f1f5f9; }
.dt-page-info { font-size: 12px; color: #94a3b8; }
.dt-page-btns { display: flex; gap: 4px; }
.dt-page-btn { min-width: 32px; height: 32px; padding: 0 8px; border: 1px solid #e2e8f0; border-radius: 6px; background: #fff; font-size: 13px; cursor: pointer; color: #475569; }
.dt-page-btn:hover:not(:disabled) { background: #f1f5f9; }
.dt-page-btn.active { background: #2563EB; color: #fff; border-color: #2563EB; font-weight: 600; }
.dt-page-btn:disabled { opacity: .4; cursor: not-allowed; }
</style>
