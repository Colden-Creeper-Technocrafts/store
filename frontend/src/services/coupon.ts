import api from './api'

export type CouponValidation = {
  valid: boolean
  code: string
  discount_type: 'percentage' | 'fixed'
  discount_value: number
  discount_amount: number
  description: string | null
}

export const validateCoupon = async (code: string, subtotal: number): Promise<CouponValidation> => {
  const response = await api.post('/coupons/validate', { code, subtotal })
  return response.data
}
