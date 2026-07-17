<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()
const isLoggedIn = computed(() => !!authStore.token)

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
      { title: 'All Orders', to: '/backstore/orders' },
      { title: 'Fulfillment', to: '/backstore/fulfillment' },
      { title: 'Returns', to: '/backstore/returns' }
    ]
  },
  {
    title: 'Customers',
    icon: '👥',
    children: [
      { title: 'All Customers', to: '/backstore/customers' },
      { title: 'Active Carts', to: '/backstore/carts' },
    ]
  },
  {
    title: 'Products',
    icon: '📦',
    children: [
      { title: 'Catalog', to: '/products' },
      { title: 'Product Master', to: '/backstore/products' },
      { title: 'Categories', to: '/backstore/categories' },
      { title: 'Inventory', to: '/backstore/products' },
      { title: 'Pricing', to: '/backstore/products' }
    ]
  },
  {
    title: 'Marketing',
    icon: '🚀',
    children: [
      { title: 'Coupons', to: '/backstore/coupons' },
      { title: 'Campaigns', to: '/marketing/campaigns' },
      { title: 'Discounts', to: '/marketing/discounts' }
    ]
  },
  {
    title: 'Shipping',
    icon: '🚚',
    children: [
      { title: 'Providers', to: '/backstore/shipping' },
      { title: 'Zones & Rates', to: '/backstore/shipping' },
    ]
  },
  {
    title: 'Settings',
    icon: '⚙️',
    children: [
      { title: 'Store settings', to: '/backstore/settings' }
    ]
  }
]

const navigationOrder = ['Dashboard', 'Products', 'Orders', 'Customers', 'Marketing', 'Shipping', 'Settings']
const orderedMenuItems = computed(() =>
  [...menuItems].sort((first, second) => navigationOrder.indexOf(first.title) - navigationOrder.indexOf(second.title))
)

const expanded = ref<string[]>([])

const routeExpanded = computed(() =>
  orderedMenuItems.value
    .filter((item) => item.children.some((child) => child.to === route.path))
    .map((item) => item.title)
)

const syncRouteExpanded = () => {
  expanded.value = Array.from(new Set([...expanded.value, ...routeExpanded.value]))
}

const toggleSection = (title: string) => {
  if (expanded.value.includes(title)) {
    if (routeExpanded.value.includes(title)) {
      return
    }

    expanded.value = expanded.value.filter((item) => item !== title)
  } else {
    expanded.value = [...expanded.value, title]
  }
}

watch(() => route.path, syncRouteExpanded, { immediate: true })

const navigateMenuItem = (item: { title: string; to?: string; children: unknown[] }) => {
  if (item.to) {
    router.push(item.to)
    return
  }

  if (item.children.length) {
    toggleSection(item.title)
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
        <div v-if="isLoggedIn" class="flex items-center gap-3">
          <router-link
            to="/"
            class="cursor-pointer rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-700"
          >
            View storefront
          </router-link>
          <button
            @click="logout"
            class="cursor-pointer rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-900 transition hover:bg-slate-50"
          >
            Logout
          </button>
        </div>
      </div>
    </header>

    <div
      :class="[
        'min-h-[calc(100vh-4rem)] gap-6',
        isLoggedIn ? 'grid xl:grid-cols-[280px_1fr]' : 'block'
      ]"
    >
      <aside
        v-if="isLoggedIn"
        class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm m-4"
      >
        <div class="mb-8">
          <h2 class="text-2xl font-semibold text-slate-900">Navigation</h2>
        </div>

        <nav class="space-y-3">
          <div
            v-for="item in orderedMenuItems"
            :key="item.title"
            class="rounded-3xl border border-slate-200 bg-slate-50 p-4"
          >
            <button
              type="button"
              class="flex w-full cursor-pointer items-center justify-between gap-3 text-left text-slate-700 transition hover:text-slate-900"
              @click="navigateMenuItem(item)"
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
                  class="block cursor-pointer rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-600 transition hover:border-slate-300 hover:bg-slate-100 hover:text-slate-900"
                >
                  {{ child.title }}
                </a>
              </div>
            </transition>
          </div>
        </nav>
      </aside>

      <main :class="isLoggedIn ? 'space-y-8 pr-4 pb-8' : 'p-4'">
        <slot />
      </main>
    </div>
  </div>
</template>
