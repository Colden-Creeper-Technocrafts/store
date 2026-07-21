import { ref } from 'vue'
import api from './api'

export type StoreLayoutType = 'ladies' | 'grocery'
export type StoreCategoryNode = {
  id: number
  name: string
  slug: string
  description?: string | null
  image?: string | null
  children: StoreCategoryNode[]
}

export type StorefrontCoupon = {
  code: string
  description: string | null
  discount_type: 'percentage' | 'fixed'
  discount_value: number
  min_order_amount: number | null
  starts_at: string | null
  expires_at: string | null
}

export type SocialLink = {
  id: number
  name: string
  url: string
  icon: string
}

const storeLayout = ref<StoreLayoutType>('ladies')
const storeName = ref('Store')
const socialLinks = ref<SocialLink[]>([])
const storeLogo = ref<string | null>(null)
const storeFavicon = ref<string | null>(null)
const storeTagline = ref<string | null>(null)
const storeBannerImage = ref<string | null>(null)
const storeBannerTitle = ref<string | null>(null)
const storeBannerText = ref<string | null>(null)
const storeCurrency = ref('INR')
const storeShippingCharge = ref(0)
const storeFreeShippingThreshold = ref<number | null>(null)
const isLoaded = ref(false)

const CURRENCY_SYMBOLS: Record<string, string> = {
  INR: '₹', USD: '$', EUR: '€', GBP: '£', JPY: '¥',
}

export const formatPrice = (amount: number | null | undefined): string => {
  const symbol = CURRENCY_SYMBOLS[storeCurrency.value] ?? storeCurrency.value
  return `${symbol}${Number(amount ?? 0).toFixed(2)}`
}
const categories = ref<StoreCategoryNode[]>([])
const categoriesLoaded = ref(false)
const liveCoupons = ref<StorefrontCoupon[]>([])
const upcomingCoupons = ref<StorefrontCoupon[]>([])
const couponsLoaded = ref(false)

export type StorefrontVariantImage = {
  id: number
  image_url: string
  is_primary: boolean
  sort_order: number
}

export type StorefrontVariant = {
  id: number
  sku?: string | null
  price: number
  sale_price?: number | null
  quantity: number
  options?: Record<string, string> | null
  is_default: boolean
  images?: StorefrontVariantImage[]
}

export type StorefrontProduct = {
  id: number
  name: string
  slug?: string | null
  sku?: string | null
  category_name?: string | null
  short_description?: string | null
  description?: string | null
  image?: string | null
  price?: number | null
  sale_price?: number | null
  quantity?: number | null
  weight?: number | null
  variants?: StorefrontVariant[]
}

const products = ref<StorefrontProduct[]>([])
const productsLoaded = ref(false)
let loadingPromise: Promise<void> | null = null
let categoriesLoadingPromise: Promise<void> | null = null
let productsLoadingPromise: Promise<void> | null = null
let couponsLoadingPromise: Promise<void> | null = null

const normalizeLayout = (layout: unknown): StoreLayoutType => {
  return String(layout ?? '').toLowerCase() === 'grocery' ? 'grocery' : 'ladies'
}

export const loadStorefront = async (force = false): Promise<void> => {
  if (!force && isLoaded.value) {
    return
  }

  if (loadingPromise) {
    return loadingPromise
  }

  loadingPromise = (async () => {
    try {
      const response = await api.get('/storefront')
      const store = response?.data?.store ?? {}

      storeLayout.value = normalizeLayout(store.layout)
      storeName.value = typeof store.name === 'string' && store.name.trim() ? store.name : 'Store'
      storeLogo.value    = typeof store.logo_url    === 'string' && store.logo_url.trim()    ? store.logo_url    : null
      storeFavicon.value = typeof store.favicon_url === 'string' && store.favicon_url.trim() ? store.favicon_url : null
      storeTagline.value = typeof store.tagline === 'string' && store.tagline.trim() ? store.tagline : null
      storeBannerImage.value = typeof store.banner_image === 'string' && store.banner_image.trim() ? store.banner_image : null
      storeBannerTitle.value = typeof store.banner_title === 'string' && store.banner_title.trim() ? store.banner_title : null
      storeBannerText.value = typeof store.banner_text === 'string' && store.banner_text.trim() ? store.banner_text : null
      storeCurrency.value = typeof store.currency === 'string' && store.currency.trim() ? store.currency.toUpperCase() : 'INR'
      storeShippingCharge.value = typeof store.shipping_charge === 'number' ? store.shipping_charge : 0
      storeFreeShippingThreshold.value = typeof store.free_shipping_threshold === 'number' ? store.free_shipping_threshold : null
      socialLinks.value = Array.isArray(response?.data?.social_links) ? response.data.social_links : []
    } catch {
      storeLayout.value = 'ladies'
      storeName.value = 'Store'
      storeLogo.value = null
      storeTagline.value = null
    } finally {
      isLoaded.value = true
      loadingPromise = null
    }
  })()

  return loadingPromise
}

export const useStorefront = () => {
  return {
    storeLayout,
    storeName,
    storeLogo,
    storeFavicon,
    storeTagline,
    storeBannerImage,
    storeBannerTitle,
    storeBannerText,
    storeShippingCharge,
    storeFreeShippingThreshold,
    isLoaded,
    categories,
    categoriesLoaded,
    liveCoupons,
    upcomingCoupons,
    couponsLoaded,
    socialLinks,
  }
}

export const loadStoreCategories = async (force = false): Promise<void> => {
  if (!force && categoriesLoaded.value) {
    return
  }

  if (categoriesLoadingPromise) {
    return categoriesLoadingPromise
  }

  categoriesLoadingPromise = (async () => {
    try {
      const response = await api.get('/storefront/categories')
      const payload = response?.data?.categories

      categories.value = Array.isArray(payload) ? payload : []
    } catch {
      categories.value = []
    } finally {
      categoriesLoaded.value = true
      categoriesLoadingPromise = null
    }
  })()

  return categoriesLoadingPromise
}

export const loadStoreCoupons = async (force = false): Promise<void> => {
  if (!force && couponsLoaded.value) {
    return
  }

  if (couponsLoadingPromise) {
    return couponsLoadingPromise
  }

  couponsLoadingPromise = (async () => {
    try {
      const response = await api.get('/storefront/coupons')
      liveCoupons.value = Array.isArray(response?.data?.live) ? response.data.live : []
      upcomingCoupons.value = Array.isArray(response?.data?.upcoming) ? response.data.upcoming : []
    } catch {
      liveCoupons.value = []
      upcomingCoupons.value = []
    } finally {
      couponsLoaded.value = true
      couponsLoadingPromise = null
    }
  })()

  return couponsLoadingPromise
}

export const productsStore = () => ({
  products,
  productsLoaded,
})

export const loadStoreProduct = async (slug: string): Promise<StorefrontProduct | null> => {
  try {
    const response = await api.get(`/storefront/products/${encodeURIComponent(slug)}`)
    return response?.data?.product ?? null
  } catch {
    return null
  }
}

export const loadStoreProducts = async (categoryIds: number[] = []): Promise<void> => {
  if (productsLoadingPromise) {
    return productsLoadingPromise
  }

  productsLoadingPromise = (async () => {
    try {
      const params: Record<string, unknown> = {}

      if (categoryIds.length) {
        params.category_ids = categoryIds
      }

      const response = await api.get('/storefront/products', { params })
      const payload = response?.data?.products

      products.value = Array.isArray(payload) ? payload : []
    } catch {
      products.value = []
    } finally {
      productsLoaded.value = true
      productsLoadingPromise = null
    }
  })()

  return productsLoadingPromise
}

export type PincodeCheckResult = {
  pincode: string
  serviceable: boolean
  message: string
}

export const checkPincode = async (pincode: string, state = ''): Promise<PincodeCheckResult> => {
  const res = await api.get('/shipping/check', { params: { pincode, state } })
  return res.data
}
