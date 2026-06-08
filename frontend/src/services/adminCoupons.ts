import api from './api'

export type AdminCoupon = {
  id: number
  code: string
  description: string | null
  discount_type: 'percentage' | 'fixed'
  discount_value: number
  min_order_amount: number | null
  max_discount_amount: number | null
  usage_limit: number | null
  used_count: number
  starts_at: string | null
  expires_at: string | null
  is_active: boolean
  created_at: string
}

export type CouponPayload = {
  code: string
  description?: string | null
  discount_type: 'percentage' | 'fixed'
  discount_value: number
  min_order_amount?: number | null
  max_discount_amount?: number | null
  usage_limit?: number | null
  starts_at?: string | null
  expires_at?: string | null
  is_active?: boolean
}

export const loadAdminCoupons = async (): Promise<AdminCoupon[]> => {
  const response = await api.get('/coupons')
  return response.data.coupons
}

export const createAdminCoupon = async (payload: CouponPayload): Promise<AdminCoupon> => {
  const response = await api.post('/coupons', payload)
  return response.data.coupon
}

export const updateAdminCoupon = async (id: number, payload: Partial<CouponPayload>): Promise<AdminCoupon> => {
  const response = await api.put(`/coupons/${id}`, payload)
  return response.data.coupon
}

export const deleteAdminCoupon = async (id: number): Promise<void> => {
  await api.delete(`/coupons/${id}`)
}
