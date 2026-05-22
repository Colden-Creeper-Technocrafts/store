<script setup>
import { computed, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '../services/api'
import { useAuthStore } from '../stores/auth'

import AuthLayout from '../layouts/AuthLayout.vue'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const name = ref('')
const email = ref('')
const password = ref('')
const error = ref('')
const loading = ref(false)

const isRegisterMode = computed(() => route.path === '/register')

const pageTitle = computed(() => (isRegisterMode.value ? 'Create Your Account' : 'Welcome Back'))

const switchMode = () => {
  router.push(isRegisterMode.value ? '/login' : '/register')
}

const redirectByRole = (role) => {
  const normalizedRole = role?.toLowerCase() || ''

  if (normalizedRole === 'admin') {
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
  } catch (err) {
    error.value = err.response?.data?.message || 'Authentication failed'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <AuthLayout title="E-Commerce" :subtitle="pageTitle">
    <div v-if="error" class="mb-4 rounded bg-red-100 p-3 text-red-600">
      {{ error }}
    </div>

    <div v-if="isRegisterMode" class="mb-4">
      <label class="mb-2 block text-sm font-medium">Name</label>
      <input
        v-model="name"
        type="text"
        placeholder="Enter name"
        class="w-full rounded-lg border p-3 focus:outline-none focus:ring-2 focus:ring-black"
      >
    </div>

    <div class="mb-4">
      <label class="mb-2 block text-sm font-medium">Email</label>
      <input
        v-model="email"
        type="email"
        placeholder="Enter email"
        class="w-full rounded-lg border p-3 focus:outline-none focus:ring-2 focus:ring-black"
      >
    </div>

    <div class="mb-6">
      <label class="mb-2 block text-sm font-medium">Password</label>
      <input
        v-model="password"
        type="password"
        placeholder="Enter password"
        class="w-full rounded-lg border p-3 focus:outline-none focus:ring-2 focus:ring-black"
      >
    </div>

    <button
      :disabled="loading"
      @click="submit"
      class="w-full rounded-lg bg-black py-3 text-white transition hover:bg-gray-800 disabled:cursor-not-allowed disabled:opacity-70"
    >
      {{ loading ? 'Please wait...' : isRegisterMode ? 'Create Account' : 'Login' }}
    </button>

    <p class="mt-5 text-center text-sm text-slate-600">
      {{ isRegisterMode ? 'Already have an account?' : "Don't have an account?" }}
      <button
        type="button"
        @click="switchMode"
        class="ml-2 font-semibold text-slate-900 underline"
      >
        {{ isRegisterMode ? 'Login' : 'Register' }}
      </button>
    </p>
  </AuthLayout>
</template>
