<script setup lang="ts">
import { computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useCartStore } from '../stores/cart'
import { useStorefront } from '../services/storefront'

const router = useRouter()
const authStore = useAuthStore()
const cartStore = useCartStore()
const { storeName } = useStorefront()

const normalizedRole = computed(() => authStore.role?.toLowerCase() ?? '')
const isLoggedIn = computed(() => !!authStore.token)
const isCustomer = computed(() => normalizedRole.value === 'customer')
const isAdmin = computed(() => normalizedRole.value === 'admin')
const displayName = computed(() => authStore.user?.name || 'Customer')

const logout = () => {
  authStore.logout()
  cartStore.reset()
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
  <div class="store-shell min-h-screen text-emerald-950">
    <div class="bg-emerald-900 px-4 py-2 text-center text-[11px] font-medium tracking-[0.16em] text-emerald-100 uppercase sm:text-xs">
      Fresh Deals Daily On Grocery Essentials
    </div>

    <header class="sticky top-0 z-50 border-b border-emerald-200/80 bg-white/95 backdrop-blur-xl">
      <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6">
        <div class="flex items-center gap-4">
          <router-link to="/" class="brand-mark text-xl tracking-[0.06em] text-emerald-950 sm:text-2xl">
            {{ storeName }}
          </router-link>
          <span class="hidden border border-emerald-200 bg-emerald-50 px-3 py-1 text-[10px] font-semibold uppercase tracking-[0.14em] text-emerald-700 sm:inline-block">
            Farm Fresh
          </span>
        </div>

        <div class="flex items-center gap-3">
          <router-link
            to="/store"
            class="hidden border border-emerald-300 bg-white px-4 py-2 text-sm font-semibold text-emerald-900 transition hover:border-emerald-500 hover:bg-emerald-50 lg:inline-flex"
          >
            Go to Store
          </router-link>

          <router-link
            to="/cart"
            class="relative border border-emerald-300 bg-white px-4 py-2 text-sm font-semibold text-emerald-900 transition hover:border-emerald-500 hover:bg-emerald-50"
            aria-label="Cart"
            title="Cart"
          >
            &#128722;
            <span
              v-if="cartStore.itemCount > 0"
              class="absolute -right-1.5 -top-1.5 flex h-5 w-5 items-center justify-center rounded-full bg-emerald-700 text-[10px] font-bold text-white"
            >{{ cartStore.itemCount }}</span>
          </router-link>

          <template v-if="isCustomer">
            <span class="hidden text-sm font-medium text-emerald-800 sm:inline">Hi, {{ displayName }}</span>
            <router-link
              to="/profile"
              class="border border-emerald-300 bg-white px-4 py-2 text-sm font-semibold text-emerald-900 transition hover:border-emerald-500 hover:bg-emerald-50"
            >
              Profile
            </router-link>
            <button
              type="button"
              @click="logout"
              class="border border-emerald-300 bg-white px-4 py-2 text-sm font-semibold text-emerald-900 transition hover:border-emerald-500 hover:bg-emerald-50"
            >
              Logout
            </button>
          </template>

          <template v-else-if="!isLoggedIn">
            <router-link
              to="/login"
              class="border border-emerald-300 bg-white px-4 py-2 text-sm font-semibold text-emerald-900 transition hover:border-emerald-500 hover:bg-emerald-50"
            >
              Login
            </router-link>
            <router-link
              to="/register"
              class="bg-emerald-800 px-4 py-2 text-sm font-semibold text-white shadow-[0_8px_16px_rgba(6,78,59,0.24)] transition hover:bg-emerald-700"
            >
              Register
            </router-link>
          </template>

          <template v-else-if="isAdmin">
            <router-link
              to="/backstore/dashboard"
              class="border border-emerald-300 bg-white px-4 py-2 text-sm font-semibold text-emerald-900 transition hover:border-emerald-500 hover:bg-emerald-50"
            >
              Dashboard
            </router-link>
            <button
              type="button"
              @click="logout"
              class="border border-emerald-300 bg-white px-4 py-2 text-sm font-semibold text-emerald-900 transition hover:border-emerald-500 hover:bg-emerald-50"
            >
              Logout
            </button>
          </template>
        </div>
      </div>
    </header>

    <main class="relative px-4 py-10 sm:px-6 lg:px-8">
      <div class="pointer-events-none absolute inset-x-0 top-0 h-40 bg-gradient-to-b from-emerald-100/60 to-transparent"></div>
      <div class="mx-auto max-w-7xl">
        <slot />
      </div>
    </main>
  </div>
</template>

<style scoped>
.store-shell {
  background:
    radial-gradient(circle at 7% 20%, rgba(167, 243, 208, 0.36), transparent 31%),
    radial-gradient(circle at 90% 8%, rgba(134, 239, 172, 0.2), transparent 32%),
    linear-gradient(180deg, #f6fffa 0%, #eefbf5 45%, #e9f8f2 100%);
}

.brand-mark {
  font-family: "Georgia", "Times New Roman", serif;
  font-weight: 700;
}
</style>
