<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useCartStore } from '../stores/cart'
import SocialIcon from '../components/SocialIcon.vue'
import { useStorefront } from '../services/storefront'

const router = useRouter()
const authStore = useAuthStore()
const cartStore = useCartStore()
const { storeName, storeLogo, storeTagline, socialLinks } = useStorefront()

const normalizedRole = computed(() => authStore.role?.toLowerCase() ?? '')
const isLoggedIn = computed(() => !!authStore.token)
const isCustomer = computed(() => normalizedRole.value === 'customer')
const isAdmin = computed(() => normalizedRole.value === 'admin')
const displayName = computed(() => authStore.user?.name || 'Customer')

const mobileOpen = ref(false)
const toggleMobile = () => { mobileOpen.value = !mobileOpen.value }
const closeMobile = () => { mobileOpen.value = false }

const currentYear = new Date().getFullYear()

const logout = () => {
  authStore.logout()
  cartStore.reset()
  closeMobile()
  router.push('/')
}

onMounted(() => {
  if (isCustomer.value) cartStore.load()
})

watch(isCustomer, (val) => {
  if (val) cartStore.load()
  else cartStore.reset()
})
</script>

<template>
  <div class="store-shell flex min-h-screen flex-col text-stone-900">
    <!-- Header -->
    <header class="sticky top-0 z-50 border-b border-stone-100 bg-white/95 shadow-sm backdrop-blur-xl">
      <div class="mx-auto max-w-7xl px-4 sm:px-6">
        <div class="flex items-center justify-between py-3">

        <!-- Brand -->
        <div class="flex items-center">
          <router-link to="/" class="flex items-center">
            <img
              v-if="storeLogo"
              :src="storeLogo"
              :alt="storeName"
              class="h-14 w-auto max-w-[220px] object-contain"
            />
            <span v-else class="brand-mark text-xl tracking-[0.08em] text-stone-900 sm:text-2xl">
              {{ storeName }}
            </span>
          </router-link>
        </div>

        <!-- Desktop nav -->
        <div class="hidden items-center justify-end gap-1 lg:flex">
          <router-link
            to="/store"
            class="rounded-md px-4 py-2 text-sm font-medium text-stone-600 transition hover:bg-amber-50 hover:text-amber-700"
          >
            Shop
          </router-link>

          <router-link
            to="/cart"
            class="relative rounded-md px-3 py-2 text-stone-500 transition hover:bg-amber-50 hover:text-amber-700"
            aria-label="Cart"
          >
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
              <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z" />
            </svg>
            <span
              v-if="cartStore.itemCount > 0"
              class="absolute -right-0.5 -top-0.5 flex h-4 w-4 items-center justify-center rounded-full bg-amber-500 text-[9px] font-bold text-white"
            >{{ cartStore.itemCount }}</span>
          </router-link>

          <template v-if="isCustomer">
            <span class="ml-1 text-xs text-stone-400">Hi, {{ displayName }}</span>
            <router-link to="/profile" class="rounded-md px-4 py-2 text-sm font-medium text-stone-600 transition hover:bg-amber-50 hover:text-amber-700">Profile</router-link>
            <button type="button" @click="logout" class="rounded-md px-4 py-2 text-sm font-medium text-stone-600 transition hover:bg-amber-50 hover:text-amber-700">Logout</button>
          </template>

          <template v-else-if="!isLoggedIn">
            <router-link to="/login" class="rounded-md px-4 py-2 text-sm font-medium text-stone-600 transition hover:bg-amber-50 hover:text-amber-700">Login</router-link>
            <router-link to="/register" class="ml-1 rounded-full bg-stone-900 px-5 py-2 text-sm font-semibold text-white transition hover:bg-stone-700">Register</router-link>
          </template>

          <template v-else-if="isAdmin">
            <router-link to="/backstore/dashboard" class="rounded-md px-4 py-2 text-sm font-medium text-stone-600 transition hover:bg-amber-50 hover:text-amber-700">Dashboard</router-link>
            <button type="button" @click="logout" class="rounded-md px-4 py-2 text-sm font-medium text-stone-600 transition hover:bg-amber-50 hover:text-amber-700">Logout</button>
          </template>
        </div>

        <!-- Mobile: cart + hamburger -->
        <div class="flex items-center justify-end gap-1 lg:hidden">
          <router-link
            to="/cart"
            class="relative rounded-md p-2 text-stone-500 transition hover:bg-amber-50 hover:text-amber-700"
            aria-label="Cart"
          >
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
              <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z" />
            </svg>
            <span
              v-if="cartStore.itemCount > 0"
              class="absolute -right-0.5 -top-0.5 flex h-4 w-4 items-center justify-center rounded-full bg-amber-500 text-[9px] font-bold text-white"
            >{{ cartStore.itemCount }}</span>
          </router-link>

          <button
            type="button"
            @click="toggleMobile"
            class="rounded-md p-2 text-stone-500 transition hover:bg-amber-50 hover:text-amber-700"
            :aria-expanded="mobileOpen"
            aria-label="Menu"
          >
            <svg v-if="!mobileOpen" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
              <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
            <svg v-else class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>
        </div>

        <!-- Tagline — centered below logo/nav row -->
        <div v-if="storeTagline" class="flex items-center justify-center gap-3 pb-2">
          <span class="h-px w-8 bg-gradient-to-r from-transparent to-amber-300"></span>
          <span class="text-[10px] font-medium uppercase tracking-[0.22em] text-stone-400">
            {{ storeTagline }}
          </span>
          <span class="h-px w-8 bg-gradient-to-l from-transparent to-amber-300"></span>
        </div>
      </div>

      <!-- Mobile dropdown -->
      <div v-if="mobileOpen" class="border-t border-stone-100 bg-white px-4 pb-4 lg:hidden">
        <nav class="mt-3 flex flex-col gap-1">
          <router-link to="/store" @click="closeMobile"
            class="rounded px-3 py-2.5 text-sm font-semibold text-stone-900 hover:bg-amber-50">
            Shop
          </router-link>

          <template v-if="isCustomer">
            <div class="px-3 py-2 text-xs text-stone-400">Signed in as {{ displayName }}</div>
            <router-link to="/profile" @click="closeMobile"
              class="rounded px-3 py-2.5 text-sm font-semibold text-stone-900 hover:bg-amber-50">Profile</router-link>
            <router-link to="/orders" @click="closeMobile"
              class="rounded px-3 py-2.5 text-sm font-semibold text-stone-900 hover:bg-amber-50">My Orders</router-link>
            <button type="button" @click="logout"
              class="rounded px-3 py-2.5 text-left text-sm font-semibold text-stone-900 hover:bg-amber-50">Logout</button>
          </template>

          <template v-else-if="!isLoggedIn">
            <router-link to="/login" @click="closeMobile"
              class="rounded px-3 py-2.5 text-sm font-semibold text-stone-900 hover:bg-amber-50">Login</router-link>
            <router-link to="/register" @click="closeMobile"
              class="rounded bg-stone-900 px-3 py-2.5 text-sm font-semibold text-white hover:bg-stone-700">Register</router-link>
          </template>

          <template v-else-if="isAdmin">
            <router-link to="/backstore/dashboard" @click="closeMobile"
              class="rounded px-3 py-2.5 text-sm font-semibold text-stone-900 hover:bg-amber-50">Admin Dashboard</router-link>
            <button type="button" @click="logout"
              class="rounded px-3 py-2.5 text-left text-sm font-semibold text-stone-900 hover:bg-amber-50">Logout</button>
          </template>
        </nav>
      </div>
    </header>

    <main class="relative flex-1 overflow-x-hidden">
      <div class="px-4 py-10 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
          <slot />
        </div>
      </div>
    </main>

    <footer class="border-t border-amber-100/70 bg-white/90">
      <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6">
        <div class="flex flex-col items-start justify-between gap-6 sm:flex-row sm:items-center">
          <p class="text-xs text-stone-400">&copy; {{ currentYear }} {{ storeName }}. All rights reserved.</p>
          <div v-if="socialLinks.length" class="grid grid-cols-3 gap-2">
            <a
              v-for="link in socialLinks"
              :key="link.id"
              :href="link.url"
              target="_blank"
              rel="noopener noreferrer"
              :title="link.name"
              class="flex h-9 w-9 items-center justify-center rounded-full bg-stone-100 text-stone-600 transition hover:bg-stone-200"
            >
              <SocialIcon :icon="link.icon" class="h-4 w-4" />
            </a>
          </div>
        </div>
      </div>
    </footer>
  </div>
</template>

<style scoped>
.store-shell {
  background:
    radial-gradient(circle at 8% 18%, rgba(253, 230, 138, 0.24), transparent 32%),
    radial-gradient(circle at 92% 5%, rgba(251, 191, 36, 0.12), transparent 30%),
    linear-gradient(180deg, #fffcf8 0%, #faf8f3 46%, #f7f3ed 100%);
}

.brand-mark {
  font-family: "Garamond", "Times New Roman", serif;
  font-weight: 700;
}
</style>
