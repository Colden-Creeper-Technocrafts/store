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

const router = useRouter()

const {
  categories, categoriesLoaded,
  liveCoupons, upcomingCoupons, couponsLoaded,
  storeName,
  storeBannerImage, storeBannerTitle, storeBannerText,
} = useStorefront()
const { products, productsLoaded } = productsStore()

const newArrivals = computed(() =>
  [...products.value]
    .sort((a, b) => (b.id ?? 0) - (a.id ?? 0))
    .slice(0, 4)
)

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
  <div class="jewelry-theme space-y-12">

    <!-- Hero Banner — CSS full-bleed breakout from the constrained layout wrapper -->
    <section
      class="-mt-10 relative overflow-hidden"
      style="width: 100vw; margin-left: calc(50% - 50vw);"
    >
      <div
        v-if="storeBannerImage"
        class="absolute inset-0 bg-cover bg-center"
        :style="{ backgroundImage: `url(${storeBannerImage})` }"
      ></div>
      <div
        v-else
        class="absolute inset-0 bg-gradient-to-br from-stone-800 via-stone-700 to-amber-900"
      ></div>

      <div class="absolute inset-0 bg-black/40"></div>

      <div class="relative flex min-h-[460px] flex-col justify-center px-8 py-24 sm:min-h-[520px] sm:px-16 lg:px-24">
        <p class="mb-4 text-xs font-semibold uppercase tracking-[0.28em] text-amber-300">
          {{ storeName }}
        </p>
        <h1
          v-if="storeBannerTitle"
          class="max-w-2xl text-2xl font-light leading-tight text-white sm:text-3xl lg:text-4xl"
        >
          {{ storeBannerTitle }}
        </h1>
        <p
          v-if="storeBannerText"
          class="mt-5 max-w-lg text-base text-stone-200 sm:text-lg"
        >
          {{ storeBannerText }}
        </p>
        <div class="mt-8">
          <router-link
            to="/store"
            class="inline-block rounded-full bg-white px-8 py-3 text-sm font-semibold text-stone-900 shadow-lg transition hover:bg-amber-50"
          >
            Shop Now
          </router-link>
        </div>
      </div>
    </section>

    <!-- Category Grid -->
    <section id="collections" class="px-2 sm:px-4">
      <div class="mb-7 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <p class="text-xs font-semibold uppercase tracking-[0.2em] text-stone-500">Collections</p>
          <h2 class="mt-2 text-3xl text-stone-900 sm:text-4xl">Explore Signature Categories</h2>
        </div>
        <router-link
          to="/store"
          class="self-start rounded-full border border-stone-300 px-5 py-2 text-sm font-medium text-stone-700 transition hover:border-stone-400 hover:bg-stone-50"
        >
          View All Pieces
        </router-link>
      </div>

      <!-- Loading state -->
      <div v-if="!categoriesLoaded" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <div
          v-for="n in 6"
          :key="n"
          class="h-48 animate-pulse rounded-[1.6rem] bg-stone-200"
        ></div>
      </div>

      <!-- Empty state -->
      <div
        v-else-if="!categories.length"
        class="rounded-[1.6rem] border border-dashed border-stone-300 p-12 text-center text-sm text-stone-400"
      >
        No categories yet. Add them from the admin panel.
      </div>

      <!-- Category cards -->
      <div v-else class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <article
          v-for="cat in categories"
          :key="cat.id"
          class="flex flex-col gap-3 cursor-pointer"
          @click="router.push({ path: '/store', query: { category: cat.slug } })"
        >
          <div
            class="collection-card relative overflow-hidden rounded-[1.6rem] border border-stone-200"
            style="min-height: 400px;"
          >
            <div
              v-if="cat.image"
              class="absolute inset-0 bg-cover bg-center"
              :style="{ backgroundImage: `url(${cat.image})` }"
            ></div>
            <div v-else class="absolute inset-0 bg-gradient-to-br from-stone-700 to-stone-900"></div>
          </div>
          <h3 class="px-1 text-lg font-medium text-stone-800">{{ cat.name }}</h3>
        </article>
      </div>
    </section>

    <!-- New Arrivals -->
    <section id="new-arrivals" class="grid gap-5 px-2 sm:px-4 lg:grid-cols-[1.2fr_0.8fr]">
      <div class="rounded-[1.8rem] border border-stone-200 bg-white p-6 sm:p-8">
        <div class="mb-6">
          <p class="text-xs font-semibold uppercase tracking-[0.2em] text-stone-500">Just In</p>
          <h2 class="mt-2 text-3xl text-stone-900">New Arrivals</h2>
        </div>

        <!-- Loading -->
        <div v-if="!productsLoaded" class="grid gap-4 sm:grid-cols-2">
          <div v-for="n in 4" :key="n" class="h-48 animate-pulse rounded-3xl bg-stone-100"></div>
        </div>

        <!-- Empty -->
        <div
          v-else-if="!newArrivals.length"
          class="rounded-2xl border border-dashed border-stone-200 p-8 text-center text-sm text-stone-400"
        >
          No products yet.
        </div>

        <div v-else class="grid gap-4 sm:grid-cols-2">
          <article
            v-for="product in newArrivals"
            :key="product.id"
            class="rounded-3xl border border-stone-200 bg-stone-50/80 p-5 transition hover:-translate-y-1 hover:border-amber-300 hover:bg-white"
          >
            <div
              class="mb-4 aspect-[3/4] w-full rounded-2xl bg-cover bg-center bg-stone-200"
              :style="product.image ? { backgroundImage: `url(${product.image})` } : {}"
            ></div>
            <p v-if="product.category_name" class="text-xs font-semibold uppercase tracking-[0.16em] text-rose-500">
              {{ product.category_name }}
            </p>
            <h3 class="mt-2 text-xl text-stone-900">{{ product.name }}</h3>
            <p v-if="product.short_description" class="mt-1 text-sm text-stone-600">{{ product.short_description }}</p>
            <div class="mt-5 flex items-center justify-between">
              <p class="text-2xl text-stone-900">
                {{ product.sale_price != null ? formatPrice(product.sale_price) : product.price != null ? formatPrice(product.price) : '—' }}
              </p>
              <router-link
                :to="`/product/${product.slug ?? product.id}`"
                class="rounded-full border border-stone-300 px-4 py-2 text-xs font-semibold uppercase tracking-[0.1em] text-stone-700 transition hover:border-stone-500"
              >
                View
              </router-link>
            </div>
          </article>
        </div>
      </div>

      <!-- Why Choose Us -->
      <div class="rounded-[1.8rem] border border-stone-200 bg-gradient-to-b from-rose-50 to-amber-50 p-6 sm:p-8">
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-rose-600">Why Choose Us</p>
        <h3 class="mt-3 text-3xl leading-tight text-stone-900">Your One-Stop Accessories Destination</h3>
        <div class="mt-7 space-y-4">
          <article class="rounded-2xl border border-rose-100 bg-white/80 p-4">
            <h4 class="text-sm font-semibold uppercase tracking-[0.14em] text-stone-800">Curated Quality</h4>
            <p class="mt-2 text-sm text-stone-600">Handpicked designs with comfort, finish, and durability checks.</p>
          </article>
          <article class="rounded-2xl border border-rose-100 bg-white/80 p-4">
            <h4 class="text-sm font-semibold uppercase tracking-[0.14em] text-stone-800">Trendy + Traditional</h4>
            <p class="mt-2 text-sm text-stone-600">From modern clutches to classic bangles, all in one boutique.</p>
          </article>
          <article class="rounded-2xl border border-rose-100 bg-white/80 p-4">
            <h4 class="text-sm font-semibold uppercase tracking-[0.14em] text-stone-800">Gift Ready</h4>
            <p class="mt-2 text-sm text-stone-600">Ready-to-gift packaging available for all occasions and festivals.</p>
          </article>
        </div>
      </div>
    </section>

    <!-- Coupons -->
    <section id="coupons" class="px-2 sm:px-4">
      <div class="mb-7">
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-stone-500">Deals & Offers</p>
        <h2 class="mt-2 text-3xl text-stone-900 sm:text-4xl">Coupons</h2>
      </div>

      <div v-if="!couponsLoaded" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <div v-for="n in 3" :key="n" class="h-28 animate-pulse rounded-2xl bg-stone-200"></div>
      </div>

      <div v-else-if="!liveCoupons.length && !upcomingCoupons.length"
        class="rounded-2xl border border-dashed border-stone-300 p-10 text-center text-sm text-stone-400"
      >
        No active coupons at the moment. Check back soon!
      </div>

      <div v-else class="space-y-8">
        <!-- Live -->
        <div v-if="liveCoupons.length">
          <p class="mb-4 text-sm font-semibold uppercase tracking-[0.16em] text-emerald-600">Live Now</p>
          <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <div
              v-for="coupon in liveCoupons"
              :key="coupon.code"
              class="coupon-card relative overflow-hidden rounded-2xl border border-emerald-200 bg-white p-5"
            >
              <div class="flex items-start justify-between gap-3">
                <div>
                  <p class="text-lg font-bold tracking-widest text-stone-900">{{ coupon.code }}</p>
                  <p v-if="coupon.description" class="mt-1 text-sm text-stone-600">{{ coupon.description }}</p>
                </div>
                <span class="shrink-0 rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-700">
                  {{ formatDiscount(coupon.discount_type, coupon.discount_value) }}
                </span>
              </div>
              <div class="mt-3 flex flex-wrap gap-3 text-xs text-stone-400">
                <span v-if="coupon.min_order_amount">Min order {{ formatPrice(coupon.min_order_amount) }}</span>
                <span v-if="coupon.expires_at">Expires {{ formatDate(coupon.expires_at) }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Upcoming -->
        <div v-if="upcomingCoupons.length">
          <p class="mb-4 text-sm font-semibold uppercase tracking-[0.16em] text-amber-600">Coming Soon</p>
          <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <div
              v-for="coupon in upcomingCoupons"
              :key="coupon.code"
              class="relative overflow-hidden rounded-2xl border border-amber-200 bg-amber-50/60 p-5 opacity-80"
            >
              <div class="flex items-start justify-between gap-3">
                <div>
                  <p class="text-lg font-bold tracking-widest text-stone-700">{{ coupon.code }}</p>
                  <p v-if="coupon.description" class="mt-1 text-sm text-stone-500">{{ coupon.description }}</p>
                </div>
                <span class="shrink-0 rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-700">
                  {{ formatDiscount(coupon.discount_type, coupon.discount_value) }}
                </span>
              </div>
              <div class="mt-3 flex flex-wrap gap-3 text-xs text-stone-400">
                <span v-if="coupon.starts_at">Starts {{ formatDate(coupon.starts_at) }}</span>
                <span v-if="coupon.min_order_amount">Min order {{ formatPrice(coupon.min_order_amount) }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

  </div>
</template>

<style scoped>
.jewelry-theme {
  font-family: "Garamond", "Times New Roman", serif;
}

.collection-card {
  transition: transform 220ms ease, box-shadow 220ms ease;
}

.collection-overlay {
  background: linear-gradient(165deg, rgba(12, 10, 9, 0.18), rgba(12, 10, 9, 0.76));
}

.collection-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 18px 38px rgba(41, 37, 36, 0.1);
}

.coupon-card::before {
  content: '';
  position: absolute;
  left: -12px;
  top: 50%;
  transform: translateY(-50%);
  width: 24px;
  height: 24px;
  background: #f7f3ed;
  border-radius: 9999px;
  border: 1px solid #a7f3d0;
}

.coupon-card::after {
  content: '';
  position: absolute;
  right: -12px;
  top: 50%;
  transform: translateY(-50%);
  width: 24px;
  height: 24px;
  background: #f7f3ed;
  border-radius: 9999px;
  border: 1px solid #a7f3d0;
}
</style>
