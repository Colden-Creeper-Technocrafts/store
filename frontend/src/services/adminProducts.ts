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
  short_description?: string | null
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

export type AdminProductImage = {
  id: number
  product_id: number
  image: string
  image_url: string
  sort_order: number
  is_primary: boolean
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

export const loadAdminProductImages = async (productId: number): Promise<AdminProductImage[]> => {
  const response = await api.get(`/products/${productId}/images`)
  return Array.isArray(response?.data?.images) ? response.data.images : []
}

export const createAdminProductImage = async (
  productId: number,
  payload: { image: File; sort_order?: number | null; is_primary?: boolean }
): Promise<AdminProductImage> => {
  const formData = new FormData()
  formData.append('image', payload.image)

  if (payload.sort_order !== undefined && payload.sort_order !== null) {
    formData.append('sort_order', String(payload.sort_order))
  }

  if (payload.is_primary !== undefined) {
    formData.append('is_primary', payload.is_primary ? '1' : '0')
  }

  const response = await api.post(`/products/${productId}/images`, formData, {
    headers: { 'Content-Type': 'multipart/form-data' },
  })

  return response.data.image
}

export const updateAdminProductImage = async (
  productId: number,
  imageId: number,
  payload: { sort_order?: number | null; is_primary?: boolean }
): Promise<AdminProductImage> => {
  const response = await api.put(`/products/${productId}/images/${imageId}`, payload)
  return response.data.image
}

export const deleteAdminProductImage = async (productId: number, imageId: number): Promise<void> => {
  await api.delete(`/products/${productId}/images/${imageId}`)
}

export const loadProductImageDefaults = async (): Promise<{ default_image: string }> => {
  const response = await api.get('/products/images/defaults')
  return {
    default_image: response?.data?.default_image ?? '',
  }
}
