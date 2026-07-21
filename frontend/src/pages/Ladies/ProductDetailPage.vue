<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { loadStoreProduct, formatPrice, checkPincode, type StorefrontProduct, type StorefrontVariant } from '../../services/storefront'
import { useAuthStore } from '../../stores/auth'
import { useCartStore } from '../../stores/cart'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const cartStore = useCartStore()

const product = ref<StorefrontProduct | null>(null)
const loading = ref(true)
const adding = ref(false)
const addError = ref('')
const addSuccess = ref(false)
const selectedVariant = ref<StorefrontVariant | null>(null)

const hasVariants = computed(() => (product.value?.variants?.length ?? 0) > 0)

const linkCopied = ref(false)

function shareUrl() { return window.location.href }
function shareTitle() { return product.value?.name ?? '' }

function shareWhatsApp() {
  window.open(`https://wa.me/?text=${encodeURIComponent(shareTitle() + ' ' + shareUrl())}`, '_blank')
}
function shareFacebook() {
  window.open(`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(shareUrl())}`, '_blank')
}
function shareTwitter() {
  window.open(`https://twitter.com/intent/tweet?text=${encodeURIComponent(shareTitle())}&url=${encodeURIComponent(shareUrl())}`, '_blank')
}
function copyLink() {
  navigator.clipboard.writeText(shareUrl()).then(() => {
    linkCopied.value = true
    setTimeout(() => { linkCopied.value = false }, 2000)
  })
}
const multipleVariants = computed(() => (product.value?.variants?.length ?? 0) > 1)

const variantImages = computed(() => selectedVariant.value?.images ?? [])
const activeImageUrl = ref<string | null>(null)
const displayImage = computed(() => {
  if (activeImageUrl.value) return activeImageUrl.value
  const imgs = variantImages.value
  return imgs.find(img => img.is_primary)?.image_url ?? imgs[0]?.image_url ?? product.value?.image ?? '/images/product-placeholder.svg'
})

watch(selectedVariant, () => { activeImageUrl.value = null })

const displayPrice = computed(() => {
  if (selectedVariant.value) return Number(selectedVariant.value.price ?? 0)
  return Number(product.value?.price ?? 0)
})

const displaySalePrice = computed(() => {
  if (selectedVariant.value) {
    return selectedVariant.value.sale_price != null ? Number(selectedVariant.value.sale_price) : null
  }
  return product.value?.sale_price != null ? Number(product.value.sale_price) : null
})

const displayQuantity = computed(() => {
  if (selectedVariant.value) return selectedVariant.value.quantity
  return product.value?.quantity ?? null
})

const inStock = () => displayQuantity.value == null || displayQuantity.value > 0
const stockLabel = () => {
  const q = displayQuantity.value
  if (q == null) return ''
  if (q === 0) return 'Out of Stock'
  if (q <= 5) return `Only ${q} left`
  return 'In Stock'
}

const variantLabel = (v: StorefrontVariant) => {
  if (v.options && Object.keys(v.options).length) {
    return Object.entries(v.options).map(([, val]) => val).join(' / ')
  }
  return v.sku ?? `Variant ${v.id}`
}

// Pincode checker
const pincodeInput = ref('')
const pincodeChecking = ref(false)
const pincodeResult = ref<{ serviceable: boolean; message: string } | null>(null)

const handlePincodeCheck = async () => {
  const pin = pincodeInput.value.trim()
  if (!pin || pin.length < 6) return
  pincodeChecking.value = true
  pincodeResult.value = null
  try {
    pincodeResult.value = await checkPincode(pin)
  } catch {
    pincodeResult.value = { serviceable: false, message: 'Could not check pincode. Try again.' }
  } finally {
    pincodeChecking.value = false
  }
}

const handleAdd = async () => {
  if (!product.value) return
  addError.value = ''
  addSuccess.value = false
  adding.value = true
  try {
    if (authStore.isCustomer) {
      await cartStore.add(product.value.id, 1)
    } else {
      const p = product.value
      cartStore.addGuest({
        id: p.id,
        name: p.name,
        sku: selectedVariant.value?.sku ?? p.sku ?? null,
        price: displayPrice.value,
        sale_price: displaySalePrice.value,
        image_url: displayImage.value,
        stock: displayQuantity.value ?? null,
      })
    }
    addSuccess.value = true
    setTimeout(() => { addSuccess.value = false }, 2500)
  } catch (e: any) {
    addError.value = e?.response?.data?.errors?.quantity?.[0]
      ?? e?.response?.data?.message
      ?? 'Could not add item.'
  } finally {
    adding.value = false
  }
}

onMounted(async () => {
  const slug = String(route.params.slug ?? '')
  if (!slug) { router.push('/store'); return }
  product.value = await loadStoreProduct(slug)
  loading.value = false
  if (!product.value) { router.push('/store'); return }
  // Pre-select the default variant
  const def = product.value.variants?.find(v => v.is_default) ?? product.value.variants?.[0] ?? null
  selectedVariant.value = def
})
</script>

<template>
  <section class="space-y-6">
    <!-- breadcrumb -->
    <nav class="flex items-center gap-2 text-xs text-stone-500">
      <router-link to="/" class="hover:text-stone-800 transition">Home</router-link>
      <span>/</span>
      <router-link to="/store" class="hover:text-stone-800 transition">Store</router-link>
      <span>/</span>
      <span class="text-stone-800">{{ product?.name ?? '…' }}</span>
    </nav>

    <div v-if="loading" class="border border-stone-200 bg-white p-16 text-center text-stone-500">
      Loading product…
    </div>

    <template v-else-if="product">
      <div class="grid gap-8 lg:grid-cols-2">

        <!-- Image -->
        <div class="space-y-3">
          <div class="w-full overflow-hidden border border-stone-200 bg-stone-50">
            <img
              :src="displayImage"
              :alt="product.name"
              class="w-full object-contain"
              style="max-height: 520px;"
            />
          </div>
          <div v-if="variantImages.length > 1" class="flex gap-2 overflow-x-auto pb-1">
            <button
              v-for="img in variantImages"
              :key="img.id"
              @click="activeImageUrl = img.image_url"
              :class="['flex-none rounded border-2 transition', activeImageUrl === img.image_url || (!activeImageUrl && img.is_primary) ? 'border-stone-900' : 'border-stone-200 hover:border-stone-400']"
            >
              <img :src="img.image_url" alt="" class="h-16 w-16 object-cover" />
            </button>
          </div>
        </div>

        <!-- Details -->
        <div class="space-y-6">
          <div>
            <p v-if="product.category_name" class="text-xs font-semibold uppercase tracking-[0.18em] text-stone-500">
              {{ product.category_name }}
            </p>
            <h1 class="mt-2 text-3xl text-stone-900 sm:text-4xl" style="font-family: Garamond, 'Times New Roman', serif">
              {{ product.name }}
            </h1>
            <p v-if="product.sku" class="mt-2 text-xs text-stone-400 font-mono">SKU: {{ product.sku }}</p>
          </div>

          <!-- Price -->
          <div class="flex items-baseline gap-3">
            <span class="text-3xl font-semibold text-stone-900">
              {{ formatPrice(displaySalePrice ?? displayPrice) }}
            </span>
            <span v-if="displaySalePrice != null" class="text-lg text-stone-400 line-through">
              {{ formatPrice(displayPrice) }}
            </span>
            <span v-if="displaySalePrice != null" class="rounded-full bg-rose-100 px-3 py-0.5 text-xs font-semibold text-rose-700">
              Sale
            </span>
          </div>

          <!-- Stock badge -->
          <div class="flex items-center gap-2">
            <span
              :class="[
                'inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold',
                inStock() ? 'bg-emerald-100 text-emerald-800' : 'bg-stone-100 text-stone-500'
              ]"
            >
              <span :class="['h-1.5 w-1.5 rounded-full', inStock() ? 'bg-emerald-500' : 'bg-stone-400']"></span>
              {{ inStock() ? stockLabel() || 'In Stock' : 'Out of Stock' }}
            </span>
          </div>

          <!-- Variant selector -->
          <div v-if="multipleVariants" class="space-y-2">
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-stone-500">
              {{ Object.keys(product.variants?.[0]?.options ?? {}).join(' / ') || 'Variant' }}
            </p>
            <div class="flex flex-wrap gap-2">
              <button
                v-for="v in product.variants"
                :key="v.id"
                @click="selectedVariant = v"
                :disabled="v.quantity === 0"
                :class="[
                  'rounded border px-4 py-2 text-sm transition',
                  selectedVariant?.id === v.id
                    ? 'border-stone-900 bg-stone-900 text-white'
                    : v.quantity === 0
                      ? 'border-stone-200 text-stone-300 cursor-not-allowed line-through'
                      : 'border-stone-300 text-stone-700 hover:border-stone-700'
                ]"
              >
                {{ variantLabel(v) }}
              </button>
            </div>
          </div>

          <!-- Short description -->
          <p v-if="product.short_description" class="text-stone-600 leading-relaxed">
            {{ product.short_description }}
          </p>

          <!-- Add to cart -->
          <div class="space-y-3">
            <div v-if="addError" class="rounded border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700">{{ addError }}</div>
            <div v-if="addSuccess" class="rounded border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-700">Added to cart!</div>
            <button
              @click="handleAdd"
              :disabled="adding || !inStock()"
              class="w-full bg-stone-900 py-4 text-sm font-semibold uppercase tracking-[0.1em] text-white transition hover:bg-stone-700 disabled:cursor-not-allowed disabled:bg-stone-300"
            >
              {{ adding ? 'Adding…' : !inStock() ? 'Out of Stock' : 'Add to Cart' }}
            </button>
            <router-link
              to="/cart"
              v-if="addSuccess"
              class="block w-full border border-stone-900 py-4 text-center text-sm font-semibold uppercase tracking-[0.1em] text-stone-900 transition hover:bg-stone-50"
            >
              View Cart
            </router-link>
          </div>

          <!-- Pincode checker -->
          <div class="border-t border-stone-100 pt-6">
            <p class="mb-2 text-xs font-semibold uppercase tracking-[0.16em] text-stone-500">Check Delivery</p>
            <div class="flex gap-2">
              <input
                v-model="pincodeInput"
                type="text"
                inputmode="numeric"
                maxlength="6"
                placeholder="Enter pincode"
                class="w-36 rounded border border-stone-200 px-3 py-2 text-sm focus:outline-none focus:border-stone-400"
                @keyup.enter="handlePincodeCheck"
              />
              <button
                @click="handlePincodeCheck"
                :disabled="pincodeChecking || pincodeInput.trim().length < 6"
                class="rounded border border-stone-900 px-4 py-2 text-sm font-semibold text-stone-900 transition hover:bg-stone-900 hover:text-white disabled:opacity-40"
              >
                {{ pincodeChecking ? 'Checking…' : 'Check' }}
              </button>
            </div>
            <p
              v-if="pincodeResult"
              class="mt-2 text-sm font-medium"
              :class="pincodeResult.serviceable ? 'text-emerald-600' : 'text-rose-600'"
            >
              {{ pincodeResult.serviceable ? '✓' : '✗' }} {{ pincodeResult.message }}
            </p>
          </div>

          <!-- Share -->
          <div class="border-t border-stone-100 pt-5">
            <p class="mb-3 text-xs font-semibold uppercase tracking-[0.16em] text-stone-500">Share</p>
            <div class="flex items-center gap-2">
              <!-- WhatsApp -->
              <button @click="shareWhatsApp" title="Share on WhatsApp" class="flex h-9 w-9 items-center justify-center rounded-full bg-[#25D366] text-white transition hover:opacity-85">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4">
                  <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                </svg>
              </button>
              <!-- Facebook -->
              <button @click="shareFacebook" title="Share on Facebook" class="flex h-9 w-9 items-center justify-center rounded-full bg-[#1877F2] text-white transition hover:opacity-85">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4">
                  <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                </svg>
              </button>
              <!-- Twitter / X -->
              <button @click="shareTwitter" title="Share on X" class="flex h-9 w-9 items-center justify-center rounded-full bg-black text-white transition hover:opacity-75">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4">
                  <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.744l7.737-8.835L1.254 2.25H8.08l4.259 5.63L18.244 2.25zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                </svg>
              </button>
              <!-- Copy link -->
              <button @click="copyLink" class="ml-1 flex items-center gap-1.5 rounded-full border border-stone-300 px-4 py-2 text-xs font-medium text-stone-700 transition hover:bg-stone-50">
                <svg v-if="!linkCopied" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-3.5 w-3.5">
                  <path d="M12.232 4.232a2.5 2.5 0 013.536 3.536l-1.225 1.224a.75.75 0 001.061 1.06l1.224-1.224a4 4 0 00-5.656-5.656l-3 3a4 4 0 00.225 5.865.75.75 0 00.977-1.138 2.5 2.5 0 01-.142-3.667l3-3z"/>
                  <path d="M11.603 7.963a.75.75 0 00-.977 1.138 2.5 2.5 0 01.142 3.667l-3 3a2.5 2.5 0 01-3.536-3.536l1.225-1.224a.75.75 0 00-1.061-1.06l-1.224 1.224a4 4 0 105.656 5.656l3-3a4 4 0 00-.225-5.865z"/>
                </svg>
                {{ linkCopied ? 'Copied!' : 'Copy link' }}
              </button>
            </div>
          </div>

          <!-- Full description -->
          <div v-if="product.description" class="border-t border-stone-100 pt-6">
            <p class="mb-3 text-xs font-semibold uppercase tracking-[0.16em] text-stone-500">Description</p>
            <div class="prose prose-stone max-w-none text-sm text-stone-600 leading-relaxed" v-html="product.description"></div>
          </div>
        </div>
      </div>
    </template>
  </section>
</template>
