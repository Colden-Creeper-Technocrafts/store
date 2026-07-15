<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { fetchAllSettings, updateSettings, uploadStoreLogo, uploadBannerImage, uploadStoreFavicon, type StoreSettings } from '../services/adminSettings'

const currencies = [
  { code: 'INR', label: 'INR (₹)' },
  { code: 'USD', label: 'USD ($)' },
  { code: 'EUR', label: 'EUR (€)' },
  { code: 'GBP', label: 'GBP (£)' },
  { code: 'JPY', label: 'JPY (¥)' },
]

const allSettings    = ref<StoreSettings[]>([])
const selectedId     = ref<number | null>(null)
const isLoading      = ref(true)
const isSaving       = ref(false)
const errorMessage   = ref('')
const successMessage = ref('')

// Logo upload state
const logoFileInput  = ref<HTMLInputElement | null>(null)
const logoFile       = ref<File | null>(null)
const logoPreview    = ref<string | null>(null)
const logoCleared    = ref(false)

// Banner image upload state
const bannerFileInput = ref<HTMLInputElement | null>(null)
const bannerFile      = ref<File | null>(null)
const bannerPreview   = ref<string | null>(null)
const bannerCleared   = ref(false)

// Favicon upload state
const faviconFileInput = ref<HTMLInputElement | null>(null)
const faviconFile      = ref<File | null>(null)
const faviconPreview   = ref<string | null>(null)
const faviconCleared   = ref(false)

function onLogoSelect(e: Event) {
  const file = (e.target as HTMLInputElement).files?.[0] ?? null
  logoFile.value    = file
  logoCleared.value = false
  if (file) {
    logoPreview.value = URL.createObjectURL(file)
  }
}

function removeLogo() {
  logoFile.value      = null
  logoPreview.value   = null
  logoCleared.value   = true
  form.value.logo_url = ''
  if (logoFileInput.value) logoFileInput.value.value = ''
}

function onBannerSelect(e: Event) {
  const file = (e.target as HTMLInputElement).files?.[0] ?? null
  bannerFile.value    = file
  bannerCleared.value = false
  if (file) bannerPreview.value = URL.createObjectURL(file)
}

function removeBanner() {
  bannerFile.value          = null
  bannerPreview.value       = null
  bannerCleared.value       = true
  form.value.banner_image   = ''
  if (bannerFileInput.value) bannerFileInput.value.value = ''
}

function onFaviconSelect(e: Event) {
  const file = (e.target as HTMLInputElement).files?.[0] ?? null
  faviconFile.value    = file
  faviconCleared.value = false
  if (file) faviconPreview.value = URL.createObjectURL(file)
}

function removeFavicon() {
  faviconFile.value        = null
  faviconPreview.value     = null
  faviconCleared.value     = true
  form.value.favicon_url   = ''
  if (faviconFileInput.value) faviconFileInput.value.value = ''
}

const form = ref({
  store_name:        '',
  business_type:     '',
  store_email:       '',
  store_phone:       '',
  store_description: '',
  logo_url:          '',
  favicon_url:       '',
  tagline:           '',
  banner_image:      '',
  banner_title:      '',
  banner_text:       '',
  currency:                  'INR',
  shipping_charge:           0,
  free_shipping_threshold:   null as number | null,
  is_active:                 false,
})

const displayLogo    = computed(() => logoPreview.value    || form.value.logo_url    || null)
const displayBanner  = computed(() => bannerPreview.value  || form.value.banner_image || null)
const displayFavicon = computed(() => faviconPreview.value || form.value.favicon_url  || null)

const dropdownOptions = computed(() =>
  allSettings.value.map((s) => ({
    value: s.id,
    label: `${s.store_name} (${s.layout ?? 'ladies'})`,
    isActive: s.is_active,
  }))
)

const selectedStore = computed(() =>
  allSettings.value.find((s) => s.id === selectedId.value) ?? null
)

function populateForm(s: StoreSettings) {
  form.value.store_name        = s.store_name        ?? ''
  form.value.business_type     = s.business_type     ?? ''
  form.value.store_email       = s.store_email       ?? ''
  form.value.store_phone       = s.store_phone       ?? ''
  form.value.store_description = s.store_description ?? ''
  form.value.logo_url          = s.logo_url          ?? ''
  form.value.favicon_url       = s.favicon_url       ?? ''
  form.value.tagline           = s.tagline           ?? ''
  form.value.banner_image      = s.banner_image      ?? ''
  form.value.banner_title      = s.banner_title      ?? ''
  form.value.banner_text       = s.banner_text       ?? ''
  form.value.currency                = s.currency                ?? 'INR'
  form.value.shipping_charge         = s.shipping_charge         ?? 0
  form.value.free_shipping_threshold = s.free_shipping_threshold ?? null
  form.value.is_active               = s.is_active               ?? false
  // Reset pending file/clear state when switching stores
  logoFile.value    = null
  logoPreview.value = null
  logoCleared.value = false
  if (logoFileInput.value) logoFileInput.value.value = ''
  bannerFile.value    = null
  bannerPreview.value = null
  bannerCleared.value = false
  if (bannerFileInput.value) bannerFileInput.value.value = ''
  faviconFile.value    = null
  faviconPreview.value = null
  faviconCleared.value = false
  if (faviconFileInput.value) faviconFileInput.value.value = ''
}

watch(selectedId, (id) => {
  errorMessage.value  = ''
  successMessage.value = ''
  const store = allSettings.value.find((s) => s.id === id)
  if (store) populateForm(store)
})

onMounted(async () => {
  try {
    allSettings.value = await fetchAllSettings()
    // Auto-select the currently active store
    const active = allSettings.value.find((s) => s.is_active) ?? allSettings.value[0] ?? null
    if (active) {
      selectedId.value = active.id
      populateForm(active)
    }
  } catch {
    errorMessage.value = 'Failed to load settings.'
  } finally {
    isLoading.value = false
  }
})

const isFormValid = computed(() => form.value.store_name.trim() !== '' && selectedId.value !== null)

async function save() {
  if (!isFormValid.value || selectedId.value === null) return
  isSaving.value      = true
  errorMessage.value  = ''
  successMessage.value = ''

  try {
    // Upload logo if a new file was selected
    if (logoFile.value) {
      form.value.logo_url = await uploadStoreLogo(selectedId.value, logoFile.value)
      logoFile.value    = null
      logoPreview.value = null
      if (logoFileInput.value) logoFileInput.value.value = ''
    }

    // Upload banner image if a new file was selected
    if (bannerFile.value) {
      form.value.banner_image = await uploadBannerImage(selectedId.value, bannerFile.value)
      bannerFile.value    = null
      bannerPreview.value = null
      if (bannerFileInput.value) bannerFileInput.value.value = ''
    }

    // Upload favicon if a new file was selected
    if (faviconFile.value) {
      form.value.favicon_url = await uploadStoreFavicon(selectedId.value, faviconFile.value)
      faviconFile.value    = null
      faviconPreview.value = null
      if (faviconFileInput.value) faviconFileInput.value.value = ''
    }

    const logoWasCleared    = logoCleared.value
    const bannerWasCleared  = bannerCleared.value
    const faviconWasCleared = faviconCleared.value
    if (logoWasCleared)    logoCleared.value    = false
    if (bannerWasCleared)  bannerCleared.value  = false
    if (faviconWasCleared) faviconCleared.value = false

    const payload = {
      store_name:        form.value.store_name,
      business_type:     form.value.business_type || null,
      store_email:       form.value.store_email    || null,
      store_phone:       form.value.store_phone    || null,
      store_description: form.value.store_description || null,
      tagline:           form.value.tagline        || null,
      banner_title:      form.value.banner_title   || null,
      banner_text:       form.value.banner_text    || null,
      currency:                  form.value.currency,
      shipping_charge:           Number(form.value.shipping_charge) || 0,
      free_shipping_threshold:   form.value.free_shipping_threshold !== null && form.value.free_shipping_threshold !== undefined
                                   ? Number(form.value.free_shipping_threshold)
                                   : null,
      is_active:                 form.value.is_active,
      ...(logoWasCleared    ? { logo_url:     null } : {}),
      ...(bannerWasCleared  ? { banner_image: null } : {}),
      ...(faviconWasCleared ? { favicon_url:  null } : {}),
    }

    const updated = await updateSettings(selectedId.value, payload)

    // Refresh the local list so dropdown badges stay accurate
    allSettings.value = await fetchAllSettings()
    selectedId.value  = updated.id
    populateForm(updated)

    successMessage.value = form.value.is_active
      ? 'Settings saved and store set as active.'
      : 'Settings saved successfully.'
  } catch (e: any) {
    errorMessage.value = e?.response?.data?.message ?? 'Failed to save settings.'
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
      <p class="mt-3 text-slate-600">Select a store from the dropdown to view and edit its configuration.</p>
    </section>

    <div v-if="isLoading" class="flex h-32 items-center justify-center text-slate-400">Loading settings…</div>

    <template v-else>
      <!-- Messages -->
      <div v-if="errorMessage"   class="rounded-2xl border border-rose-200    bg-rose-50    px-4 py-3 text-sm text-rose-700">{{ errorMessage }}</div>
      <div v-if="successMessage" class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ successMessage }}</div>

      <!-- Store selector -->
      <section class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
        <h2 class="text-2xl font-semibold text-slate-900 mb-6">Select Store</h2>

        <div class="flex flex-col gap-4 sm:flex-row sm:items-end">
          <div class="flex-1">
            <label class="block text-sm font-semibold text-slate-900 mb-2">Store</label>
            <select
              v-model="selectedId"
              class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 focus:outline-none focus:ring-2 focus:ring-slate-900"
            >
              <option :value="null" disabled>— Choose a store —</option>
              <option
                v-for="opt in dropdownOptions"
                :key="opt.value"
                :value="opt.value"
              >
                {{ opt.label }}{{ opt.isActive ? ' ✦ Active' : '' }}
              </option>
            </select>
          </div>

          <div
            v-if="selectedStore"
            :class="[
              'shrink-0 rounded-2xl border px-5 py-3 text-sm font-semibold',
              selectedStore.is_active
                ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
                : 'border-slate-200 bg-slate-50 text-slate-500'
            ]"
          >
            {{ selectedStore.is_active ? '✦ Currently Active' : 'Inactive' }}
          </div>
        </div>
      </section>

      <template v-if="selectedId !== null">
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

            <div class="grid gap-6 sm:grid-cols-2">
              <div>
                <label class="block text-sm font-semibold text-slate-900 mb-2">Store Logo</label>

                <!-- Hidden file input -->
                <input
                  ref="logoFileInput"
                  type="file"
                  accept="image/jpg,image/jpeg,image/png,image/gif,image/webp"
                  class="hidden"
                  @change="onLogoSelect"
                />

                <!-- Preview or placeholder -->
                <div
                  class="relative flex items-center justify-center rounded-2xl border-2 border-dashed border-slate-200 bg-slate-50 p-4"
                  :class="displayLogo ? 'border-solid border-slate-300 bg-white' : ''"
                >
                  <img
                    v-if="displayLogo"
                    :src="displayLogo"
                    alt="Logo preview"
                    class="max-h-24 w-auto object-contain"
                  />
                  <p v-else class="text-sm text-slate-400">No logo uploaded — store name will be shown</p>

                  <!-- Remove button -->
                  <button
                    v-if="displayLogo"
                    type="button"
                    @click="removeLogo"
                    class="absolute right-2 top-2 flex h-6 w-6 items-center justify-center rounded-full bg-rose-100 text-rose-600 hover:bg-rose-200"
                    title="Remove logo"
                  >
                    ✕
                  </button>
                </div>

                <button
                  type="button"
                  @click="logoFileInput?.click()"
                  class="mt-3 w-full rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                >
                  {{ displayLogo ? 'Replace Logo' : 'Upload Logo' }}
                </button>
                <p class="mt-1 text-xs text-slate-400">JPG, PNG, GIF or WebP · Max 3 MB</p>
              </div>
              <div>
                <label class="block text-sm font-semibold text-slate-900 mb-2">Tagline</label>
                <input
                  v-model="form.tagline"
                  type="text"
                  placeholder="e.g. Since 2003"
                  class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 focus:outline-none focus:ring-2 focus:ring-slate-900"
                />
                <p class="mt-1 text-xs text-slate-400">Short text shown beside the logo in the header.</p>
              </div>
            </div>

            <!-- Favicon -->
            <div>
              <label class="block text-sm font-semibold text-slate-900 mb-2">Favicon</label>
              <p class="mb-3 text-xs text-slate-400">The small icon shown in browser tabs. PNG or ICO · Max 512 KB · Recommended 32 × 32 px</p>

              <input
                ref="faviconFileInput"
                type="file"
                accept="image/png,image/x-icon,image/vnd.microsoft.icon,image/svg+xml"
                class="hidden"
                @change="onFaviconSelect"
              />

              <div class="flex items-center gap-4">
                <div
                  class="relative flex h-16 w-16 flex-shrink-0 items-center justify-center rounded-2xl border-2 border-dashed border-slate-200 bg-slate-50"
                  :class="displayFavicon ? 'border-solid border-slate-300 bg-white' : ''"
                >
                  <img
                    v-if="displayFavicon"
                    :src="displayFavicon"
                    alt="Favicon preview"
                    class="h-8 w-8 object-contain"
                  />
                  <span v-else class="text-xl">🌐</span>

                  <button
                    v-if="displayFavicon"
                    type="button"
                    @click="removeFavicon"
                    class="absolute -right-2 -top-2 flex h-5 w-5 items-center justify-center rounded-full bg-rose-100 text-xs text-rose-600 hover:bg-rose-200"
                    title="Remove favicon"
                  >✕</button>
                </div>

                <button
                  type="button"
                  @click="faviconFileInput?.click()"
                  class="rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                >
                  {{ displayFavicon ? 'Replace Favicon' : 'Upload Favicon' }}
                </button>
              </div>
            </div>
          </div>
        </section>

        <!-- Banner -->
        <section class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
          <h2 class="text-2xl font-semibold text-slate-900 mb-2">Homepage Banner</h2>
          <p class="mb-6 text-sm text-slate-500">Displayed as a full-width hero at the top of the storefront homepage.</p>

          <div class="space-y-6">
            <!-- Banner image upload -->
            <div>
              <label class="block text-sm font-semibold text-slate-900 mb-2">Banner Image</label>

              <input
                ref="bannerFileInput"
                type="file"
                accept="image/jpg,image/jpeg,image/png,image/gif,image/webp"
                class="hidden"
                @change="onBannerSelect"
              />

              <div
                class="relative flex items-center justify-center overflow-hidden rounded-2xl border-2 border-dashed border-slate-200 bg-slate-50"
                :class="displayBanner ? 'border-solid border-slate-300 bg-white' : 'p-8'"
              >
                <img
                  v-if="displayBanner"
                  :src="displayBanner"
                  alt="Banner preview"
                  class="h-48 w-full object-cover"
                />
                <p v-else class="text-sm text-slate-400">No banner image — a gradient fallback will be shown</p>

                <button
                  v-if="displayBanner"
                  type="button"
                  @click="removeBanner"
                  class="absolute right-3 top-3 flex h-7 w-7 items-center justify-center rounded-full bg-rose-100 text-rose-600 hover:bg-rose-200"
                  title="Remove banner image"
                >
                  ✕
                </button>
              </div>

              <button
                type="button"
                @click="bannerFileInput?.click()"
                class="mt-3 w-full rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
              >
                {{ displayBanner ? 'Replace Banner Image' : 'Upload Banner Image' }}
              </button>
              <p class="mt-1 text-xs text-slate-400">JPG, PNG, GIF or WebP · Max 6 MB · Recommended 1920 × 600 px</p>
            </div>

            <div class="grid gap-6 sm:grid-cols-2">
              <div>
                <label class="block text-sm font-semibold text-slate-900 mb-2">Banner Title</label>
                <input
                  v-model="form.banner_title"
                  type="text"
                  placeholder="e.g. Discover Timeless Elegance"
                  class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 focus:outline-none focus:ring-2 focus:ring-slate-900"
                />
              </div>
              <div>
                <label class="block text-sm font-semibold text-slate-900 mb-2">Banner Text</label>
                <input
                  v-model="form.banner_text"
                  type="text"
                  placeholder="e.g. Curated jewellery for every occasion"
                  class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 focus:outline-none focus:ring-2 focus:ring-slate-900"
                />
              </div>
            </div>
          </div>
        </section>

        <!-- Shipping -->
        <section class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
          <h2 class="text-2xl font-semibold text-slate-900 mb-2">Shipping</h2>
          <p class="text-slate-500 mb-6">Flat shipping fee applied to every order. Set a threshold for free shipping.</p>

          <div class="grid gap-6 sm:grid-cols-2">
            <div>
              <label class="block text-sm font-semibold text-slate-900 mb-2">Shipping Charge (₹)</label>
              <input
                v-model.number="form.shipping_charge"
                type="number"
                min="0"
                step="0.01"
                placeholder="e.g. 50"
                class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 focus:outline-none focus:ring-2 focus:ring-slate-900"
              />
              <p class="mt-1 text-xs text-slate-400">Charged on every order. Enter 0 for no shipping fee.</p>
            </div>
            <div>
              <label class="block text-sm font-semibold text-slate-900 mb-2">Free Shipping Threshold (₹)</label>
              <input
                v-model.number="form.free_shipping_threshold"
                type="number"
                min="0"
                step="0.01"
                placeholder="e.g. 500 (leave blank to disable)"
                class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 focus:outline-none focus:ring-2 focus:ring-slate-900"
              />
              <p class="mt-1 text-xs text-slate-400">Orders at or above this amount get free shipping. Leave blank to always charge.</p>
            </div>
          </div>

          <div class="mt-4 rounded-2xl border border-sky-100 bg-sky-50 px-5 py-3 text-sm text-sky-800">
            <span v-if="!form.shipping_charge">Shipping is free on all orders.</span>
            <span v-else-if="form.free_shipping_threshold">
              Shipping: ₹{{ Number(form.shipping_charge).toFixed(2) }} · Free on orders ≥ ₹{{ Number(form.free_shipping_threshold).toFixed(2) }}
            </span>
            <span v-else>Flat shipping: ₹{{ Number(form.shipping_charge).toFixed(2) }} on every order.</span>
          </div>
        </section>

        <!-- Activation -->
        <section class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
          <h2 class="text-2xl font-semibold text-slate-900 mb-2">Store Activation</h2>
          <p class="mb-6 text-sm text-slate-500">Only one store can be active at a time. Activating this store will automatically deactivate all others.</p>
          <label
            :class="[
              'flex cursor-pointer items-start gap-4 rounded-2xl border-2 p-5 transition',
              form.is_active
                ? 'border-emerald-400 bg-emerald-50'
                : 'border-slate-200 bg-white hover:border-slate-300'
            ]"
          >
            <input
              v-model="form.is_active"
              type="checkbox"
              class="mt-0.5 h-5 w-5 rounded border-slate-300 accent-emerald-600"
            />
            <div>
              <p class="font-semibold text-slate-900">Set as Active Store</p>
              <p class="mt-1 text-sm text-slate-600">
                This store will be shown to customers. All other stores will be deactivated.
              </p>
            </div>
          </label>
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
    </template>
  </div>
</template>
