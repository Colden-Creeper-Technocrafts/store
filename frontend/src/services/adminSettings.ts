import api from './api'

export type StoreFeatures = {
  reviews: boolean
  wishlist: boolean
  subscriptions: boolean
  loyalty: boolean
}

export type StoreSettings = {
  id: number
  store_name: string
  business_type: string | null
  store_email: string | null
  store_phone: string | null
  store_description: string | null
  logo_url: string | null
  favicon_url: string | null
  tagline: string | null
  banner_image: string | null
  banner_title: string | null
  banner_text: string | null
  currency: string
  shipping_charge: number
  free_shipping_threshold: number | null
  features: StoreFeatures | null
  layout: string
  is_active: boolean
}

export type StoreSettingsPayload = {
  store_name?: string
  business_type?: string | null
  store_email?: string | null
  store_phone?: string | null
  store_description?: string | null
  logo_url?: string | null
  favicon_url?: string | null
  tagline?: string | null
  banner_image?: string | null
  banner_title?: string | null
  banner_text?: string | null
  currency?: string
  shipping_charge?: number
  free_shipping_threshold?: number | null
  features?: Partial<StoreFeatures>
  is_active?: boolean
}

export const fetchAllSettings = async (): Promise<StoreSettings[]> => {
  const response = await api.get('/admin/settings')
  return Array.isArray(response.data.settings) ? response.data.settings : []
}

export const updateSettings = async (id: number, payload: StoreSettingsPayload): Promise<StoreSettings> => {
  const response = await api.put(`/admin/settings/${id}`, payload)
  return response.data.settings
}

export const uploadStoreLogo = async (id: number, file: File): Promise<string> => {
  const formData = new FormData()
  formData.append('logo', file)
  const response = await api.post(`/admin/settings/${id}/logo`, formData, {
    headers: { 'Content-Type': 'multipart/form-data' },
  })
  return response.data.logo_url
}

export const uploadStoreFavicon = async (id: number, file: File): Promise<string> => {
  const formData = new FormData()
  formData.append('favicon', file)
  const response = await api.post(`/admin/settings/${id}/favicon`, formData, {
    headers: { 'Content-Type': 'multipart/form-data' },
  })
  return response.data.favicon_url
}

export const uploadBannerImage = async (id: number, file: File): Promise<string> => {
  const formData = new FormData()
  formData.append('banner_image', file)
  const response = await api.post(`/admin/settings/${id}/banner-image`, formData, {
    headers: { 'Content-Type': 'multipart/form-data' },
  })
  return response.data.banner_image
}
