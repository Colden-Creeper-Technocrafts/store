<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const displayName = computed(() => authStore.user?.name || 'Customer')
const email = computed(() => authStore.user?.email || 'No email available')

const logout = () => {
  authStore.logout()
  router.push('/')
}
</script>

<template>
  <section class="mx-auto max-w-2xl rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
    <p class="text-sm uppercase tracking-[0.2em] text-slate-500">Customer Profile</p>
    <h1 class="mt-3 text-3xl font-semibold text-slate-900">{{ displayName }}</h1>
    <p class="mt-2 text-slate-600">{{ email }}</p>

    <div class="mt-8 grid gap-4 rounded-2xl border border-slate-200 bg-slate-50 p-5">
      <p class="text-sm text-slate-600">Role</p>
      <p class="text-lg font-medium text-slate-900">Customer</p>
    </div>

    <div class="mt-8 flex gap-3">
      <router-link
        to="/"
        class="rounded-full border border-slate-200 bg-white px-6 py-3 text-sm font-semibold text-slate-900 transition hover:bg-slate-50"
      >
        Back To Store
      </router-link>
      <button
        type="button"
        @click="logout"
        class="rounded-full bg-slate-900 px-6 py-3 text-sm font-semibold text-white transition hover:bg-slate-700"
      >
        Logout
      </button>
    </div>
  </section>
</template>
