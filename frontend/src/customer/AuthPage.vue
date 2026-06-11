<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '../services/api'
import { useAuthStore } from '../stores/auth'
import { sendLoginOtp, verifyLoginOtp, setPassword } from '../services/otp'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const name = ref('')
const email = ref('')
const password = ref('')
const error = ref('')
const loading = ref(false)

// OTP login state
const loginTab = ref<'password' | 'otp'>('password')
const otpPhone = ref('')
const otpCode = ref('')
const otpSent = ref(false)
const otpSending = ref(false)
const otpVerifying = ref(false)

// Set-password state (shown after OTP login for new accounts)
const showSetPassword = ref(false)
const newPassword = ref('')
const newPasswordConfirm = ref('')
const setPasswordLoading = ref(false)
const setPasswordSuccess = ref('')

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

const sendOtp = async () => {
  error.value = ''
  otpSending.value = true
  try {
    await sendLoginOtp(otpPhone.value.trim())
    otpSent.value = true
  } catch (err: any) {
    error.value = err.response?.data?.message ?? 'Failed to send OTP.'
  } finally { otpSending.value = false }
}

const verifyOtp = async () => {
  error.value = ''
  otpVerifying.value = true
  try {
    const result = await verifyLoginOtp(otpPhone.value.trim(), otpCode.value.trim())
    authStore.setAuth({ token: result.token, user: { ...result.user, email: result.user.email ?? undefined }, role: result.user.role })
    if (!result.user.has_password) {
      showSetPassword.value = true
      return
    }
    redirectByRole(result.user.role)
  } catch (err: any) {
    error.value = err.response?.data?.message ?? 'Invalid OTP.'
  } finally { otpVerifying.value = false }
}

const submitSetPassword = async () => {
  error.value = ''
  setPasswordLoading.value = true
  try {
    await setPassword(newPassword.value, newPasswordConfirm.value)
    setPasswordSuccess.value = 'Password saved! Redirecting…'
    setTimeout(() => redirectByRole(authStore.user?.role ?? 'customer'), 1200)
  } catch (err: any) {
    const errors = err.response?.data?.errors
    error.value = errors ? Object.values(errors).flat().join(' ') : err.response?.data?.message ?? 'Failed to set password.'
  } finally { setPasswordLoading.value = false }
}
</script>

<template>
  <div class="mx-auto max-w-md">
    <div class="border border-stone-200 bg-white p-8 sm:p-10">
      <p class="text-xs font-semibold uppercase tracking-[0.22em] text-stone-400">Ladies Collection</p>
      <h1 class="mt-3 text-3xl text-stone-900" style="font-family: Garamond, 'Times New Roman', serif">
        {{ isRegisterMode ? 'Create Account' : 'Welcome Back' }}
      </h1>

      <!-- Set password panel (shown after first OTP login) -->
      <template v-if="showSetPassword">
        <p class="mt-2 text-sm text-stone-500">Set a password to enable email login in the future.</p>
        <div v-if="error" class="mt-4 border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700">{{ error }}</div>
        <div v-if="setPasswordSuccess" class="mt-4 border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-700">{{ setPasswordSuccess }}</div>
        <div class="mt-6 space-y-4">
          <div>
            <label class="block text-sm font-medium text-stone-700">New Password</label>
            <input v-model="newPassword" type="password" placeholder="Min 8 characters"
              class="mt-1 w-full border border-stone-300 px-3 py-2.5 text-sm focus:border-stone-600 focus:outline-none" />
          </div>
          <div>
            <label class="block text-sm font-medium text-stone-700">Confirm Password</label>
            <input v-model="newPasswordConfirm" type="password" placeholder="Repeat password"
              class="mt-1 w-full border border-stone-300 px-3 py-2.5 text-sm focus:border-stone-600 focus:outline-none" />
          </div>
          <button @click="submitSetPassword" :disabled="setPasswordLoading"
            class="w-full bg-stone-900 py-3 text-sm font-semibold text-white transition hover:bg-stone-700 disabled:opacity-60">
            {{ setPasswordLoading ? 'Saving…' : 'Set Password' }}
          </button>
          <button @click="redirectByRole(authStore.user?.role ?? 'customer')"
            class="w-full text-center text-sm text-stone-400 hover:text-stone-700">
            Skip for now
          </button>
        </div>
      </template>

      <!-- Normal login / register / OTP -->
      <template v-else>
        <p class="mt-2 text-sm text-stone-500">
          {{ isRegisterMode ? 'Join to save your orders and wishlist.' : 'Sign in to your account.' }}
        </p>

        <!-- Login tabs (only on login page) -->
        <div v-if="!isRegisterMode" class="mt-6 flex rounded-xl border border-stone-200 p-1 text-sm">
          <button type="button" @click="loginTab = 'password'; error = ''"
            :class="loginTab === 'password' ? 'bg-stone-900 text-white' : 'text-stone-500 hover:text-stone-800'"
            class="flex-1 rounded-lg py-2 font-medium transition">
            Email & Password
          </button>
          <button type="button" @click="loginTab = 'otp'; error = ''"
            :class="loginTab === 'otp' ? 'bg-stone-900 text-white' : 'text-stone-500 hover:text-stone-800'"
            class="flex-1 rounded-lg py-2 font-medium transition">
            Mobile OTP
          </button>
        </div>

        <div v-if="error" class="mt-4 border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700">{{ error }}</div>

        <!-- OTP login flow -->
        <div v-if="!isRegisterMode && loginTab === 'otp'" class="mt-6 space-y-4">
          <div>
            <label class="block text-sm font-medium text-stone-700">Mobile Number</label>
            <input v-model="otpPhone" type="tel" placeholder="+91 98765 43210" :disabled="otpSent"
              class="mt-1 w-full border border-stone-300 px-3 py-2.5 text-sm focus:border-stone-600 focus:outline-none disabled:bg-stone-50" />
          </div>
          <template v-if="otpSent">
            <div>
              <label class="block text-sm font-medium text-stone-700">Enter OTP</label>
              <input v-model="otpCode" type="text" maxlength="6" inputmode="numeric" placeholder="6-digit OTP"
                class="mt-1 w-full border border-stone-300 px-3 py-2.5 text-center text-xl tracking-[0.3em] focus:border-stone-600 focus:outline-none" />
            </div>
            <button @click="verifyOtp" :disabled="otpVerifying || otpCode.length !== 6"
              class="w-full bg-stone-900 py-3 text-sm font-semibold text-white transition hover:bg-stone-700 disabled:opacity-60">
              {{ otpVerifying ? 'Verifying…' : 'Verify & Sign In' }}
            </button>
            <button @click="sendOtp" :disabled="otpSending"
              class="w-full text-center text-sm text-stone-400 hover:text-stone-700 disabled:opacity-60">
              {{ otpSending ? 'Sending…' : 'Resend OTP' }}
            </button>
          </template>
          <button v-else @click="sendOtp" :disabled="otpSending || !otpPhone.trim()"
            class="w-full bg-stone-900 py-3 text-sm font-semibold text-white transition hover:bg-stone-700 disabled:opacity-60">
            {{ otpSending ? 'Sending OTP…' : 'Send OTP' }}
          </button>
        </div>

        <!-- Email/password form -->
        <form v-else @submit.prevent="submit" class="mt-6 space-y-4">
          <div v-if="isRegisterMode">
            <label class="block text-sm font-medium text-stone-700">Full Name</label>
            <input v-model="name" type="text" required placeholder="Your name"
              class="mt-1 w-full border border-stone-300 px-3 py-2.5 text-sm focus:border-stone-600 focus:outline-none" />
          </div>
          <div>
            <label class="block text-sm font-medium text-stone-700">Email</label>
            <input v-model="email" type="email" required placeholder="you@example.com"
              class="mt-1 w-full border border-stone-300 px-3 py-2.5 text-sm focus:border-stone-600 focus:outline-none" />
          </div>
          <div>
            <label class="block text-sm font-medium text-stone-700">Password</label>
            <input v-model="password" type="password" required placeholder="••••••••"
              class="mt-1 w-full border border-stone-300 px-3 py-2.5 text-sm focus:border-stone-600 focus:outline-none" />
          </div>
          <button type="submit" :disabled="loading"
            class="w-full bg-stone-900 py-3 text-sm font-semibold text-white transition hover:bg-stone-700 disabled:opacity-60">
            {{ loading ? 'Please wait…' : isRegisterMode ? 'Create Account' : 'Sign In' }}
          </button>
        </form>

        <p class="mt-6 text-center text-sm text-stone-500">
          {{ isRegisterMode ? 'Already have an account?' : "Don't have an account?" }}
          <button type="button" @click="switchMode"
            class="ml-1 font-semibold text-stone-900 underline decoration-amber-400 underline-offset-2 hover:text-amber-800">
            {{ isRegisterMode ? 'Sign In' : 'Register' }}
          </button>
        </p>
      </template>
    </div>

    <p class="mt-4 text-center text-xs text-stone-400">
      Your data is safe · Secured by Razorpay for payments
    </p>
  </div>
</template>
