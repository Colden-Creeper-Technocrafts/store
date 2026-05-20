<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const menuItems = [
  {
    title: 'Dashboard',
    icon: '📊',
    to: '/backstore/dashboard',
    children: []
  },
  {
    title: 'Orders',
    icon: '🛒',
    children: [
      { title: 'All Orders', to: '/orders' },
      { title: 'Fulfillment', to: '/orders/fulfillment' },
      { title: 'Returns', to: '/orders/returns' }
    ]
  },
  {
    title: 'Customers',
    icon: '👥',
    children: [
      { title: 'Customer list', to: '/customers' },
      { title: 'Segmentation', to: '/customers/segments' },
      { title: 'Loyalty', to: '/customers/loyalty' }
    ]
  },
  {
    title: 'Products',
    icon: '📦',
    children: [
      { title: 'Catalog', to: '/products' },
      { title: 'Inventory', to: '/products/inventory' },
      { title: 'Pricing', to: '/products/pricing' }
    ]
  },
  {
    title: 'Marketing',
    icon: '🚀',
    children: [
      { title: 'Campaigns', to: '/marketing/campaigns' },
      { title: 'Discounts', to: '/marketing/discounts' }
    ]
  },
  {
    title: 'Settings',
    icon: '⚙️',
    children: [
      { title: 'Store settings', to: '/settings/store' },
      { title: 'Account', to: '/settings/account' }
    ]
  }
]

const expanded = ref<string[]>(['Dashboard', 'Orders', 'Customers'])

const toggleSection = (title: string) => {
  if (expanded.value.includes(title)) {
    expanded.value = expanded.value.filter((item) => item !== title)
  } else {
    expanded.value = [...expanded.value, title]
  }
}

const logout = () => {
  authStore.logout()
  router.push('/backstore/login')
}
</script>

<template>
  <div class="min-h-screen bg-slate-50">
    <header class="sticky top-0 z-50 border-b border-slate-200 bg-white">
      <div class="flex items-center justify-between px-6 py-4">
        <div>
          <h1 class="text-xl font-bold text-slate-900">Admin Panel</h1>
        </div>
        <button
          @click="logout"
          class="rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-900 transition hover:bg-slate-50"
        >
          Logout
        </button>
      </div>
    </header>

    <div class="grid min-h-[calc(100vh-4rem)] gap-6 xl:grid-cols-[280px_1fr]">
      <aside class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm m-4">
        <div class="mb-8">
          <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Menu</p>
          <h2 class="mt-3 text-2xl font-semibold text-slate-900">Navigation</h2>
        </div>

        <nav class="space-y-3">
          <div
            v-for="item in menuItems"
            :key="item.title"
            class="rounded-3xl border border-slate-200 bg-slate-50 p-4"
          >
            <button
              type="button"
              class="flex w-full items-center justify-between gap-3 text-left text-slate-700 transition hover:text-slate-900"
              @click="item.children.length ? toggleSection(item.title) : null"
            >
              <span class="flex items-center gap-3">
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-slate-900 text-sm text-white">{{ item.icon }}</span>
                <span class="font-medium">{{ item.title }}</span>
              </span>
              <span v-if="item.children.length" class="text-slate-400">{{ expanded.includes(item.title) ? '−' : '+' }}</span>
            </button>

            <transition name="fade">
              <div v-if="item.children.length && expanded.includes(item.title)" class="mt-3 space-y-2">
                <a
                  v-for="child in item.children"
                  :key="child.title"
                  :href="child.to"
                  class="block rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-600 transition hover:border-slate-300 hover:bg-slate-100 hover:text-slate-900"
                >
                  {{ child.title }}
                </a>
              </div>
            </transition>
          </div>
        </nav>
      </aside>

      <main class="space-y-8 pr-4 pb-8">
        <slot />
      </main>
    </div>
  </div>
</template>
