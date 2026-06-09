<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { loadStoreProduct, type StorefrontProduct } from '../../services/storefront'
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

const inStock = () => product.value?.quantity == null || product.value.quantity > 0
const stockLabel = () => {
  const q = product.value?.quantity
  if (q == null) return ''
  if (q === 0) return 'Out of Stock'
  if (q <= 5) return `Only ${q} left`
  return 'In Stock'
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
        sku: p.sku ?? null,
        price: Number(p.price ?? 0),
        sale_price: p.sale_price != null ? Number(p.sale_price) : null,
        image_url: p.image ?? '/images/product-placeholder.svg',
        stock: p.quantity ?? null,
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
  if (!product.value) router.push('/store')
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
      <div class="grid gap-8 lg:grid-cols-[480px_1fr]">

        <!-- Image -->
        <div class="overflow-hidden border border-stone-200 bg-stone-50">
          <img
            :src="product.image ?? '/images/product-placeholder.svg'"
            :alt="product.name"
            class="h-full w-full object-cover"
            style="min-height: 320px; max-height: 520px;"
          />
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
              ₹{{ Number(product.sale_price ?? product.price ?? 0).toFixed(2) }}
            </span>
            <span v-if="product.sale_price != null" class="text-lg text-stone-400 line-through">
              ₹{{ Number(product.price ?? 0).toFixed(2) }}
            </span>
            <span v-if="product.sale_price != null" class="rounded-full bg-rose-100 px-3 py-0.5 text-xs font-semibold text-rose-700">
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
