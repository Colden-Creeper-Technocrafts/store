<script setup lang="ts">
import { onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import {
  useStorefront,
  loadStoreCategories,
  loadStoreProducts,
  loadStoreCoupons,
  productsStore,
  formatPrice,
} from '../../services/storefront'
import { useAuthStore } from '../../stores/auth'
import { useCartStore } from '../../stores/cart'

const router    = useRouter()
const authStore = useAuthStore()
const cartStore = useCartStore()

const {
  categories, categoriesLoaded,
  liveCoupons, couponsLoaded,
  storeName,
  storeBannerImage, storeBannerTitle, storeBannerText,
} = useStorefront()

const { products, productsLoaded } = productsStore()

// Top-level categories only (no children), up to 6
const featuredCategories = computed(() =>
  categories.value.filter(c => c.image).slice(0, 6)
)

// Latest 6 products by id
const newProducts = computed(() =>
  [...products.value]
    .sort((a, b) => (b.id ?? 0) - (a.id ?? 0))
    .slice(0, 6)
)

function goToCategory(slug: string) {
  router.push({ path: '/store', query: { category: slug } })
}

function goToProduct(slug: string | null | undefined) {
  if (slug) router.push(`/product/${slug}`)
}

async function addToCart(product: typeof products.value[number]) {
  if (authStore.isCustomer) {
    await cartStore.add(product.id, 1)
  } else {
    cartStore.addGuest({
      id:        product.id,
      name:      product.name,
      sku:       product.sku ?? null,
      price:     Number(product.sale_price ?? product.price ?? 0),
      sale_price: product.sale_price != null ? Number(product.sale_price) : null,
      image_url: product.image ?? '/images/product-placeholder.svg',
      stock:     product.quantity ?? null,
    })
  }
}

const formatDiscount = (type: string, value: number) =>
  type === 'percentage' ? `${value}% OFF` : `${formatPrice(value)} OFF`

const formatDate = (iso: string | null) => {
  if (!iso) return null
  return new Date(iso).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' })
}

onMounted(async () => {
  await Promise.all([loadStoreCategories(), loadStoreProducts(), loadStoreCoupons()])
})
</script>

<template>
  <div class="space-y-8 text-emerald-950">

    <!-- Hero -->
    <section class="hero-grid grid gap-6 border border-emerald-300 bg-emerald-900 px-6 py-10 text-white lg:grid-cols-[1.2fr_0.8fr] lg:px-10">
      <div class="space-y-5">
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-200">
          {{ storeName || 'Fresh Grocery Delivered' }}
        </p>
        <h1 class="text-4xl leading-tight sm:text-5xl">
          {{ storeBannerTitle || 'Healthy Groceries For Everyday Living' }}
        </h1>
        <p class="max-w-2xl text-emerald-50/90">
          {{ storeBannerText || 'Shop vegetables, fruits, dairy, snacks, and home essentials in one place.' }}
        </p>
        <div class="flex flex-col gap-3 sm:flex-row">
          <router-link
            to="/store"
            class="inline-block border border-emerald-100 bg-emerald-100 px-6 py-3 text-sm font-semibold text-emerald-900 transition hover:bg-white"
          >
            Start Shopping
          </router-link>
        </div>
      </div>

      <div class="grid grid-cols-2 gap-3 self-start">
        <div
          class="col-span-2 min-h-[150px] border border-emerald-200/50 bg-cover bg-center"
          :style="storeBannerImage
            ? { backgroundImage: `linear-gradient(rgba(6,78,59,.35),rgba(6,78,59,.35)),url(${storeBannerImage})` }
            : { backgroundImage: `linear-gradient(rgba(6,78,59,.35),rgba(6,78,59,.35)),url('https://images.unsplash.com/photo-1543168256-418811576931?auto=format&fit=crop&w=1200&q=80')` }"
        ></div>
        <div class="border border-emerald-200/50 bg-emerald-800/80 p-4">
          <p class="text-xs uppercase tracking-[0.14em] text-emerald-200">Categories</p>
          <p class="mt-2 text-2xl">{{ categoriesLoaded ? categories.length : '…' }}</p>
        </div>
        <div class="border border-emerald-200/50 bg-emerald-800/80 p-4">
          <p class="text-xs uppercase tracking-[0.14em] text-emerald-200">Products</p>
          <p class="mt-2 text-2xl">{{ productsLoaded ? products.length : '…' }}</p>
        </div>
      </div>
    </section>

    <!-- Categories -->
    <section class="border border-emerald-200 bg-white p-6 sm:p-8">
      <div class="mb-6 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Shop By Category</p>
          <h2 class="mt-2 text-3xl text-emerald-950">Everything You Need, Daily</h2>
        </div>
        <router-link
          to="/store"
          class="self-start border border-emerald-300 bg-white px-5 py-2 text-sm font-semibold text-emerald-900 transition hover:border-emerald-500 hover:bg-emerald-50"
        >
          Browse All
        </router-link>
      </div>

      <!-- Loading skeletons -->
      <div v-if="!categoriesLoaded" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
        <div
          v-for="i in 6" :key="i"
          class="h-32 animate-pulse border border-emerald-100 bg-emerald-50"
        ></div>
      </div>

      <!-- Category cards -->
      <div v-else class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
        <article
          v-for="category in featuredCategories"
          :key="category.id"
          @click="goToCategory(category.slug)"
          class="relative cursor-pointer overflow-hidden border border-emerald-200 p-5 transition hover:border-emerald-400"
        >
          <div
            class="absolute inset-0 bg-cover bg-center"
            :style="{ backgroundImage: `url(${category.image})` }"
          ></div>
          <div class="category-overlay absolute inset-0"></div>
          <div class="relative z-10">
            <h3 class="mt-2 text-xl text-white">{{ category.name }}</h3>
            <p v-if="category.description" class="mt-2 text-sm text-emerald-50">{{ category.description }}</p>
          </div>
        </article>
      </div>
    </section>

    <!-- New Products + Coupons -->
    <section class="grid gap-4 lg:grid-cols-[1.1fr_0.9fr]">

      <!-- New products -->
      <div class="border border-emerald-200 bg-white p-6 sm:p-8">
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">New Arrivals</p>
        <h2 class="mt-2 text-3xl text-emerald-950">Just Added</h2>

        <!-- Loading skeletons -->
        <div v-if="!productsLoaded" class="mt-5 grid gap-3 sm:grid-cols-2">
          <div v-for="i in 6" :key="i" class="h-40 animate-pulse border border-emerald-100 bg-emerald-50"></div>
        </div>

        <p v-else-if="newProducts.length === 0" class="mt-4 text-sm text-emerald-600">
          No products yet.
        </p>

        <div v-else class="mt-5 grid gap-3 sm:grid-cols-2">
          <article
            v-for="product in newProducts"
            :key="product.id"
            class="border border-emerald-200 bg-emerald-50/50 p-4 transition hover:border-emerald-400 hover:bg-emerald-50"
          >
            <div
              @click="goToProduct(product.slug)"
              class="mb-3 h-28 cursor-pointer bg-cover bg-center bg-emerald-100"
              :style="product.image
                ? { backgroundImage: `linear-gradient(rgba(4,120,87,.15),rgba(4,120,87,.15)),url(${product.image})` }
                : {}"
            ></div>
            <p v-if="product.category_name" class="text-xs font-semibold uppercase tracking-[0.12em] text-emerald-700">
              {{ product.category_name }}
            </p>
            <h3
              @click="goToProduct(product.slug)"
              class="mt-2 cursor-pointer text-lg text-emerald-950 hover:text-emerald-700 line-clamp-1"
            >
              {{ product.name }}
            </h3>
            <div class="mt-4 flex items-center justify-between">
              <div>
                <p class="text-xl text-emerald-950">{{ formatPrice(product.sale_price ?? product.price ?? 0) }}</p>
                <p v-if="product.sale_price != null" class="text-xs text-emerald-500 line-through">
                  {{ formatPrice(product.price ?? 0) }}
                </p>
              </div>
              <button
                @click="addToCart(product)"
                :disabled="product.quantity === 0"
                class="border border-emerald-300 bg-white px-3 py-2 text-xs font-semibold uppercase tracking-[0.1em] text-emerald-900 transition hover:border-emerald-500 hover:bg-emerald-100 disabled:opacity-40"
              >
                Add
              </button>
            </div>
          </article>
        </div>
      </div>

      <!-- Coupons / Deals -->
      <div class="border border-emerald-200 bg-emerald-50 p-6 sm:p-8">
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Active Offers</p>
        <h2 class="mt-2 text-3xl text-emerald-950">Today's Deals</h2>

        <div v-if="!couponsLoaded" class="mt-6 space-y-3">
          <div v-for="i in 3" :key="i" class="h-16 animate-pulse border border-emerald-100 bg-white"></div>
        </div>

        <div v-else-if="liveCoupons.length === 0" class="mt-6">
          <p class="text-sm text-emerald-600">No active offers right now. Check back soon.</p>
        </div>

        <div v-else class="mt-6 space-y-3">
          <div
            v-for="coupon in liveCoupons"
            :key="coupon.code"
            class="border border-emerald-200 bg-white p-4"
          >
            <div class="flex items-start justify-between gap-2">
              <div>
                <p class="text-sm font-semibold text-emerald-900">{{ formatDiscount(coupon.discount_type, coupon.discount_value) }}</p>
                <p v-if="coupon.description" class="mt-1 text-sm text-emerald-800">{{ coupon.description }}</p>
                <p v-if="coupon.min_order_amount" class="mt-1 text-xs text-emerald-600">
                  Min. order {{ formatPrice(coupon.min_order_amount) }}
                </p>
              </div>
              <span class="shrink-0 rounded bg-emerald-100 px-2 py-1 text-xs font-mono font-semibold text-emerald-800">
                {{ coupon.code }}
              </span>
            </div>
            <p v-if="coupon.expires_at" class="mt-2 text-xs text-emerald-500">
              Expires {{ formatDate(coupon.expires_at) }}
            </p>
          </div>
        </div>
      </div>
    </section>

  </div>
</template>

<style scoped>
.hero-grid {
  background:
    linear-gradient(120deg, rgba(6, 95, 70, 0.8), rgba(4, 120, 87, 0.88)),
    radial-gradient(circle at 10% 15%, rgba(167, 243, 208, 0.35), transparent 36%);
}

.category-overlay {
  background: linear-gradient(160deg, rgba(6, 78, 59, 0.35), rgba(6, 78, 59, 0.82));
}

.line-clamp-1 {
  display: -webkit-box;
  -webkit-line-clamp: 1;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
