import api from './api'

export type AdminUser = {
  id: number
  name: string
  email: string
  role: string
  created_at: string
}

export type UsersMeta = {
  total: number
  per_page: number
  current_page: number
  last_page: number
}

export type CreateUserPayload = {
  name: string
  email: string
  role: 'Admin' | 'Customer'
  phone?: string
}

export const fetchAdminUsers = async (params: {
  search?: string
  page?: number
  per_page?: number
} = {}): Promise<{ users: AdminUser[]; meta: UsersMeta }> => {
  const response = await api.get('/admin/users', { params })
  return response.data
}

export const createAdminUser = async (payload: CreateUserPayload): Promise<AdminUser> => {
  const response = await api.post('/admin/users', payload)
  return response.data.user
}
