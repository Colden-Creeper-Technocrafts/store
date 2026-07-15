<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useCartStore } from '../../stores/cart'
import { formatPrice, useStorefront } from '../../services/storefront'

const cartStore = useCartStore()
const { storeShippingCharge, storeFreeShippingThreshold } = useStorefront()

const shippingCost = computed(() => {
  const t = cartStore.total
  if (storeFreeShippingThreshold.value !== null && t >= storeFreeShippingThreshold.value) return 0
  return storeShippingCharge.value
})
const finalTotal = computed(() => cartStore.total + shippingCost.value)

onMounted(async () => {
  await cartStore.load()
})

const decrement = async (itemId: number, qty: number) => {
  if (qty <= 1) await cartStore.remove(itemId)
  else await cartStore.update(itemId, qty - 1).catch(() => {})
}

const increment = async (itemId: number, qty: number) => {
  if (qty < 99) await cartStore.update(itemId, qty + 1).catch(() => {})
}
</script>

<template>
  <section class="space-y-6">
    <div class="border border-emerald-200 bg-white p-8 text-emerald-950">
      <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Grocery Collection</p>
      <h1 class="mt-3 text-3xl">Your Cart</h1>
    </div>

    <div v-if="cartStore.loading && !cartStore.loaded" class="border border-emerald-200 bg-white p-8 text-center text-emerald-700">
      Loading cart...
    </div>

    <div v-else-if="cartStore.isEmpty" class="border border-emerald-200 bg-white p-12 text-center">
      <p class="text-2xl">🛒</p>
      <p class="mt-3 text-emerald-700">Your cart is empty.</p>
      <router-link to="/store" class="mt-6 inline-block border border-emerald-300 px-6 py-3 text-sm font-semibold text-emerald-900 transition hover:bg-emerald-50">
        Continue Shopping
      </router-link>
    </div>

    <template v-else>
      <div v-if="cartStore.error" class="border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700">
        {{ cartStore.error }}
      </div>

      <div class="grid gap-6 lg:grid-cols-[1fr_320px]">
        <div class="space-y-3">
          <div
            v-for="item in cartStore.items"
            :key="item.id"
            class="flex gap-4 border border-emerald-200 bg-white p-4"
          >
            <img
              :src="item.product.image_url"
              :alt="item.product.name"
              class="h-24 w-24 flex-shrink-0 object-cover"
            />
            <div class="flex flex-1 flex-col justify-between">
              <div>
                <p class="font-semibold text-emerald-950">{{ item.product.name }}</p>
                <p v-if="item.product.sku" class="text-xs text-emerald-400">{{ item.product.sku }}</p>
              </div>
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <button
                    @click="decrement(item.id, item.quantity)"
                    class="flex h-7 w-7 items-center justify-center border border-emerald-300 text-emerald-800 hover:bg-emerald-50"
                    :disabled="cartStore.loading"
                  >−</button>
                  <span class="w-8 text-center text-sm font-medium">{{ item.quantity }}</span>
                  <button
                    @click="increment(item.id, item.quantity)"
                    class="flex h-7 w-7 items-center justify-center border border-emerald-300 text-emerald-800 hover:bg-emerald-50 disabled:opacity-40 disabled:cursor-not-allowed"
                    :disabled="cartStore.loading || (item.product.stock != null && item.quantity >= item.product.stock)"
                  >+</button>
                </div>
                <div class="flex items-center gap-4">
                  <span class="font-semibold text-emerald-950">{{ formatPrice(item.line_total) }}</span>
                  <button
                    @click="cartStore.remove(item.id)"
                    class="text-xs text-rose-500 hover:underline"
                    :disabled="cartStore.loading"
                  >Remove</button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="border border-emerald-200 bg-white p-6 self-start space-y-4">
          <p class="font-semibold text-emerald-950 text-lg">Order Summary</p>
          <div class="flex justify-between text-sm text-emerald-700">
            <span>Subtotal ({{ cartStore.itemCount }} item{{ cartStore.itemCount !== 1 ? 's' : '' }})</span>
            <span>{{ formatPrice(cartStore.total) }}</span>
          </div>
          <div class="flex justify-between text-sm text-emerald-700">
            <span>Shipping</span>
            <span>{{ shippingCost === 0 ? 'FREE' : formatPrice(shippingCost) }}</span>
          </div>
          <div class="flex justify-between border-t border-emerald-100 pt-4 font-semibold text-emerald-950">
            <span>Total</span>
            <span>{{ formatPrice(finalTotal) }}</span>
          </div>
          <router-link
            to="/checkout"
            class="block w-full bg-emerald-800 py-3 text-center text-sm font-semibold text-white transition hover:bg-emerald-700"
          >
            Proceed to Checkout
          </router-link>
          <router-link to="/store" class="block w-full border border-emerald-300 py-3 text-center text-sm font-semibold text-emerald-900 transition hover:bg-emerald-50">
            Continue Shopping
          </router-link>
        </div>
      </div>
    </template>
  </section>
</template>
