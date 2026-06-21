<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useAuthStore } from '../../stores/auth'
import { fetchAddresses, setDefaultAddress, deleteAddress, type UserAddress } from '../../services/addresses'

const authStore = useAuthStore()
const addresses = ref<UserAddress[]>([])
const loading = ref(false)
const error = ref('')
const actionId = ref<number | null>(null)

const load = async () => {
  loading.value = true
  try {
    addresses.value = await fetchAddresses()
  } catch {
    error.value = 'Failed to load addresses.'
  } finally {
    loading.value = false
  }
}

const makeDefault = async (id: number) => {
  actionId.value = id
  try {
    await setDefaultAddress(id)
    await load()
  } finally {
    actionId.value = null
  }
}

const remove = async (id: number) => {
  if (!confirm('Remove this address?')) return
  actionId.value = id
  try {
    await deleteAddress(id)
    addresses.value = addresses.value.filter(a => a.id !== id)
  } finally {
    actionId.value = null
  }
}

onMounted(() => {
  if (authStore.isCustomer) load()
})
</script>

<template>
  <section class="space-y-6">
    <div class="border border-stone-200 bg-white p-8">
      <p class="text-xs font-semibold uppercase tracking-[0.18em] text-stone-500">Ladies Collection</p>
      <h1 class="mt-3 text-3xl text-stone-900">Saved Addresses</h1>
      <p class="mt-2 text-sm text-stone-500">Addresses are saved automatically when you place an order.</p>
    </div>

    <!-- Not logged in -->
    <div v-if="!authStore.isCustomer" class="border border-stone-200 bg-white p-12 text-center space-y-4">
      <p class="text-stone-600">Please log in to view your saved addresses.</p>
      <router-link to="/login"
        class="inline-block border border-stone-300 px-6 py-3 text-sm font-semibold text-stone-900 transition hover:bg-stone-50">
        Login
      </router-link>
    </div>

    <div v-else-if="loading" class="border border-stone-200 bg-white p-10 text-center text-stone-500">
      Loading addresses…
    </div>

    <div v-else-if="error" class="border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
      {{ error }}
    </div>

    <div v-else-if="addresses.length === 0" class="border border-stone-200 bg-white p-12 text-center space-y-4">
      <p class="text-stone-500 text-sm">No saved addresses yet.</p>
      <p class="text-stone-400 text-xs">Your address will be saved automatically the next time you place an order.</p>
      <router-link to="/store"
        class="inline-block border border-stone-300 px-6 py-3 text-sm font-semibold text-stone-900 transition hover:bg-stone-50">
        Shop Now
      </router-link>
    </div>

    <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <div
        v-for="addr in addresses"
        :key="addr.id"
        :class="[
          'relative border bg-white p-5 space-y-3 transition',
          addr.is_default ? 'border-stone-800' : 'border-stone-200'
        ]"
      >
        <!-- Default badge -->
        <div class="flex items-start justify-between gap-2">
          <span
            v-if="addr.is_default"
            class="inline-block rounded-full bg-stone-900 px-3 py-0.5 text-[10px] font-bold uppercase tracking-wider text-white"
          >
            Default
          </span>
          <span
            v-else-if="addr.label"
            class="inline-block rounded-full bg-stone-100 px-3 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-stone-600"
          >
            {{ addr.label }}
          </span>
          <span v-else></span>

          <!-- Remove -->
          <button
            @click="remove(addr.id)"
            :disabled="actionId === addr.id"
            class="text-stone-300 hover:text-rose-500 transition disabled:opacity-40 text-lg leading-none"
            title="Remove"
          >×</button>
        </div>

        <!-- Address details -->
        <div class="text-sm text-stone-700 space-y-0.5">
          <p class="font-semibold text-stone-900">{{ addr.name }}</p>
          <p>{{ addr.address }}</p>
          <p>{{ addr.city }}<span v-if="addr.state">, {{ addr.state }}</span> — {{ addr.postal_code }}</p>
          <p>{{ addr.country }}</p>
          <p v-if="addr.phone" class="text-stone-500 text-xs mt-1">{{ addr.phone }}</p>
        </div>

        <!-- Set as default -->
        <button
          v-if="!addr.is_default"
          @click="makeDefault(addr.id)"
          :disabled="actionId === addr.id"
          class="text-xs font-semibold text-stone-500 underline underline-offset-2 hover:text-stone-900 transition disabled:opacity-40"
        >
          {{ actionId === addr.id ? 'Updating…' : 'Set as default' }}
        </button>
      </div>
    </div>
  </section>
</template>
