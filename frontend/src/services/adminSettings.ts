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
  currency: string
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
  currency?: string
  features?: Partial<StoreFeatures>
}

export const fetchSettings = async (): Promise<StoreSettings | null> => {
  const response = await api.get('/admin/settings')
  return response.data.settings
}

export const updateSettings = async (payload: StoreSettingsPayload): Promise<StoreSettings> => {
  const response = await api.put('/admin/settings', payload)
  return response.data.settings
}
