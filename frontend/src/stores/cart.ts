import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { addToCart, clearCart, fetchCart, removeCartItem, updateCartItem } from '../services/cart'
import type { Cart, CartItem, CartProduct } from '../services/cart'
import { productsStore, loadStoreProducts } from '../services/storefront'
import { useAuthStore } from './auth'

const LOCAL_CART_KEY = 'guest_cart'

type GuestItem = {
  product_id: number
  quantity: number
  product: CartProduct
}

function loadLocalCart(): GuestItem[] {
  try {
    return JSON.parse(localStorage.getItem(LOCAL_CART_KEY) ?? '[]')
  } catch {
    return []
  }
}

export const useCartStore = defineStore('cart', () => {
  const authStore = useAuthStore()

  const cart = ref<Cart | null>(null)
  const guestItems = ref<GuestItem[]>(loadLocalCart())
  const loading = ref(false)
  const loaded = ref(false)
  const error = ref<string | null>(null)

  const saveLocal = () => {
    localStorage.setItem(LOCAL_CART_KEY, JSON.stringify(guestItems.value))
  }

  const items = computed<CartItem[]>(() => {
    if (authStore.isCustomer) return cart.value?.items ?? []
    return guestItems.value.map(gi => ({
      id: gi.product_id,
      product_id: gi.product_id,
      quantity: gi.quantity,
      line_total: Number(((gi.product.sale_price ?? gi.product.price) * gi.quantity).toFixed(2)),
      product: gi.product,
    }))
  })

  const itemCount = computed(() => {
    if (authStore.isCustomer) return cart.value?.item_count ?? 0
    return guestItems.value.reduce((sum, i) => sum + i.quantity, 0)
  })

  const total = computed(() => {
    if (authStore.isCustomer) return cart.value?.total ?? 0
    return items.value.reduce((sum, i) => sum + i.line_total, 0)
  })

  const isEmpty = computed(() => items.value.length === 0)

  const guestOrderItems = computed(() =>
    guestItems.value.map(i => ({ product_id: i.product_id, quantity: i.quantity }))
  )

  const load = async () => {
    error.value = null
    if (!authStore.isCustomer) {
      // Always fetch fresh product data to get up-to-date stock from variant table
      await loadStoreProducts()
      const { products } = productsStore()
      guestItems.value.forEach(item => {
        const fresh = products.value.find(p => p.id === item.product_id)
        if (fresh) item.product.stock = fresh.quantity ?? null
      })
      saveLocal()
      loaded.value = true
      return
    }
    if (loading.value) return
    loading.value = true
    error.value = null
    try {
      cart.value = await fetchCart()
      loaded.value = true
    } catch {
      cart.value = null
    } finally {
      loading.value = false
    }
  }

  const add = async (productId: number, quantity = 1) => {
    loading.value = true
    error.value = null
    try {
      cart.value = await addToCart(productId, quantity)
    } catch (e: any) {
      error.value = e?.response?.data?.message ?? 'Could not add item.'
      throw e
    } finally {
      loading.value = false
    }
  }

  const addGuest = (product: CartProduct, quantity = 1) => {
    const existing = guestItems.value.find(i => i.product_id === product.id)
    if (existing) {
      existing.quantity = Math.min(existing.product.stock ?? 99, existing.quantity + quantity)
      existing.product = product
    } else {
      guestItems.value.push({ product_id: product.id, quantity, product })
    }
    saveLocal()
  }

  const update = async (itemId: number, quantity: number) => {
    if (!authStore.isCustomer) {
      error.value = null
      const item = guestItems.value.find(i => i.product_id === itemId)
      if (item) {
        const maxStock = item.product.stock
        if (maxStock !== null && maxStock !== undefined && quantity > maxStock) {
          error.value = `Only ${maxStock} unit(s) of "${item.product.name}" available.`
          throw new Error('stock_exceeded')
        }
        item.quantity = quantity
        saveLocal()
      }
      return
    }
    loading.value = true
    error.value = null
    try {
      cart.value = await updateCartItem(itemId, quantity)
    } catch (e: any) {
      error.value = e?.response?.data?.errors?.quantity?.[0]
        ?? e?.response?.data?.message
        ?? 'Could not update quantity.'
      throw e
    } finally {
      loading.value = false
    }
  }

  const remove = async (itemId: number) => {
    if (!authStore.isCustomer) {
      guestItems.value = guestItems.value.filter(i => i.product_id !== itemId)
      saveLocal()
      return
    }
    loading.value = true
    try {
      cart.value = await removeCartItem(itemId)
    } finally {
      loading.value = false
    }
  }

  const clear = async () => {
    if (!authStore.isCustomer) {
      guestItems.value = []
      saveLocal()
      return
    }
    loading.value = true
    try {
      cart.value = await clearCart()
    } finally {
      loading.value = false
    }
  }

  const reset = () => {
    cart.value = null
    loaded.value = false
    error.value = null
  }

  return {
    cart,
    loading,
    loaded,
    error,
    items,
    itemCount,
    total,
    isEmpty,
    guestOrderItems,
    load,
    add,
    addGuest,
    update,
    remove,
    clear,
    reset,
  }
})
