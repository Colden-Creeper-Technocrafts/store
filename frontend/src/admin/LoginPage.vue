<script setup>

import { ref } from 'vue'
import api from '../services/api'
import { useAuthStore } from '../stores/auth'
import { useRouter } from 'vue-router'

import AuthLayout from '../layouts/AuthLayout.vue'

const authStore = useAuthStore()
const router = useRouter()

const email = ref('')
const password = ref('')
const error = ref('')
const loading = ref(false)

const login = async () => {

    error.value = ''

    loading.value = true

    try {

        const response = await api.post('/login', {
            email: email.value,
            password: password.value
        })

        const { token, user, role } = response.data
        const normalizedRole = (role || user?.role || '').toLowerCase()

        authStore.setAuth({ token, user, role })

        if (normalizedRole === 'admin') {
            router.push('/backstore/dashboard')
            return
        }

        router.push('/')

    } catch (err) {

        error.value = err.response?.data?.message || 'Login failed'

    } finally {

        loading.value = false

    }

}

</script>

<template>

<AuthLayout subtitle="Welcome Back">

    <div
        v-if="error"
        class="bg-red-100 text-red-500 p-3 rounded mb-4"
    >
        {{ error }}
    </div>

    <div class="mb-4">

        <label class="block mb-2 text-sm font-medium">
            Email
        </label>

        <input
            v-model="email"
            type="email"
            placeholder="Enter email"
            class="w-full border rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-black"
        >

    </div>

    <div class="mb-6">

        <label class="block mb-2 text-sm font-medium">
            Password
        </label>

        <input
            v-model="password"
            type="password"
            placeholder="Enter password"
            class="w-full border rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-black"
        >

    </div>

    <div class="text-right mb-4">
        <router-link
            to="/forgot-password"
            class="text-sm text-blue-500 hover:underline"
        >
            Forgot Password?
        </router-link>
    </div>

    <button
        @click="login"
        :disabled="loading"
        class="w-full bg-black text-white py-3 rounded-lg hover:bg-gray-800 transition"
    >

        {{ loading ? 'Please wait...' : 'Login' }}

    </button>


</AuthLayout>

</template>
