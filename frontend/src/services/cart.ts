import api from './api'

export type CartProduct = {
  id: number
  name: string
  sku: string | null
  price: number
  sale_price: number | null
  image_url: string
  stock?: number | null
}

export type CartItem = {
  id: number
  product_id: number
  quantity: number
  line_total: number
  product: CartProduct
}

export type Cart = {
  id: number
  items: CartItem[]
  item_count: number
  subtotal: number
  total: number
}

export const fetchCart = async (): Promise<Cart> => {
  const response = await api.get('/cart')
  return response.data.cart
}

export const addToCart = async (productId: number, quantity = 1): Promise<Cart> => {
  const response = await api.post('/cart/items', { product_id: productId, quantity })
  return response.data.cart
}

export const updateCartItem = async (itemId: number, quantity: number): Promise<Cart> => {
  const response = await api.put(`/cart/items/${itemId}`, { quantity })
  return response.data.cart
}

export const removeCartItem = async (itemId: number): Promise<Cart> => {
  const response = await api.delete(`/cart/items/${itemId}`)
  return response.data.cart
}

export const clearCart = async (): Promise<Cart> => {
  const response = await api.delete('/cart')
  return response.data.cart
}
