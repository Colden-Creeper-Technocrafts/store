<script setup lang="ts">
import { computed, watch } from 'vue'
import { useRoute, useRouter, RouterView } from 'vue-router'
import { useAuthStore } from './stores/auth'
import { useStorefront } from './services/storefront'
import LadiesStore from './layouts/LadiesStore.vue'
import GroceryStore from './layouts/GroceryStore.vue'
import AdminLayout from './layouts/AdminLayout.vue'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const { storeLayout, storeName, storeFavicon } = useStorefront()

watch(storeName, (name) => {
  if (name && name !== 'Store') document.title = name
}, { immediate: true })

watch(storeFavicon, (url) => {
  const link = document.querySelector<HTMLLinkElement>('link[rel="icon"]')
  if (link && url) link.href = url
}, { immediate: true })

const isLoggedIn = computed(() => !!authStore.token)
const isStorePage = computed(() => route.meta.layout === 'store')
const isAdminPage = computed(() => !!route.meta.requiresAdmin)
const activeStoreLayout = computed(() => (storeLayout.value === 'grocery' ? GroceryStore : LadiesStore))

const logout = () => {
  authStore.logout()
  router.push('/')
}
</script>

<template>
  <div class="bg-stone-800 py-1.5 text-center text-xs text-stone-300 tracking-wide">
    Beta version — currently available for friends &amp; family only.
  </div>

  <component v-if="isStorePage" :is="activeStoreLayout">
    <RouterView />
  </component>

  <component v-else-if="isAdminPage" :is="AdminLayout">
    <RouterView />
  </component>

  <div v-else class="min-h-screen bg-slate-50 text-slate-900">
    <header class="sticky top-0 z-50 border-b border-slate-200 bg-white/95 backdrop-blur-xl">
      <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6">
        <div class="flex items-center gap-4">
          <router-link to="/" class="text-xl font-bold tracking-tight text-slate-900">Store Analytics</router-link>
          <nav class="hidden items-center gap-3 md:flex">
            <router-link to="/" class="rounded-full px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100">Home</router-link>
            <router-link v-if="isLoggedIn" to="/backstore/dashboard" class="rounded-full px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100">Dashboard</router-link>
          </nav>
        </div>

        <div class="flex items-center gap-3">
          <router-link v-if="!isLoggedIn" to="/backstore/login" class="rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-900 transition hover:bg-slate-50">Login</router-link>
          <button v-if="isLoggedIn" @click="logout" class="rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-900 transition hover:bg-slate-50">Logout</button>
        </div>
      </div>
    </header>

    <main class="px-4 py-10 sm:px-6 lg:px-8">
      <div class="mx-auto max-w-7xl">
        <RouterView />
      </div>
    </main>
  </div>
</template>
