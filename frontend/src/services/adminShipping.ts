import api from './api'

// ── Types ─────────────────────────────────────────────────────────────────────

export type ShippingProvider = {
  id: number
  name: string
  slug: string
  description: string | null
  is_active: boolean
  settings: Record<string, unknown> | null
  sort_order: number
  has_credentials: boolean
  methods_count: number
}

export type ShippingZoneLocation = {
  id: number
  shipping_zone_id: number
  type: 'state' | 'pincode_prefix' | 'pincode'
  value: string
}

export type ShippingZone = {
  id: number
  name: string
  description: string | null
  is_active: boolean
  sort_order: number
  locations: ShippingZoneLocation[]
}

export type ShippingMethod = {
  id: number
  shipping_provider_id: number
  name: string
  code: string
  description: string | null
  min_days: number
  max_days: number
  is_active: boolean
  sort_order: number
  provider?: ShippingProvider
}

export type ShippingRate = {
  id: number
  shipping_method_id: number
  shipping_zone_id: number | null
  min_weight_kg: number
  max_weight_kg: number | null
  min_order_amount: number | null
  max_order_amount: number | null
  base_rate: number
  per_kg_rate: number
  is_free: boolean
  sort_order: number
  zone?: ShippingZone | null
}

// ── Providers ─────────────────────────────────────────────────────────────────

export const fetchProviders = async (): Promise<ShippingProvider[]> => {
  const res = await api.get('/admin/shipping/providers')
  return res.data.providers
}

export const updateProvider = async (
  id: number,
  payload: Partial<{ is_active: boolean; settings: Record<string, unknown>; credentials: Record<string, unknown>; sort_order: number }>
): Promise<ShippingProvider> => {
  const res = await api.put(`/admin/shipping/providers/${id}`, payload)
  return res.data.provider
}

export const validateProvider = async (id: number): Promise<{ valid: boolean; message: string }> => {
  const res = await api.post(`/admin/shipping/providers/${id}/validate`)
  return res.data
}

// ── Zones ─────────────────────────────────────────────────────────────────────

export const fetchZones = async (): Promise<ShippingZone[]> => {
  const res = await api.get('/admin/shipping/zones')
  return res.data.zones
}

export const createZone = async (payload: {
  name: string
  description?: string
  is_active?: boolean
  sort_order?: number
}): Promise<ShippingZone> => {
  const res = await api.post('/admin/shipping/zones', payload)
  return res.data.zone
}

export const updateZone = async (id: number, payload: Partial<ShippingZone>): Promise<ShippingZone> => {
  const res = await api.put(`/admin/shipping/zones/${id}`, payload)
  return res.data.zone
}

export const deleteZone = async (id: number): Promise<void> => {
  await api.delete(`/admin/shipping/zones/${id}`)
}

export const addZoneLocation = async (
  zoneId: number,
  payload: { type: ShippingZoneLocation['type']; value: string }
): Promise<ShippingZoneLocation> => {
  const res = await api.post(`/admin/shipping/zones/${zoneId}/locations`, payload)
  return res.data.location
}

export const removeZoneLocation = async (zoneId: number, locationId: number): Promise<void> => {
  await api.delete(`/admin/shipping/zones/${zoneId}/locations/${locationId}`)
}

// ── Methods ───────────────────────────────────────────────────────────────────

export const fetchMethods = async (): Promise<ShippingMethod[]> => {
  const res = await api.get('/admin/shipping/methods')
  return res.data.methods
}

export const createMethod = async (payload: {
  shipping_provider_id: number
  name: string
  code: string
  description?: string
  min_days?: number
  max_days?: number
  is_active?: boolean
  sort_order?: number
}): Promise<ShippingMethod> => {
  const res = await api.post('/admin/shipping/methods', payload)
  return res.data.method
}

export const updateMethod = async (id: number, payload: Partial<ShippingMethod>): Promise<ShippingMethod> => {
  const res = await api.put(`/admin/shipping/methods/${id}`, payload)
  return res.data.method
}

export const deleteMethod = async (id: number): Promise<void> => {
  await api.delete(`/admin/shipping/methods/${id}`)
}

// ── Rates ─────────────────────────────────────────────────────────────────────

export const fetchRates = async (methodId: number): Promise<ShippingRate[]> => {
  const res = await api.get(`/admin/shipping/methods/${methodId}/rates`)
  return res.data.rates
}

export const createRate = async (
  methodId: number,
  payload: Omit<ShippingRate, 'id' | 'shipping_method_id' | 'zone'>
): Promise<ShippingRate> => {
  const res = await api.post(`/admin/shipping/methods/${methodId}/rates`, payload)
  return res.data.rate
}

export const updateRate = async (id: number, payload: Partial<ShippingRate>): Promise<ShippingRate> => {
  const res = await api.put(`/admin/shipping/rates/${id}`, payload)
  return res.data.rate
}

export const deleteRate = async (id: number): Promise<void> => {
  await api.delete(`/admin/shipping/rates/${id}`)
}
