<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '../services/api'
import { useAuthStore } from '../stores/auth'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const name = ref('')
const email = ref('')
const password = ref('')
const error = ref('')
const loading = ref(false)

const isRegisterMode = computed(() => route.path === '/register')

const switchMode = () => {
  router.push(isRegisterMode.value ? '/login' : '/register')
}

const redirectByRole = (role: string) => {
  if ((role ?? '').toLowerCase() === 'admin') {
    router.push('/backstore/dashboard')
    return
  }
  router.push('/')
}

const submit = async () => {
  error.value = ''
  loading.value = true
  try {
    const payload = isRegisterMode.value
      ? { name: name.value, email: email.value, password: password.value }
      : { email: email.value, password: password.value }
    const endpoint = isRegisterMode.value ? '/register' : '/login'
    const response = await api.post(endpoint, payload)
    const { token, user, role } = response.data
    authStore.setAuth({ token, user, role })
    redirectByRole(role ?? user?.role)
  } catch (err: any) {
    error.value = err.response?.data?.message || 'Authentication failed'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="mx-auto max-w-md">
    <!-- Card -->
    <div class="border border-stone-200 bg-white p-8 sm:p-10">
      <p class="text-xs font-semibold uppercase tracking-[0.22em] text-stone-400">Ladies Collection</p>
      <h1 class="mt-3 text-3xl text-stone-900" style="font-family: Garamond, 'Times New Roman', serif">
        {{ isRegisterMode ? 'Create Account' : 'Welcome Back' }}
      </h1>
      <p class="mt-2 text-sm text-stone-500">
        {{ isRegisterMode ? 'Join to save your orders and wishlist.' : 'Sign in to your account.' }}
      </p>

      <div v-if="error" class="mt-6 border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700">
        {{ error }}
      </div>

      <form @submit.prevent="submit" class="mt-7 space-y-4">
        <div v-if="isRegisterMode">
          <label class="block text-sm font-medium text-stone-700">Full Name</label>
          <input
            v-model="name"
            type="text"
            required
            placeholder="Your name"
            class="mt-1 w-full border border-stone-300 px-3 py-2.5 text-sm focus:border-stone-600 focus:outline-none"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-stone-700">Email</label>
          <input
            v-model="email"
            type="email"
            required
            placeholder="you@example.com"
            class="mt-1 w-full border border-stone-300 px-3 py-2.5 text-sm focus:border-stone-600 focus:outline-none"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-stone-700">Password</label>
          <input
            v-model="password"
            type="password"
            required
            placeholder="••••••••"
            class="mt-1 w-full border border-stone-300 px-3 py-2.5 text-sm focus:border-stone-600 focus:outline-none"
          />
        </div>

        <button
          type="submit"
          :disabled="loading"
          class="w-full bg-stone-900 py-3 text-sm font-semibold text-white transition hover:bg-stone-700 disabled:cursor-not-allowed disabled:opacity-60"
        >
          {{ loading ? 'Please wait…' : isRegisterMode ? 'Create Account' : 'Sign In' }}
        </button>
      </form>

      <p class="mt-6 text-center text-sm text-stone-500">
        {{ isRegisterMode ? 'Already have an account?' : "Don't have an account?" }}
        <button
          type="button"
          @click="switchMode"
          class="ml-1 font-semibold text-stone-900 underline decoration-amber-400 underline-offset-2 hover:text-amber-800"
        >
          {{ isRegisterMode ? 'Sign In' : 'Register' }}
        </button>
      </p>
    </div>

    <!-- Decorative footer note -->
    <p class="mt-4 text-center text-xs text-stone-400">
      Your data is safe · Secured by Razorpay for payments
    </p>
  </div>
</template>
