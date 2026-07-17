<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '../services/api'
import { useAuthStore } from '../stores/auth'
import { useCartStore } from '../stores/cart'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const cartStore = useCartStore()

const displayName = computed(() => authStore.user?.name || 'Customer')
const email = computed(() => authStore.user?.email || '')
const initials = computed(() => displayName.value.split(' ').map((w: string) => w[0]).slice(0, 2).join('').toUpperCase())

// Edit form
const editing = ref(false)
const editName = ref('')
const editEmail = ref('')
const saving = ref(false)
const saveError = ref('')
const pendingEmail = ref<string | null>(null)

// Notices from email verification redirect
const emailVerifiedNotice = ref(false)
const emailErrorNotice = ref('')

onMounted(async () => {
  if (route.query.email_verified === '1') {
    emailVerifiedNotice.value = true
    router.replace('/profile')
  }
  if (route.query.email_error) {
    emailErrorNotice.value = (route.query.email_error as string) === 'expired'
      ? 'expired'
      : 'invalid'
    router.replace('/profile')
  }
  // Fetch current pending_email if any
  try {
    const res = await api.get('/profile')
    if (res.data.pending_email) {
      pendingEmail.value = res.data.pending_email
    }
  } catch { /* ignore */ }
})

const startEdit = () => {
  editName.value = authStore.user?.name || ''
  editEmail.value = authStore.user?.email || ''
  saveError.value = ''
  editing.value = true
}

const cancelEdit = () => {
  editing.value = false
  saveError.value = ''
}

const saveProfile = async () => {
  saveError.value = ''
  saving.value = true
  try {
    const res = await api.patch('/profile', {
      name: editName.value.trim(),
      email: editEmail.value.trim(),
    })
    const { user, email_changed, pending_email: newPending } = res.data
    authStore.setUser({ ...authStore.user, name: user.name, email: user.email })
    pendingEmail.value = newPending ?? null
    editing.value = false
    if (email_changed) {
      // Notice is shown via pendingEmail ref — no redirect needed
    }
  } catch (err: any) {
    const errors = err.response?.data?.errors
    saveError.value = errors
      ? Object.values(errors).flat().join(' ')
      : err.response?.data?.message ?? 'Failed to save. Please try again.'
  } finally {
    saving.value = false
  }
}

const logout = () => {
  authStore.logout()
  cartStore.reset()
  router.push('/')
}
</script>

<template>
  <div class="mx-auto max-w-2xl space-y-5">

    <!-- Email verified notice -->
    <div v-if="emailVerifiedNotice" class="border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800 flex items-start justify-between gap-3">
      <span>Your email address has been updated successfully.</span>
      <button @click="emailVerifiedNotice = false" class="shrink-0 text-emerald-400 hover:text-emerald-700">✕</button>
    </div>

    <!-- Email error notice -->
    <div v-if="emailErrorNotice" class="border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800 flex items-start justify-between gap-3">
      <span>{{ emailErrorNotice === 'expired' ? 'The email verification link has expired. Please request a new one.' : 'The email verification link is invalid or has already been used.' }}</span>
      <button @click="emailErrorNotice = ''" class="shrink-0 text-rose-400 hover:text-rose-700">✕</button>
    </div>

    <!-- Pending email notice -->
    <div v-if="pendingEmail" class="border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
      A verification email has been sent to <strong>{{ pendingEmail }}</strong>. Click the link in that email to confirm your new address.
    </div>

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

    <!-- Account info / Edit form -->
    <div class="border border-stone-200 bg-white p-6">
      <div class="flex items-center justify-between">
        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-stone-400">Account Details</p>
        <button v-if="!editing" @click="startEdit"
          class="text-xs font-semibold uppercase tracking-[0.12em] text-stone-500 underline decoration-amber-400 underline-offset-2 hover:text-amber-800">
          Edit
        </button>
      </div>

      <!-- Read-only view -->
      <div v-if="!editing" class="mt-4 grid gap-4 sm:grid-cols-2">
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

      <!-- Edit form -->
      <form v-else @submit.prevent="saveProfile" class="mt-4 space-y-4">
        <div v-if="saveError" class="border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700">{{ saveError }}</div>

        <div>
          <label class="block text-sm font-medium text-stone-700">Name</label>
          <input v-model="editName" type="text" required
            class="mt-1 w-full border border-stone-300 px-3 py-2.5 text-sm focus:border-stone-600 focus:outline-none" />
        </div>

        <div>
          <label class="block text-sm font-medium text-stone-700">Email</label>
          <input v-model="editEmail" type="email" required
            class="mt-1 w-full border border-stone-300 px-3 py-2.5 text-sm focus:border-stone-600 focus:outline-none" />
          <p class="mt-1 text-xs text-stone-400">Changing your email will send a verification link to the new address.</p>
        </div>

        <div class="flex gap-3 pt-1">
          <button type="submit" :disabled="saving"
            class="bg-stone-900 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-stone-700 disabled:opacity-60">
            {{ saving ? 'Saving…' : 'Save Changes' }}
          </button>
          <button type="button" @click="cancelEdit"
            class="border border-stone-300 px-6 py-2.5 text-sm font-semibold text-stone-700 transition hover:bg-stone-50">
            Cancel
          </button>
        </div>
      </form>
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
