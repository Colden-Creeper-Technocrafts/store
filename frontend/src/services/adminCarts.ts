import api from './api'

export type AdminCartItem = {
  id: number
  product_name: string
  quantity: number
  price: number
}

export type AdminCart = {
  id: number
  user: { id: number; name: string; email: string }
  item_count: number
  subtotal: number
  updated_at: string
  items: AdminCartItem[]
}

export const fetchAdminCarts = async (): Promise<AdminCart[]> => {
  const res = await api.get('/admin/carts')
  return res.data.carts
}

export const clearAdminCart = async (cartId: number): Promise<void> => {
  await api.delete(`/admin/carts/${cartId}`)
}
