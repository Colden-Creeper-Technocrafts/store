<script setup lang="ts">
import { computed, onMounted } from 'vue'
import CategoryTreeSidebar from '../../components/store/CategoryTreeSidebar.vue'
import { loadStoreCategories, useStorefront } from '../../services/storefront'

const { categories, categoriesLoaded } = useStorefront()
const loading = computed(() => !categoriesLoaded.value)

const products = [
  {
    id: 1,
    name: 'Organic Gala Apples',
    tag: 'Fresh Produce',
    price: '$4.99',
    image: 'https://images.unsplash.com/photo-1567306226416-28f0efdc88ce?auto=format&fit=crop&w=900&q=80'
  },
  {
    id: 2,
    name: 'Almond Milk 1L',
    tag: 'Dairy Alternative',
    price: '$3.45',
    image: 'https://images.unsplash.com/photo-1514996937319-344454492b37?auto=format&fit=crop&w=900&q=80'
  },
  {
    id: 3,
    name: 'Sourdough Bread',
    tag: 'Bakery',
    price: '$5.25',
    image: 'https://images.unsplash.com/photo-1511688878356-97321b7cf77a?auto=format&fit=crop&w=900&q=80'
  },
  {
    id: 4,
    name: 'Cold Brew Coffee',
    tag: 'Beverages',
    price: '$6.20',
    image: 'https://images.unsplash.com/photo-1511920170033-f8396924c348?auto=format&fit=crop&w=900&q=80'
  },
  {
    id: 5,
    name: 'Lavender Hand Soap',
    tag: 'Home Care',
    price: '$7.10',
    image: 'https://images.unsplash.com/photo-1580910051078-3c8c2e5cd7fa?auto=format&fit=crop&w=900&q=80'
  },
  {
    id: 6,
    name: 'Greek Yogurt',
    tag: 'Refrigerated',
    price: '$2.95',
    image: 'https://images.unsplash.com/photo-1524591664309-5b8b0f34d96a?auto=format&fit=crop&w=900&q=80'
  }
]

onMounted(() => {
  loadStoreCategories()
})
</script>

<template>
  <section class="grid gap-4 lg:grid-cols-[300px_1fr]">
    <CategoryTreeSidebar
      title="Grocery Categories"
      :categories="categories"
      :loading="loading"
      tone="grocery"
    />

    <div class="space-y-6">
      <div class="border border-emerald-200 bg-white p-8 text-emerald-950">
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Grocery Collection</p>
        <h1 class="mt-3 text-3xl">Store</h1>
        <p class="mt-3 text-emerald-800">Shop by aisle: produce, bakery, beverages, home care, and household essentials.</p>
      </div>

      <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <article
          v-for="product in products"
          :key="product.id"
          class="overflow-hidden rounded-3xl border border-emerald-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
        >
          <div
            class="h-40 bg-cover bg-center"
            :style="{ backgroundImage: `url(${product.image})` }"
          ></div>
          <div class="p-5">
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-emerald-600">{{ product.tag }}</p>
            <h2 class="mt-3 text-xl font-semibold text-emerald-950">{{ product.name }}</h2>
            <div class="mt-4 flex items-center justify-between">
              <span class="text-lg font-semibold text-emerald-950">{{ product.price }}</span>
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
