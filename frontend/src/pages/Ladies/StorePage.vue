<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import CategoryTreeSidebar from '../../components/store/CategoryTreeSidebar.vue'
import { loadStoreCategories, loadStoreProducts, productsStore, useStorefront } from '../../services/storefront'

const { categories, categoriesLoaded } = useStorefront()
const { products, productsLoaded } = productsStore()
const loading = computed(() => !categoriesLoaded.value)

const loadProducts = async (categoryIds: number[] = []): Promise<void> => {
  await loadStoreProducts(categoryIds)
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


      <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <div v-if="!productsLoaded" class="rounded-3xl border border-stone-200 bg-white p-10 text-center text-stone-700">
          Loading products...
        </div>

        <div v-else-if="products.length === 0" class="rounded-3xl border border-stone-200 bg-white p-10 text-center text-stone-700">
          No products found for this category.
        </div>

        <article
          v-else
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
              <button class="rounded-full border border-stone-300 bg-stone-50 px-4 py-2 text-xs font-semibold uppercase tracking-[0.1em] text-stone-700 transition hover:bg-stone-100">
                Add
              </button>
            </div>
          </div>
        </article>
      </section>
    </div>
  </section>
</template>
