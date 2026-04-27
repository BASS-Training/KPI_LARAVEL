import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/services/api'

export const useUserManagementStore = defineStore('userManagement', () => {
  const users   = ref([])
  const roles   = ref([])
  const meta    = ref({})
  const loading = ref(false)
  const error   = ref(null)

  async function fetchAll(params = {}) {
    loading.value = true
    error.value = null
    try {
      const { data } = await api.get('/v2/users', { params })
      users.value = data.data
      meta.value = { total: data.total, last_page: data.last_page, current_page: data.current_page }
    } catch (e) {
      error.value = e.userMessage || 'Failed to load users.'
    } finally {
      loading.value = false
    }
  }

  async function fetchRoles() {
    const { data } = await api.get('/v2/users/roles')
    roles.value = data.data
    return data.data
  }

  async function create(payload) {
    const { data } = await api.post('/v2/users', payload)
    users.value.unshift(data.data)
    return data
  }

  async function update(id, payload) {
    const { data } = await api.put(`/v2/users/${id}`, payload)
    const idx = users.value.findIndex(u => u.id === id)
    if (idx !== -1) users.value[idx] = data.data
    return data
  }

  async function remove(id) {
    await api.delete(`/v2/users/${id}`)
    users.value = users.value.filter(u => u.id !== id)
  }

  async function assignToTenant(userId, payload) {
    const { data } = await api.post(`/v2/users/${userId}/tenants`, payload)
    return data
  }

  async function removeFromTenant(userId, tenantId) {
    await api.delete(`/v2/users/${userId}/tenants/${tenantId}`)
  }

  return { users, roles, meta, loading, error, fetchAll, fetchRoles, create, update, remove, assignToTenant, removeFromTenant }
})
