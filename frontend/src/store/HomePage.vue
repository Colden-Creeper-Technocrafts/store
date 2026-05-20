<script setup lang="ts">
import { ref } from 'vue'

const categories = [
  { id: 1, name: 'Electronics', icon: '🔌', color: 'from-blue-500 to-blue-600' },
  { id: 2, name: 'Fashion', icon: '👕', color: 'from-pink-500 to-rose-600' },
  { id: 3, name: 'Home & Garden', icon: '🏠', color: 'from-green-500 to-emerald-600' },
  { id: 4, name: 'Sports', icon: '⚽', color: 'from-orange-500 to-red-600' }
]

const featuredProducts = [
  { id: 1, name: 'Premium Headphones', price: '$199.99', image: '🎧', rating: 4.8, reviews: 342 },
  { id: 2, name: 'Smart Watch', price: '$299.99', image: '⌚', rating: 4.6, reviews: 218 },
  { id: 3, name: 'Wireless Charger', price: '$49.99', image: '🔋', rating: 4.9, reviews: 567 },
  { id: 4, name: 'Phone Stand', price: '$24.99', image: '📱', rating: 4.7, reviews: 189 },
  { id: 5, name: 'USB Hub', price: '$39.99', image: '🔗', rating: 4.5, reviews: 245 },
  { id: 6, name: 'Screen Protector', price: '$12.99', image: '🛡️', rating: 4.9, reviews: 456 }
]

const deals = [
  { id: 1, title: 'Summer Sale', discount: '50% OFF', description: 'Selected items' },
  { id: 2, title: 'Free Shipping', discount: 'Over $50', description: 'Nationwide delivery' },
  { id: 3, title: 'Bundle Deals', discount: 'Up to 40%', description: 'Buy more, save more' }
]
</script>

<template>
  <div class="space-y-12">
    <!-- Hero Section -->
    <section class="rounded-3xl bg-gradient-to-r from-slate-900 to-slate-800 px-8 py-16 text-white md:px-12 md:py-24">
      <div class="mx-auto grid max-w-6xl gap-8 lg:grid-cols-2 lg:items-center">
        <div>
          <p class="mb-4 text-sm uppercase tracking-widest text-slate-300">Welcome to our store</p>
          <h1 class="mb-6 text-5xl font-bold leading-tight md:text-6xl">Discover Amazing Products</h1>
          <p class="mb-8 max-w-lg text-lg text-slate-300">Shop the latest electronics, fashion, home goods, and more with unbeatable prices and fast shipping.</p>
          <div class="flex flex-col gap-3 sm:flex-row">
            <button class="rounded-full bg-white px-8 py-3 font-semibold text-slate-900 transition hover:bg-slate-100">Shop Now</button>
            <button class="rounded-full border-2 border-white px-8 py-3 font-semibold transition hover:bg-white hover:text-slate-900">Learn More</button>
          </div>
        </div>
        <div class="text-center text-7xl">📦</div>
      </div>
    </section>

    <!-- Category Section -->
    <section class="px-4 sm:px-6">
      <div class="mb-8">
        <h2 class="text-3xl font-bold text-slate-900">Shop by Category</h2>
        <p class="mt-2 text-slate-600">Browse our popular collections</p>
      </div>
      <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div
          v-for="category in categories"
          :key="category.id"
          class="group cursor-pointer rounded-3xl p-6 transition hover:shadow-lg"
          :class="`bg-gradient-to-br ${category.color}`"
        >
          <div class="text-5xl mb-4">{{ category.icon }}</div>
          <h3 class="font-semibold text-white">{{ category.name }}</h3>
          <p class="mt-2 text-sm text-white/80">Explore collection</p>
        </div>
      </div>
    </section>

    <!-- Featured Deals -->
    <section class="px-4 sm:px-6">
      <div class="mb-8">
        <h2 class="text-3xl font-bold text-slate-900">Limited Time Offers</h2>
      </div>
      <div class="grid gap-4 md:grid-cols-3">
        <div
          v-for="deal in deals"
          :key="deal.id"
          class="rounded-3xl border-2 border-slate-200 bg-white p-6 transition hover:border-slate-300"
        >
          <div class="mb-4 text-4xl">🎁</div>
          <h3 class="font-bold text-slate-900">{{ deal.title }}</h3>
          <p class="mt-2 text-3xl font-bold text-emerald-600">{{ deal.discount }}</p>
          <p class="mt-1 text-sm text-slate-600">{{ deal.description }}</p>
          <button class="mt-4 rounded-full bg-slate-900 px-6 py-2 text-sm font-semibold text-white transition hover:bg-slate-700">Shop Deal</button>
        </div>
      </div>
    </section>

    <!-- Featured Products -->
    <section class="px-4 sm:px-6">
      <div class="mb-8">
        <h2 class="text-3xl font-bold text-slate-900">Featured Products</h2>
        <p class="mt-2 text-slate-600">Our best-selling items</p>
      </div>
      <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        <div
          v-for="product in featuredProducts"
          :key="product.id"
          class="rounded-3xl border border-slate-200 bg-white p-6 transition hover:shadow-lg"
        >
          <div class="mb-4 flex h-40 items-center justify-center rounded-2xl bg-slate-50 text-6xl">{{ product.image }}</div>
          <h3 class="font-semibold text-slate-900">{{ product.name }}</h3>
          <div class="mt-3 flex items-center justify-between">
            <span class="text-2xl font-bold text-emerald-600">{{ product.price }}</span>
            <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-3 py-1 text-sm">
              <span>⭐</span>
              <span class="font-semibold text-slate-900">{{ product.rating }}</span>
            </span>
          </div>
          <p class="mt-2 text-xs text-slate-500">{{ product.reviews }} reviews</p>
          <button class="mt-4 w-full rounded-full bg-slate-900 py-2 font-semibold text-white transition hover:bg-slate-700">Add to Cart</button>
        </div>
      </div>
    </section>

    <!-- Newsletter -->
    <section class="mx-4 rounded-3xl bg-slate-100 px-8 py-12 sm:mx-6 md:px-16 md:py-16">
      <div class="mx-auto max-w-2xl text-center">
        <h2 class="text-3xl font-bold text-slate-900">Get Exclusive Deals</h2>
        <p class="mt-3 text-slate-600">Subscribe to our newsletter and save 10% on your first order</p>
        <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:gap-2">
          <input
            type="email"
            placeholder="Enter your email"
            class="flex-1 rounded-full border border-slate-300 bg-white px-6 py-3 focus:outline-none focus:ring-2 focus:ring-slate-900"
          >
          <button class="rounded-full bg-slate-900 px-8 py-3 font-semibold text-white transition hover:bg-slate-700">Subscribe</button>
        </div>
      </div>
    </section>

    <!-- Footer Info -->
    <section class="grid gap-8 px-4 py-8 sm:px-6 sm:grid-cols-3">
      <div class="text-center">
        <div class="text-4xl mb-3">🚚</div>
        <h3 class="font-semibold text-slate-900">Free Shipping</h3>
        <p class="mt-1 text-sm text-slate-600">On orders over $50</p>
      </div>
      <div class="text-center">
        <div class="text-4xl mb-3">🔄</div>
        <h3 class="font-semibold text-slate-900">Easy Returns</h3>
        <p class="mt-1 text-sm text-slate-600">30-day return policy</p>
      </div>
      <div class="text-center">
        <div class="text-4xl mb-3">🔒</div>
        <h3 class="font-semibold text-slate-900">Secure Payment</h3>
        <p class="mt-1 text-sm text-slate-600">100% secure checkout</p>
      </div>
    </section>
  </div>
</template>
