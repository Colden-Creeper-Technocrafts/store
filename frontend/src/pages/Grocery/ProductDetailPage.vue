<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
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

const displayImage = computed(() => {
  const defaultV = product.value?.variants?.find(v => v.is_default) ?? product.value?.variants?.[0]
  const imgs = defaultV?.images ?? []
  return imgs.find(img => img.is_primary)?.image_url ?? imgs[0]?.image_url ?? product.value?.image ?? '/images/product-placeholder.svg'
})

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
        image_url: displayImage.value,
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
    <nav class="flex items-center gap-2 text-xs text-emerald-700">
      <router-link to="/" class="hover:text-emerald-900 transition">Home</router-link>
      <span>/</span>
      <router-link to="/store" class="hover:text-emerald-900 transition">Store</router-link>
      <span>/</span>
      <span class="text-emerald-900">{{ product?.name ?? '…' }}</span>
    </nav>

    <div v-if="loading" class="border border-emerald-200 bg-white p-16 text-center text-emerald-700">
      Loading product…
    </div>

    <template v-else-if="product">
      <div class="grid gap-8 lg:grid-cols-[480px_1fr]">
        <div class="aspect-[3/4] w-full overflow-hidden rounded-2xl border border-emerald-100 bg-emerald-50">
          <img
            :src="displayImage"
            :alt="product.name"
            class="h-full w-full object-cover"
          />
        </div>

        <div class="space-y-6">
          <div>
            <p v-if="product.category_name" class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-600">
              {{ product.category_name }}
            </p>
            <h1 class="mt-2 text-3xl font-bold text-emerald-950 sm:text-4xl">{{ product.name }}</h1>
            <p v-if="product.sku" class="mt-2 text-xs text-stone-400 font-mono">SKU: {{ product.sku }}</p>
          </div>

          <div class="flex items-baseline gap-3">
            <span class="text-3xl font-bold text-emerald-800">
              ₹{{ Number(product.sale_price ?? product.price ?? 0).toFixed(2) }}
            </span>
            <span v-if="product.sale_price != null" class="text-lg text-stone-400 line-through">
              ₹{{ Number(product.price ?? 0).toFixed(2) }}
            </span>
            <span v-if="product.sale_price != null" class="rounded-full bg-rose-100 px-3 py-0.5 text-xs font-semibold text-rose-700">Sale</span>
          </div>

          <div>
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

          <p v-if="product.short_description" class="text-stone-600 leading-relaxed">{{ product.short_description }}</p>

          <div class="space-y-3">
            <div v-if="addError" class="rounded border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700">{{ addError }}</div>
            <div v-if="addSuccess" class="rounded border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-700">Added to cart!</div>
            <button
              @click="handleAdd"
              :disabled="adding || !inStock()"
              class="w-full rounded-xl bg-emerald-700 py-4 text-sm font-semibold text-white transition hover:bg-emerald-800 disabled:cursor-not-allowed disabled:bg-stone-300"
            >
              {{ adding ? 'Adding…' : !inStock() ? 'Out of Stock' : 'Add to Cart' }}
            </button>
            <router-link to="/cart" v-if="addSuccess"
              class="block w-full rounded-xl border border-emerald-700 py-4 text-center text-sm font-semibold text-emerald-800 transition hover:bg-emerald-50">
              View Cart
            </router-link>
          </div>

          <div v-if="product.description" class="border-t border-stone-100 pt-6">
            <p class="mb-3 text-xs font-semibold uppercase tracking-[0.16em] text-stone-500">Description</p>
            <div class="text-sm text-stone-600 leading-relaxed" v-html="product.description"></div>
          </div>
        </div>
      </div>
    </template>
  </section>
</template>
