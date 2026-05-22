<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const normalizedRole = computed(() => authStore.role?.toLowerCase() ?? '')
const isLoggedIn = computed(() => !!authStore.token)
const isCustomer = computed(() => normalizedRole.value === 'customer')
const isAdmin = computed(() => normalizedRole.value === 'admin')
const displayName = computed(() => authStore.user?.name || 'Customer')

const logout = () => {
  authStore.logout()
  router.push('/')
}
</script>

<template>
  <div class="min-h-screen bg-slate-50 text-slate-900">
    <header class="border-b border-slate-200 bg-white">
      <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6">
        <router-link to="/" class="text-xl font-semibold text-slate-900">Front Store</router-link>

        <div class="flex items-center gap-3">
          <template v-if="isCustomer">
            <span class="hidden text-sm font-medium text-slate-700 sm:inline">Hi, {{ displayName }}</span>
            <router-link
              to="/profile"
              class="rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-900 transition hover:bg-slate-50"
            >
              Profile
            </router-link>
            <button
              type="button"
              @click="logout"
              class="rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-900 transition hover:bg-slate-50"
            >
              Logout
            </button>
          </template>

          <template v-else-if="!isLoggedIn">
            <router-link
              to="/login"
              class="rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-900 transition hover:bg-slate-50"
            >
              Login
            </router-link>
            <router-link
              to="/register"
              class="rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-700"
            >
              Register
            </router-link>
          </template>

          <template v-else-if="isAdmin">
            <router-link
              to="/backstore/dashboard"
              class="rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-900 transition hover:bg-slate-50"
            >
              Dashboard
            </router-link>
            <button
              type="button"
              @click="logout"
              class="rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-900 transition hover:bg-slate-50"
            >
              Logout
            </button>
          </template>
        </div>
      </div>
    </header>

    <main class="px-4 py-10 sm:px-6 lg:px-8">
      <div class="mx-auto max-w-7xl">
        <slot />
      </div>
    </main>
  </div>
</template>
