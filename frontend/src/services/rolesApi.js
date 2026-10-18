import api from '@/services/api'

export const fetchRoles = (params = {}) => api.get('/roles', { params })

export const fetchRole = (id) => api.get(`/roles/${id}`)

export const fetchPermissionCatalog = () => api.get('/roles/permissions')

export const createRole = (payload) => api.post('/roles', payload)

export const updateRole = (id, payload) => api.put(`/roles/${id}`, payload)

export const deleteRole = (id) => api.delete(`/roles/${id}`)
