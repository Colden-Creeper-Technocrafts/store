<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import CategoryTreeSidebar from '../../components/store/CategoryTreeSidebar.vue'
import { loadStoreCategories, loadStoreProducts, productsStore, useStorefront, formatPrice } from '../../services/storefront'
import { useAuthStore } from '../../stores/auth'
import { useCartStore } from '../../stores/cart'
import type { StorefrontProduct } from '../../services/storefront'

const { categories, categoriesLoaded } = useStorefront()
const { products, productsLoaded } = productsStore()
const loading = computed(() => !categoriesLoaded.value)
const authStore = useAuthStore()
const cartStore = useCartStore()
const router = useRouter()
const addingId = ref<number | null>(null)
const addError = ref<string | null>(null)
const search = ref('')
const sort = ref<'az' | 'za' | 'price-asc' | 'price-desc'>('az')

const effectivePrice = (p: StorefrontProduct) => Number(p.sale_price ?? p.price ?? 0)

const filtered = computed(() => {
  let list = [...products.value]
  const q = search.value.trim().toLowerCase()
  if (q) list = list.filter(p => p.name.toLowerCase().includes(q) || (p.short_description ?? '').toLowerCase().includes(q))
  if (sort.value === 'az') list.sort((a, b) => a.name.localeCompare(b.name))
  else if (sort.value === 'za') list.sort((a, b) => b.name.localeCompare(a.name))
  else if (sort.value === 'price-asc') list.sort((a, b) => effectivePrice(a) - effectivePrice(b))
  else if (sort.value === 'price-desc') list.sort((a, b) => effectivePrice(b) - effectivePrice(a))
  return list
})

const loadProducts = async (categoryIds: number[] = []): Promise<void> => {
  await loadStoreProducts(categoryIds)
}

const handleAdd = async (product: StorefrontProduct) => {
  addError.value = null
  if (authStore.isCustomer) {
    addingId.value = product.id
    try {
      await cartStore.add(product.id, 1)
    } catch (e: any) {
      addError.value = e?.response?.data?.errors?.quantity?.[0]
        ?? e?.response?.data?.message
        ?? 'Could not add item.'
    } finally {
      addingId.value = null
    }
  } else {
    const stock = product.quantity
    if (stock !== null && stock !== undefined) {
      const currentQty = cartStore.items.find(i => i.product_id === product.id)?.quantity ?? 0
      if (stock <= 0) { addError.value = `"${product.name}" is out of stock.`; return }
      if (currentQty + 1 > stock) { addError.value = `Only ${stock} unit(s) of "${product.name}" available.`; return }
    }
    cartStore.addGuest({
      id: product.id,
      name: product.name,
      sku: null,
      price: Number(product.price ?? 0),
      sale_price: product.sale_price != null ? Number(product.sale_price) : null,
      image_url: product.image ?? '/images/product-placeholder.svg',
      stock: product.quantity ?? null,
    })
  }
}

onMounted(async () => {
  await loadStoreCategories()
  await loadStoreProducts()
})
</script>

<template>
  <section class="grid gap-4 lg:grid-cols-[300px_1fr]">
    <CategoryTreeSidebar
      title="Ladies Categories"
      :categories="categories"
      :loading="loading"
      tone="ladies"
      @selected-updated="loadProducts"
    />

    <div class="space-y-5">
      <!-- Page header -->
      <div class="border border-stone-200 bg-white p-8">
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-stone-500">Ladies Collection</p>
        <h1 class="mt-3 text-3xl text-stone-900" style="font-family: Garamond, 'Times New Roman', serif">Store</h1>
        <p class="mt-3 text-stone-600">Browse handbags, cosmetics, hair accessories, bangles, kids accessories, and gift articles.</p>
      </div>

      <!-- Search + sort toolbar -->
      <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
        <div class="relative flex-1">
          <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-stone-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
          </svg>
          <input
            v-model="search"
            type="search"
            placeholder="Search products…"
            class="w-full border border-stone-300 bg-white py-2.5 pl-9 pr-4 text-sm focus:outline-none focus:border-stone-500"
          />
        </div>
        <select
          v-model="sort"
          class="border border-stone-300 bg-white px-4 py-2.5 text-sm text-stone-700 focus:outline-none focus:border-stone-500"
        >
          <option value="az">Name A → Z</option>
          <option value="za">Name Z → A</option>
          <option value="price-asc">Price: Low → High</option>
          <option value="price-desc">Price: High → Low</option>
        </select>
      </div>

      <!-- Error banner -->
      <div v-if="addError" class="border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700 flex items-center justify-between">
        <span>{{ addError }}</span>
        <button @click="addError = null" class="ml-4 text-rose-400 hover:text-rose-700">✕</button>
      </div>

      <!-- Product grid -->
      <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <div v-if="!productsLoaded" class="col-span-full border border-stone-200 bg-white p-10 text-center text-stone-500">
          Loading products…
        </div>

        <div v-else-if="filtered.length === 0" class="col-span-full border border-stone-200 bg-white p-10 text-center text-stone-500">
          {{ search ? `No products match "${search}".` : 'No products found for this category.' }}
        </div>

        <template v-else>
          <article
            v-for="product in filtered"
            :key="product.id"
            class="group overflow-hidden border border-stone-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
          >
            <!-- Image — clickable -->
            <router-link :to="`/product/${product.slug}`" class="block">
              <div class="relative aspect-[3/4] overflow-hidden bg-stone-100">
                <img
                  :src="product.image ?? '/images/product-placeholder.svg'"
                  :alt="product.name"
                  class="h-full w-full object-cover transition group-hover:scale-105"
                />
                <!-- OOS overlay -->
                <div
                  v-if="product.quantity !== null && product.quantity !== undefined && product.quantity === 0"
                  class="absolute inset-0 flex items-center justify-center bg-white/60"
                >
                  <span class="rounded-full bg-stone-800 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-white">Out of Stock</span>
                </div>
                <!-- Sale badge -->
                <span
                  v-if="product.sale_price != null"
                  class="absolute left-3 top-3 rounded-full bg-rose-500 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white"
                >Sale</span>
              </div>
            </router-link>

            <div class="p-5">
              <p class="text-xs font-semibold uppercase tracking-[0.16em] text-stone-400">{{ product.category_name }}</p>
              <router-link :to="`/product/${product.slug}`" class="mt-2 block">
                <h2 class="text-lg font-semibold text-stone-900 transition group-hover:text-amber-800 leading-snug">{{ product.name }}</h2>
              </router-link>
              <p v-if="product.short_description" class="mt-1.5 text-sm text-stone-500 line-clamp-2">{{ product.short_description }}</p>

              <!-- Price row -->
              <div class="mt-4 flex items-center justify-between gap-2">
                <div class="flex items-baseline gap-2">
                  <span class="text-lg font-semibold text-stone-900">
                    {{ formatPrice(product.sale_price ?? product.price) }}
                  </span>
                  <span v-if="product.sale_price != null" class="text-sm text-stone-400 line-through">
                    {{ formatPrice(product.price) }}
                  </span>
                </div>
                <button
                  @click="handleAdd(product)"
                  :disabled="addingId === product.id || (product.quantity !== null && product.quantity !== undefined && product.quantity === 0)"
                  class="shrink-0 border border-stone-300 bg-stone-50 px-4 py-2 text-xs font-semibold uppercase tracking-[0.1em] text-stone-700 transition hover:bg-stone-100 disabled:cursor-not-allowed disabled:opacity-40"
                >
                  {{ addingId === product.id ? '…' : (product.quantity !== null && product.quantity !== undefined && product.quantity === 0) ? 'OOS' : 'Add' }}
                </button>
              </div>
            </div>
          </article>
        </template>
      </section>
    </div>
  </section>
</template>
