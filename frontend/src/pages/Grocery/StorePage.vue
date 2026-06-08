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
      title="Grocery Categories"
      :categories="categories"
      :loading="loading"
      tone="grocery"
      @selected-updated="loadProducts"
    />

    <div class="space-y-6">
      <div class="border border-emerald-200 bg-white p-8 text-emerald-950">
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Grocery Collection</p>
        <h1 class="mt-3 text-3xl">Store</h1>
        <p class="mt-3 text-emerald-800">Shop by aisle: produce, bakery, beverages, home care, and household essentials.</p>
      </div>


      <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <div v-if="!productsLoaded" class="rounded-3xl border border-emerald-200 bg-white p-10 text-center text-emerald-700">
          Loading products...
        </div>

        <div v-else-if="products.length === 0" class="rounded-3xl border border-emerald-200 bg-white p-10 text-center text-emerald-700">
          No products found for this category.
        </div>

        <article
          v-else
          v-for="product in products"
          :key="product.id"
          class="overflow-hidden rounded-3xl border border-emerald-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
        >
          <div
            class="h-40 bg-cover bg-center"
            :style="{ backgroundImage: `url(${product.image})` }"
          ></div>
          <div class="p-5">
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-emerald-600">{{ product.category_name }}</p>
            <h2 class="mt-3 text-xl font-semibold text-emerald-950">{{ product.name }}</h2>
            <p class="mt-2 text-sm text-emerald-700">{{ product.short_description }}</p>
            <div class="mt-4 flex items-center justify-between">
              <span class="text-lg font-semibold text-emerald-950">${{ product.sale_price ?? product.price }}</span>
              <button class="rounded-full border border-emerald-300 bg-emerald-50 px-4 py-2 text-xs font-semibold uppercase tracking-[0.1em] text-emerald-900 transition hover:bg-emerald-100">
                Add
              </button>
            </div>
          </div>
        </article>
      </section>
    </div>
  </section>
</template>
