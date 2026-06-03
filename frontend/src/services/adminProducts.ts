import api from './api'

export type AdminProduct = {
  id: number
  name: string
  sku?: string | null
  price?: number | null
  quantity?: number | null
  status?: boolean
  category_id?: number | null
  category_name?: string | null
  description?: string | null
}

export type AdminProductVariant = {
  id: number
  product_id: number
  sku?: string | null
  price?: number | null
  sale_price?: number | null
  quantity?: number | null
  weight?: number | null
  status?: boolean
  options?: Record<string, any> | null
  is_default?: boolean
}

export const loadAdminProducts = async (params: Record<string, any> = {}): Promise<{ products: AdminProduct[]; meta: any }> => {
  const response = await api.get('/products', { params })
  const products = Array.isArray(response?.data?.products) ? response.data.products : []
  const meta = response?.data?.meta ?? {}
  return { products, meta }
}

export const createAdminProduct = async (payload: Partial<AdminProduct>): Promise<AdminProduct> => {
  const response = await api.post('/products', payload)
  return response.data.product
}

export const updateAdminProduct = async (id: number, payload: Partial<AdminProduct>): Promise<AdminProduct> => {
  const response = await api.put(`/products/${id}`, payload)
  return response.data.product
}

export const deleteAdminProduct = async (id: number): Promise<void> => {
  await api.delete(`/products/${id}`)
}

export const getAdminProduct = async (id: number): Promise<AdminProduct> => {
  const response = await api.get(`/products/${id}`)
  return response.data.product
}

export const loadAdminProductVariants = async (productId: number): Promise<AdminProductVariant[]> => {
  const response = await api.get(`/products/${productId}/variants`)
  return Array.isArray(response?.data?.variants) ? response.data.variants : []
}

export const createAdminProductVariant = async (productId: number, payload: Partial<AdminProductVariant>): Promise<AdminProductVariant> => {
  const response = await api.post(`/products/${productId}/variants`, payload)
  return response.data.variant
}

export const updateAdminProductVariant = async (productId: number, variantId: number, payload: Partial<AdminProductVariant>): Promise<AdminProductVariant> => {
  const response = await api.put(`/products/${productId}/variants/${variantId}`, payload)
  return response.data.variant
}

export const deleteAdminProductVariant = async (productId: number, variantId: number): Promise<void> => {
  await api.delete(`/products/${productId}/variants/${variantId}`)
}
