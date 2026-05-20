<script setup lang="ts">
import { ref, computed } from 'vue'

// Business type templates with configurations
const businessTypes = [
  {
    id: 'jewelry',
    name: 'Jewelry Store',
    description: 'For jewelry, watches, and accessories',
    icon: '💎',
    colors: { primary: 'indigo', accent: 'amber' },
    categories: ['Rings', 'Necklaces', 'Bracelets', 'Earrings', 'Watches'],
    attributes: ['Material', 'Carat', 'Size', 'Color']
  },
  {
    id: 'supermarket',
    name: 'Supermarket',
    description: 'For grocery and general retail',
    icon: '🛒',
    colors: { primary: 'green', accent: 'emerald' },
    categories: ['Groceries', 'Dairy', 'Bakery', 'Meat', 'Produce', 'Frozen'],
    attributes: ['Weight', 'Expiry Date', 'Barcode', 'Organic']
  },
  {
    id: 'fashion',
    name: 'Fashion Boutique',
    description: 'For clothing and fashion items',
    icon: '👗',
    colors: { primary: 'rose', accent: 'pink' },
    categories: ['Men', 'Women', 'Kids', 'Accessories', 'Shoes'],
    attributes: ['Size', 'Color', 'Material', 'Style']
  },
  {
    id: 'electronics',
    name: 'Electronics Store',
    description: 'For gadgets and tech products',
    icon: '📱',
    colors: { primary: 'blue', accent: 'cyan' },
    categories: ['Phones', 'Laptops', 'Tablets', 'Accessories', 'Audio'],
    attributes: ['Brand', 'Warranty', 'Storage', 'RAM']
  }
]

// Store configuration form
const storeConfig = ref({
  businessType: '',
  storeName: '',
  storeDescription: '',
  storeEmail: '',
  storePhone: '',
  currency: 'USD',
  timezone: 'UTC',
  features: {
    reviews: true,
    wishlist: true,
    subscriptions: false,
    loyalty: false
  }
})

const selectedType = computed(() => {
  return businessTypes.find(t => t.id === storeConfig.value.businessType)
})

const isFormValid = computed(() => {
  return storeConfig.value.businessType &&
    storeConfig.value.storeName &&
    storeConfig.value.storeEmail
})

const submitConfiguration = () => {
  if (isFormValid.value) {
    // Save to localStorage or API
    localStorage.setItem('storeConfig', JSON.stringify(storeConfig.value))
    alert('Store configured successfully!')
  }
}
</script>

<template>
  <div class="space-y-8">
    <!-- Header -->
    <section class="rounded-3xl bg-white p-8 shadow-sm border border-slate-200">
      <h1 class="text-4xl font-semibold text-slate-900">Store Configuration</h1>
      <p class="mt-3 text-slate-600">Set up your store to match your business type and preferences</p>
    </section>

    <!-- Business Type Selection -->
    <section class="rounded-3xl bg-white p-8 shadow-sm border border-slate-200">
      <h2 class="text-2xl font-semibold text-slate-900 mb-6">Select Your Business Type</h2>
      <div class="grid gap-4 sm:grid-cols-2">
        <button
          v-for="type in businessTypes"
          :key="type.id"
          @click="storeConfig.businessType = type.id"
          :class="[
            'rounded-3xl border-2 p-6 text-left transition',
            storeConfig.businessType === type.id
              ? 'border-slate-900 bg-slate-50'
              : 'border-slate-200 bg-white hover:border-slate-300'
          ]"
        >
          <div class="text-4xl mb-3">{{ type.icon }}</div>
          <h3 class="font-semibold text-slate-900">{{ type.name }}</h3>
          <p class="mt-2 text-sm text-slate-600">{{ type.description }}</p>
        </button>
      </div>
    </section>

    <!-- Business Type Details -->
    <section v-if="selectedType" class="rounded-3xl bg-white p-8 shadow-sm border border-slate-200">
      <h3 class="text-xl font-semibold text-slate-900 mb-4">{{ selectedType.name }} - Quick Setup</h3>
      <div class="grid gap-4 sm:grid-cols-2">
        <div>
          <p class="font-semibold text-slate-900 mb-2">Default Categories:</p>
          <div class="flex flex-wrap gap-2">
            <span
              v-for="category in selectedType.categories"
              :key="category"
              class="rounded-full bg-slate-100 px-3 py-1 text-sm text-slate-700"
            >
              {{ category }}
            </span>
          </div>
        </div>
        <div>
          <p class="font-semibold text-slate-900 mb-2">Product Attributes:</p>
          <div class="flex flex-wrap gap-2">
            <span
              v-for="attr in selectedType.attributes"
              :key="attr"
              class="rounded-full bg-emerald-100 px-3 py-1 text-sm text-emerald-700"
            >
              {{ attr }}
            </span>
          </div>
        </div>
      </div>
    </section>

    <!-- Store Details Form -->
    <section class="rounded-3xl bg-white p-8 shadow-sm border border-slate-200">
      <h2 class="text-2xl font-semibold text-slate-900 mb-6">Store Information</h2>
      <form @submit.prevent="submitConfiguration" class="space-y-6">
        <div class="grid gap-6 sm:grid-cols-2">
          <div>
            <label class="block text-sm font-semibold text-slate-900 mb-2">Store Name *</label>
            <input
              v-model="storeConfig.storeName"
              type="text"
              placeholder="Enter your store name"
              class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 focus:outline-none focus:ring-2 focus:ring-slate-900"
            >
          </div>
          <div>
            <label class="block text-sm font-semibold text-slate-900 mb-2">Email Address *</label>
            <input
              v-model="storeConfig.storeEmail"
              type="email"
              placeholder="store@example.com"
              class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 focus:outline-none focus:ring-2 focus:ring-slate-900"
            >
          </div>
        </div>

        <div class="grid gap-6 sm:grid-cols-2">
          <div>
            <label class="block text-sm font-semibold text-slate-900 mb-2">Phone Number</label>
            <input
              v-model="storeConfig.storePhone"
              type="tel"
              placeholder="+1 (555) 000-0000"
              class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 focus:outline-none focus:ring-2 focus:ring-slate-900"
            >
          </div>
          <div>
            <label class="block text-sm font-semibold text-slate-900 mb-2">Currency</label>
            <select
              v-model="storeConfig.currency"
              class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 focus:outline-none focus:ring-2 focus:ring-slate-900"
            >
              <option value="USD">USD ($)</option>
              <option value="EUR">EUR (€)</option>
              <option value="GBP">GBP (£)</option>
              <option value="INR">INR (₹)</option>
              <option value="JPY">JPY (¥)</option>
            </select>
          </div>
        </div>

        <div>
          <label class="block text-sm font-semibold text-slate-900 mb-2">Store Description</label>
          <textarea
            v-model="storeConfig.storeDescription"
            placeholder="Tell customers about your store..."
            rows="4"
            class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 focus:outline-none focus:ring-2 focus:ring-slate-900"
          ></textarea>
        </div>
      </form>
    </section>

    <!-- Features Configuration -->
    <section class="rounded-3xl bg-white p-8 shadow-sm border border-slate-200">
      <h2 class="text-2xl font-semibold text-slate-900 mb-6">Store Features</h2>
      <div class="grid gap-4">
        <label class="flex items-center gap-4 rounded-2xl border border-slate-200 p-4 cursor-pointer hover:bg-slate-50">
          <input
            v-model="storeConfig.features.reviews"
            type="checkbox"
            class="h-5 w-5 rounded border-slate-300"
          >
          <div>
            <p class="font-semibold text-slate-900">Product Reviews</p>
            <p class="text-sm text-slate-600">Allow customers to review and rate products</p>
          </div>
        </label>

        <label class="flex items-center gap-4 rounded-2xl border border-slate-200 p-4 cursor-pointer hover:bg-slate-50">
          <input
            v-model="storeConfig.features.wishlist"
            type="checkbox"
            class="h-5 w-5 rounded border-slate-300"
          >
          <div>
            <p class="font-semibold text-slate-900">Wishlist</p>
            <p class="text-sm text-slate-600">Let customers save items for later</p>
          </div>
        </label>

        <label class="flex items-center gap-4 rounded-2xl border border-slate-200 p-4 cursor-pointer hover:bg-slate-50">
          <input
            v-model="storeConfig.features.subscriptions"
            type="checkbox"
            class="h-5 w-5 rounded border-slate-300"
          >
          <div>
            <p class="font-semibold text-slate-900">Subscriptions</p>
            <p class="text-sm text-slate-600">Offer recurring subscription products</p>
          </div>
        </label>

        <label class="flex items-center gap-4 rounded-2xl border border-slate-200 p-4 cursor-pointer hover:bg-slate-50">
          <input
            v-model="storeConfig.features.loyalty"
            type="checkbox"
            class="h-5 w-5 rounded border-slate-300"
          >
          <div>
            <p class="font-semibold text-slate-900">Loyalty Program</p>
            <p class="text-sm text-slate-600">Reward repeat customers with points</p>
          </div>
        </label>
      </div>
    </section>

    <!-- Action Buttons -->
    <div class="flex flex-col gap-3 sm:flex-row sm:justify-between">
      <button class="rounded-full border border-slate-200 bg-white px-8 py-3 font-semibold text-slate-900 transition hover:bg-slate-50">
        Cancel
      </button>
      <button
        @click="submitConfiguration"
        :disabled="!isFormValid"
        class="rounded-full bg-slate-900 px-8 py-3 font-semibold text-white transition hover:bg-slate-700 disabled:opacity-50 disabled:cursor-not-allowed"
      >
        Save Configuration
      </button>
    </div>
  </div>
</template>
