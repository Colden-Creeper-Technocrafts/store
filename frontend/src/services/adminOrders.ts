import api from './api'

export type Shipment = {
  id: number
  awb_number: string | null
  tracking_url: string | null
  label_url: string | null
  provider_shipment_id: string | null
  status: string
  weight_kg: number | null
  shipped_at: string | null
}

export type TrackingEvent = {
  status: string
  location: string | null
  description: string | null
  timestamp: string | null
}

export type TrackingResult = {
  awb_number: string
  current_status: string | null
  estimated_delivery: string | null
  history: TrackingEvent[]
}

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
  return_status: string | null
  return_reason: string | null
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
  has_return?: boolean
  return_status?: string
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

export const updateOrderReturnStatus = async (
  id: number,
  return_status: string,
  return_reason?: string | null
): Promise<AdminOrder> => {
  const response = await api.patch(`/admin/orders/${id}/return-status`, { return_status, return_reason })
  return response.data.order
}

export const fulfillOrder = async (id: number): Promise<Shipment> => {
  const response = await api.post(`/admin/orders/${id}/fulfill`)
  return response.data.shipment
}

export const getLiveTracking = async (id: number): Promise<TrackingResult> => {
  const response = await api.get(`/admin/orders/${id}/tracking-live`)
  return response.data
}
