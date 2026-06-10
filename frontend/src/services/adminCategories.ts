import api from './api'

export type AdminCategory = {
  id: number
  name: string
  slug: string
  description?: string | null
  image?: string | null
  parent_category_id?: number | null
  sort_order: number
  is_active: boolean
  children?: AdminCategory[]
}

export const loadAdminCategories = async (): Promise<AdminCategory[]> => {
  const response = await api.get('/categories')
  return Array.isArray(response?.data?.categories) ? response.data.categories : []
}

export const createAdminCategory = async (payload: Partial<AdminCategory>): Promise<AdminCategory> => {
  const response = await api.post('/categories', payload)
  return response.data.category
}

export const updateAdminCategory = async (id: number, payload: Partial<AdminCategory>): Promise<AdminCategory> => {
  const response = await api.put(`/categories/${id}`, payload)
  return response.data.category
}

export const deleteAdminCategory = async (id: number): Promise<void> => {
  await api.delete(`/categories/${id}`)
}

export const uploadCategoryImage = async (id: number, file: File): Promise<string> => {
  const formData = new FormData()
  formData.append('image', file)
  const response = await api.post(`/categories/${id}/image`, formData, {
    headers: { 'Content-Type': 'multipart/form-data' },
  })
  return response.data.image
}
