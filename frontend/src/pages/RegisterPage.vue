<script setup>

import { ref } from 'vue'
import api from '../services/api'
import { useRouter } from 'vue-router'

import AuthLayout from '../layouts/AuthLayout.vue'

const router = useRouter()

const name = ref('')
const email = ref('')
const password = ref('')
const error = ref('')
const loading = ref(false)

const register = async () => {

    error.value = ''

    loading.value = true

    try {

        await api.post('/register', {
            name: name.value,
            email: email.value,
            password: password.value
        })

        router.push('/login')

    } catch (err) {

        error.value = err.response?.data?.message || 'Registration failed'

    } finally {

        loading.value = false

    }

}

</script>

<template>

<AuthLayout subtitle="Create Your Account">

    <div
        v-if="error"
        class="bg-red-100 text-red-500 p-3 rounded mb-4"
    >
        {{ error }}
    </div>

    <div class="mb-4">

        <label class="block mb-2 text-sm font-medium">
            Name
        </label>

        <input
            v-model="name"
            type="text"
            placeholder="Enter name"
            class="w-full border rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-black"
        >

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

    <button
        @click="register"
        :disabled="loading"
        class="w-full bg-black text-white py-3 rounded-lg hover:bg-gray-800 transition"
    >

        {{ loading ? 'Please wait...' : 'Register' }}

    </button>

    <div class="text-center mt-5">

        <router-link
            to="/login"
            class="text-blue-500 hover:underline"
        >
            Already have account?
        </router-link>

    </div>

</AuthLayout>

</template>