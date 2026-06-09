<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useCartStore } from '../stores/cart'

const router = useRouter()
const authStore = useAuthStore()
const cartStore = useCartStore()

const displayName = computed(() => authStore.user?.name || 'Customer')
const email = computed(() => authStore.user?.email || '')
const initials = computed(() => displayName.value.split(' ').map(w => w[0]).slice(0, 2).join('').toUpperCase())

const logout = () => {
  authStore.logout()
  cartStore.reset()
  router.push('/')
}
</script>

<template>
  <div class="mx-auto max-w-2xl space-y-5">
    <!-- Header card -->
    <div class="border border-stone-200 bg-white p-8">
      <p class="text-xs font-semibold uppercase tracking-[0.22em] text-stone-400">My Account</p>
      <div class="mt-5 flex items-center gap-5">
        <div class="flex h-16 w-16 items-center justify-center rounded-full bg-amber-100 text-xl font-bold text-amber-800">
          {{ initials }}
        </div>
        <div>
          <h1 class="text-2xl text-stone-900" style="font-family: Garamond, 'Times New Roman', serif">{{ displayName }}</h1>
          <p class="mt-1 text-sm text-stone-500">{{ email }}</p>
        </div>
      </div>
    </div>

    <!-- Quick links -->
    <div class="grid gap-4 sm:grid-cols-2">
      <router-link
        to="/orders"
        class="group flex items-center justify-between border border-stone-200 bg-white p-6 transition hover:border-amber-300 hover:bg-amber-50"
      >
        <div>
          <p class="text-xs font-semibold uppercase tracking-[0.16em] text-stone-400">My Orders</p>
          <p class="mt-1 text-lg text-stone-900">View order history</p>
        </div>
        <span class="text-stone-300 transition group-hover:text-amber-500">→</span>
      </router-link>

      <router-link
        to="/store"
        class="group flex items-center justify-between border border-stone-200 bg-white p-6 transition hover:border-amber-300 hover:bg-amber-50"
      >
        <div>
          <p class="text-xs font-semibold uppercase tracking-[0.16em] text-stone-400">Continue Shopping</p>
          <p class="mt-1 text-lg text-stone-900">Browse collection</p>
        </div>
        <span class="text-stone-300 transition group-hover:text-amber-500">→</span>
      </router-link>
    </div>

    <!-- Account info -->
    <div class="border border-stone-200 bg-white p-6 space-y-4">
      <p class="text-xs font-semibold uppercase tracking-[0.16em] text-stone-400">Account Details</p>
      <div class="grid gap-4 sm:grid-cols-2">
        <div>
          <p class="text-xs text-stone-400">Name</p>
          <p class="mt-1 font-medium text-stone-900">{{ displayName }}</p>
        </div>
        <div>
          <p class="text-xs text-stone-400">Email</p>
          <p class="mt-1 font-medium text-stone-900">{{ email }}</p>
        </div>
        <div>
          <p class="text-xs text-stone-400">Account Type</p>
          <p class="mt-1 font-medium text-stone-900">Customer</p>
        </div>
      </div>
    </div>

    <!-- Actions -->
    <div class="flex gap-3">
      <button
        type="button"
        @click="logout"
        class="border border-stone-300 px-6 py-3 text-sm font-semibold text-stone-900 transition hover:bg-stone-50"
      >
        Sign Out
      </button>
    </div>
  </div>
</template>
