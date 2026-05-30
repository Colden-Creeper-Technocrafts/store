<script setup lang="ts">
import { computed, onMounted } from 'vue'
import CategoryTreeSidebar from '../../components/store/CategoryTreeSidebar.vue'
import { loadStoreCategories, useStorefront } from '../../services/storefront'

const { categories, categoriesLoaded } = useStorefront()
const loading = computed(() => !categoriesLoaded.value)

const products = [
  {
    id: 1,
    name: 'Velvet Evening Clutch',
    tag: 'Handbags',
    price: '$74',
    image: 'https://images.unsplash.com/photo-1566150905458-1bf1fc113f0d?auto=format&fit=crop&w=900&q=80'
  },
  {
    id: 2,
    name: 'Satin Scrunchie Pack',
    tag: 'Hair Accessories',
    price: '$18',
    image: 'https://images.unsplash.com/photo-1522335789203-aabd1fc54bc9?auto=format&fit=crop&w=900&q=80'
  },
  {
    id: 3,
    name: 'Rose Gold Hair Pin Set',
    tag: 'Hair Pins',
    price: '$26',
    image: 'https://images.unsplash.com/photo-1517841905240-472988babdf9?auto=format&fit=crop&w=900&q=80'
  },
  {
    id: 4,
    name: 'Kids Sparkle Bangle Duo',
    tag: 'Bangles',
    price: '$22',
    image: 'https://images.unsplash.com/photo-1514996937319-344454492b37?auto=format&fit=crop&w=900&q=80'
  },
  {
    id: 5,
    name: 'Cosmetics Gift Kit',
    tag: 'Beauty',
    price: '$58',
    image: 'https://images.unsplash.com/photo-1526040652367-ac003a0475fe?auto=format&fit=crop&w=900&q=80'
  },
  {
    id: 6,
    name: 'Festive Gift Articles',
    tag: 'Gifts',
    price: '$34',
    image: 'https://images.unsplash.com/photo-1495121605193-b116b5b9c5d7?auto=format&fit=crop&w=900&q=80'
  }
]

onMounted(() => {
  loadStoreCategories()
})
</script>

<template>
  <section class="grid gap-4 lg:grid-cols-[300px_1fr]">
    <CategoryTreeSidebar
      title="Ladies Categories"
      :categories="categories"
      :loading="loading"
      tone="ladies"
    />

    <div class="space-y-6">
      <div class="border border-stone-200 bg-white p-8">
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-stone-500">Ladies Collection</p>
        <h1 class="mt-3 text-3xl text-stone-900">Store</h1>
        <p class="mt-3 text-stone-600">Browse handbags, cosmetics, hair accessories, bangles, kids accessories, and gift articles.</p>
      </div>

      <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
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
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-stone-500">{{ product.tag }}</p>
            <h2 class="mt-3 text-xl font-semibold text-stone-900">{{ product.name }}</h2>
            <div class="mt-4 flex items-center justify-between">
              <span class="text-lg font-semibold text-stone-900">{{ product.price }}</span>
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
