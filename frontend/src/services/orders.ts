import api from './api'

export type OrderItem = {
  id: number
  product_id: number | null
  name: string
  sku: string | null
  price: number
  quantity: number
  subtotal: number
}

export type Order = {
  id: number
  status: string
  payment_status: string
  subtotal: number
  discount_amount: number
  total: number
  coupon_code: string | null
  shipping_name: string
  shipping_email: string
  shipping_phone: string | null
  shipping_address: string
  shipping_city: string
  shipping_postal_code: string
  shipping_country: string
  notes: string | null
  tracking_number: string | null
  tracking_url: string | null
  created_at: string
  items: OrderItem[]
}

export type ShippingPayload = {
  shipping_name: string
  shipping_email: string
  shipping_phone?: string | null
  shipping_address: string
  shipping_city: string
  shipping_postal_code: string
  shipping_country: string
  notes?: string | null
}

export type GuestOrderPayload = ShippingPayload & {
  items: { product_id: number; quantity: number }[]
}

export const placeOrder = async (payload: ShippingPayload): Promise<Order> => {
  const response = await api.post('/checkout', payload)
  return response.data.order
}

export const placeGuestOrder = async (payload: GuestOrderPayload): Promise<Order> => {
  const response = await api.post('/checkout/guest', payload)
  return response.data.order
}

export const fetchOrders = async (): Promise<Order[]> => {
  const response = await api.get('/orders')
  return Array.isArray(response.data.orders) ? response.data.orders : []
}

export const fetchOrder = async (id: number): Promise<Order> => {
  const response = await api.get(`/orders/${id}`)
  return response.data.order
}
