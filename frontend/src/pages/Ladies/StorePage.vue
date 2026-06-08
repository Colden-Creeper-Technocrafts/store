<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import CategoryTreeSidebar from '../../components/store/CategoryTreeSidebar.vue'
import { loadStoreCategories, loadStoreProducts, productsStore, useStorefront } from '../../services/storefront'
import { useAuthStore } from '../../stores/auth'
import { useCartStore } from '../../stores/cart'
import type { StorefrontProduct } from '../../services/storefront'

const { categories, categoriesLoaded } = useStorefront()
const { products, productsLoaded } = productsStore()
const loading = computed(() => !categoriesLoaded.value)
const authStore = useAuthStore()
const cartStore = useCartStore()
const addingId = ref<number | null>(null)
const addError = ref<string | null>(null)

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
      if (stock <= 0) {
        addError.value = `"${product.name}" is out of stock.`
        return
      }
      if (currentQty + 1 > stock) {
        addError.value = `Only ${stock} unit(s) of "${product.name}" available.`
        return
      }
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

    <div class="space-y-6">
      <div class="border border-stone-200 bg-white p-8">
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-stone-500">Ladies Collection</p>
        <h1 class="mt-3 text-3xl text-stone-900">Store</h1>
        <p class="mt-3 text-stone-600">Browse handbags, cosmetics, hair accessories, bangles, kids accessories, and gift articles.</p>
      </div>


      <div v-if="addError" class="border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700 flex items-center justify-between">
        <span>{{ addError }}</span>
        <button @click="addError = null" class="ml-4 text-rose-400 hover:text-rose-700">✕</button>
      </div>

      <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <div v-if="!productsLoaded" class="rounded-3xl border border-stone-200 bg-white p-10 text-center text-stone-700">
          Loading products...
        </div>

        <div v-else-if="products.length === 0" class="rounded-3xl border border-stone-200 bg-white p-10 text-center text-stone-700">
          No products found for this category.
        </div>

        <template v-else>
          <article
            v-for="product in products"
            :key="product.id"
            class="overflow-hidden rounded-3xl border border-stone-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
          >
            <div
              class="h-40 bg-cover bg-center"
              :style="{ backgroundImage: `url(${product.image})` }"
            ></div>
            <div class="p-5">
              <p class="text-xs font-semibold uppercase tracking-[0.16em] text-stone-500">{{ product.category_name }}</p>
              <h2 class="mt-3 text-xl font-semibold text-stone-900">{{ product.name }}</h2>
              <p class="mt-2 text-sm text-stone-600">{{ product.short_description }}</p>
              <div class="mt-4 flex items-center justify-between">
                <span class="text-lg font-semibold text-stone-900">${{ product.sale_price ?? product.price }}</span>
                <button
                  @click="handleAdd(product)"
                  :disabled="addingId === product.id"
                  class="rounded-full border border-stone-300 bg-stone-50 px-4 py-2 text-xs font-semibold uppercase tracking-[0.1em] text-stone-700 transition hover:bg-stone-100 disabled:opacity-50"
                >
                  {{ addingId === product.id ? '…' : 'Add' }}
                </button>
              </div>
            </div>
          </article>
        </template>
      </section>
    </div>
  </section>
</template>
