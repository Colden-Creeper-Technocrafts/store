import { ref } from 'vue'
import api from './api'

export type StoreLayoutType = 'ladies' | 'grocery'
export type StoreCategoryNode = {
  id: number
  name: string
  slug: string
  description?: string | null
  children: StoreCategoryNode[]
}

const storeLayout = ref<StoreLayoutType>('ladies')
const storeName = ref('Store')
const isLoaded = ref(false)
const categories = ref<StoreCategoryNode[]>([])
const categoriesLoaded = ref(false)
let loadingPromise: Promise<void> | null = null
let categoriesLoadingPromise: Promise<void> | null = null

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
    } catch {
      storeLayout.value = 'ladies'
      storeName.value = 'Store'
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
    isLoaded,
    categories,
    categoriesLoaded
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
