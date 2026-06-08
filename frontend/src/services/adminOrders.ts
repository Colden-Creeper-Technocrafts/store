import api from './api'

export type AdminOrderItem = {
  id: number
  product_id: number | null
  name: string
  sku: string | null
  price: number
  quantity: number
  subtotal: number
}

export type StatusHistoryEntry = {
  id: number
  from_status: string | null
  to_status: string
  note: string | null
  created_at: string
  changed_by: { id: number; name: string } | null
}

export type AdminOrder = {
  id: number
  user_id: number | null
  user: { id: number; name: string; email: string } | null
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
  admin_notes: string | null
  tracking_number: string | null
  tracking_url: string | null
  created_at: string
  items: AdminOrderItem[]
  status_history?: StatusHistoryEntry[]
}

export type AdminOrderListMeta = {
  total: number
  per_page: number
  current_page: number
  last_page: number
}

export type OrderFilters = {
  status?: string
  payment_status?: string
  search?: string
  date_from?: string
  date_to?: string
  page?: number
  per_page?: number
}

export const fetchAdminOrders = async (
  filters: OrderFilters = {}
): Promise<{ orders: AdminOrder[]; meta: AdminOrderListMeta }> => {
  const response = await api.get('/admin/orders', { params: filters })
  return response.data
}

export const fetchAdminOrder = async (id: number): Promise<AdminOrder> => {
  const response = await api.get(`/admin/orders/${id}`)
  return response.data.order
}

export const updateOrderStatus = async (id: number, status: string, note?: string): Promise<AdminOrder> => {
  const response = await api.patch(`/admin/orders/${id}/status`, { status, note })
  return response.data.order
}

export const updateOrderPaymentStatus = async (id: number, payment_status: string): Promise<AdminOrder> => {
  const response = await api.patch(`/admin/orders/${id}/payment-status`, { payment_status })
  return response.data.order
}

export const updateOrderTracking = async (
  id: number,
  tracking_number: string | null,
  tracking_url: string | null
): Promise<AdminOrder> => {
  const response = await api.patch(`/admin/orders/${id}/tracking`, { tracking_number, tracking_url })
  return response.data.order
}

export const updateOrderNotes = async (id: number, admin_notes: string | null): Promise<AdminOrder> => {
  const response = await api.patch(`/admin/orders/${id}/notes`, { admin_notes })
  return response.data.order
}
