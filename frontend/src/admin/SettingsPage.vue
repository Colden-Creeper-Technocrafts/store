<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { fetchSettings, updateSettings, type StoreSettings } from '../services/adminSettings'

const businessTypes = [
  { id: 'jewelry',     name: 'Jewelry Store',     description: 'Jewelry, watches, and accessories', icon: '💎' },
  { id: 'supermarket', name: 'Supermarket',        description: 'Grocery and general retail',        icon: '🛒' },
  { id: 'fashion',     name: 'Fashion Boutique',   description: 'Clothing and fashion items',        icon: '👗' },
  { id: 'electronics', name: 'Electronics Store',  description: 'Gadgets and tech products',         icon: '📱' },
]

const currencies = [
  { code: 'INR', label: 'INR (₹)' },
  { code: 'USD', label: 'USD ($)' },
  { code: 'EUR', label: 'EUR (€)' },
  { code: 'GBP', label: 'GBP (£)' },
  { code: 'JPY', label: 'JPY (¥)' },
]

const isLoading = ref(true)
const isSaving = ref(false)
const errorMessage = ref('')
const successMessage = ref('')

const form = ref({
  store_name: '',
  business_type: '',
  store_email: '',
  store_phone: '',
  store_description: '',
  currency: 'INR',
  features: {
    reviews: true,
    wishlist: true,
    subscriptions: false,
    loyalty: false,
  },
})

function populateForm(settings: StoreSettings) {
  form.value.store_name        = settings.store_name ?? ''
  form.value.business_type     = settings.business_type ?? ''
  form.value.store_email       = settings.store_email ?? ''
  form.value.store_phone       = settings.store_phone ?? ''
  form.value.store_description = settings.store_description ?? ''
  form.value.currency          = settings.currency ?? 'INR'
  form.value.features = {
    reviews:       settings.features?.reviews       ?? true,
    wishlist:      settings.features?.wishlist       ?? true,
    subscriptions: settings.features?.subscriptions  ?? false,
    loyalty:       settings.features?.loyalty        ?? false,
  }
}

onMounted(async () => {
  try {
    const settings = await fetchSettings()
    if (settings) populateForm(settings)
  } catch {
    errorMessage.value = 'Failed to load settings.'
  } finally {
    isLoading.value = false
  }
})

const isFormValid = computed(() => form.value.store_name.trim() !== '')

async function save() {
  if (!isFormValid.value) return
  isSaving.value = true
  errorMessage.value = ''
  successMessage.value = ''
  try {
    await updateSettings(form.value)
    successMessage.value = 'Settings saved successfully.'
  } catch (e: any) {
    const msg = e?.response?.data?.message ?? 'Failed to save settings.'
    errorMessage.value = msg
  } finally {
    isSaving.value = false
  }
}
</script>

<template>
  <div class="space-y-8">
    <!-- Header -->
    <section class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
      <h1 class="text-4xl font-semibold text-slate-900">Store Settings</h1>
      <p class="mt-3 text-slate-600">Configure your store information and preferences.</p>
    </section>

    <div v-if="isLoading" class="flex h-32 items-center justify-center text-slate-400">Loading settings…</div>

    <template v-else>
      <!-- Messages -->
      <div v-if="errorMessage" class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ errorMessage }}</div>
      <div v-if="successMessage" class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ successMessage }}</div>

      <!-- Business Type -->
      <section class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
        <h2 class="text-2xl font-semibold text-slate-900 mb-6">Business Type</h2>
        <div class="grid gap-4 sm:grid-cols-2">
          <button
            v-for="type in businessTypes"
            :key="type.id"
            type="button"
            @click="form.business_type = type.id"
            :class="[
              'rounded-3xl border-2 p-6 text-left transition',
              form.business_type === type.id
                ? 'border-slate-900 bg-slate-50'
                : 'border-slate-200 bg-white hover:border-slate-300'
            ]"
          >
            <div class="text-4xl mb-3">{{ type.icon }}</div>
            <h3 class="font-semibold text-slate-900">{{ type.name }}</h3>
            <p class="mt-2 text-sm text-slate-600">{{ type.description }}</p>
          </button>
        </div>
      </section>

      <!-- Store Information -->
      <section class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
        <h2 class="text-2xl font-semibold text-slate-900 mb-6">Store Information</h2>
        <div class="space-y-6">
          <div class="grid gap-6 sm:grid-cols-2">
            <div>
              <label class="block text-sm font-semibold text-slate-900 mb-2">Store Name *</label>
              <input
                v-model="form.store_name"
                type="text"
                placeholder="Enter your store name"
                class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 focus:outline-none focus:ring-2 focus:ring-slate-900"
              />
            </div>
            <div>
              <label class="block text-sm font-semibold text-slate-900 mb-2">Email Address</label>
              <input
                v-model="form.store_email"
                type="email"
                placeholder="store@example.com"
                class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 focus:outline-none focus:ring-2 focus:ring-slate-900"
              />
            </div>
          </div>

          <div class="grid gap-6 sm:grid-cols-2">
            <div>
              <label class="block text-sm font-semibold text-slate-900 mb-2">Phone Number</label>
              <input
                v-model="form.store_phone"
                type="tel"
                placeholder="+91 98765 43210"
                class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 focus:outline-none focus:ring-2 focus:ring-slate-900"
              />
            </div>
            <div>
              <label class="block text-sm font-semibold text-slate-900 mb-2">Currency</label>
              <select
                v-model="form.currency"
                class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 focus:outline-none focus:ring-2 focus:ring-slate-900"
              >
                <option v-for="c in currencies" :key="c.code" :value="c.code">{{ c.label }}</option>
              </select>
            </div>
          </div>

          <div>
            <label class="block text-sm font-semibold text-slate-900 mb-2">Store Description</label>
            <textarea
              v-model="form.store_description"
              rows="4"
              placeholder="Tell customers about your store…"
              class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 focus:outline-none focus:ring-2 focus:ring-slate-900"
            />
          </div>
        </div>
      </section>

      <!-- Features -->
      <section class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
        <h2 class="text-2xl font-semibold text-slate-900 mb-6">Store Features</h2>
        <div class="grid gap-4">
          <label class="flex cursor-pointer items-center gap-4 rounded-2xl border border-slate-200 p-4 hover:bg-slate-50">
            <input v-model="form.features.reviews" type="checkbox" class="h-5 w-5 rounded border-slate-300" />
            <div>
              <p class="font-semibold text-slate-900">Product Reviews</p>
              <p class="text-sm text-slate-600">Allow customers to review and rate products</p>
            </div>
          </label>
          <label class="flex cursor-pointer items-center gap-4 rounded-2xl border border-slate-200 p-4 hover:bg-slate-50">
            <input v-model="form.features.wishlist" type="checkbox" class="h-5 w-5 rounded border-slate-300" />
            <div>
              <p class="font-semibold text-slate-900">Wishlist</p>
              <p class="text-sm text-slate-600">Let customers save items for later</p>
            </div>
          </label>
          <label class="flex cursor-pointer items-center gap-4 rounded-2xl border border-slate-200 p-4 hover:bg-slate-50">
            <input v-model="form.features.subscriptions" type="checkbox" class="h-5 w-5 rounded border-slate-300" />
            <div>
              <p class="font-semibold text-slate-900">Subscriptions</p>
              <p class="text-sm text-slate-600">Offer recurring subscription products</p>
            </div>
          </label>
          <label class="flex cursor-pointer items-center gap-4 rounded-2xl border border-slate-200 p-4 hover:bg-slate-50">
            <input v-model="form.features.loyalty" type="checkbox" class="h-5 w-5 rounded border-slate-300" />
            <div>
              <p class="font-semibold text-slate-900">Loyalty Program</p>
              <p class="text-sm text-slate-600">Reward repeat customers with points</p>
            </div>
          </label>
        </div>
      </section>

      <!-- Save -->
      <div class="flex justify-end">
        <button
          @click="save"
          :disabled="isSaving || !isFormValid"
          class="rounded-full bg-slate-900 px-8 py-3 font-semibold text-white transition hover:bg-slate-700 disabled:cursor-not-allowed disabled:opacity-50"
        >
          {{ isSaving ? 'Saving…' : 'Save Settings' }}
        </button>
      </div>
    </template>
  </div>
</template>
