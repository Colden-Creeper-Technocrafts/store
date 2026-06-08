<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'

import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import { useCartStore } from '../../stores/cart'
import { placeOrder, placeGuestOrder } from '../../services/orders'

const router = useRouter()
const authStore = useAuthStore()
const cartStore = useCartStore()

const submitting = ref(false)
const errorMessage = ref('')

const form = reactive({
  shipping_name: authStore.user?.name ?? '',
  shipping_email: authStore.user?.email ?? '',
  shipping_phone: '',
  shipping_address: '',
  shipping_city: '',
  shipping_postal_code: '',
  shipping_country: 'India',
  notes: '',
})

onMounted(async () => {
  if (!cartStore.loaded) await cartStore.load()
  if (cartStore.isEmpty) router.push('/cart')
})

const submit = async () => {
  submitting.value = true
  errorMessage.value = ''
  try {
    const shipping = {
      shipping_name: form.shipping_name,
      shipping_email: form.shipping_email,
      shipping_phone: form.shipping_phone || null,
      shipping_address: form.shipping_address,
      shipping_city: form.shipping_city,
      shipping_postal_code: form.shipping_postal_code,
      shipping_country: form.shipping_country,
      notes: form.notes || null,
    }

    let orderId: number
    if (authStore.isCustomer) {
      const order = await placeOrder(shipping)
      orderId = order.id
      await cartStore.load()
      router.push({ path: '/orders', query: { placed: String(orderId) } })
    } else {
      const order = await placeGuestOrder({ ...shipping, items: cartStore.guestOrderItems })
      orderId = order.id
      await cartStore.clear()
      router.push({ path: '/orders', query: { placed: String(orderId), guest: '1' } })
    }
  } catch (e: any) {
    const errors = e?.response?.data?.errors
    if (errors) {
      errorMessage.value = Object.values(errors).flat().join(' ')
    } else {
      errorMessage.value = e?.response?.data?.message ?? 'Unable to place order. Please try again.'
    }
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <section class="space-y-6">
    <div class="border border-stone-200 bg-white p-8">
      <p class="text-xs font-semibold uppercase tracking-[0.18em] text-stone-500">Ladies Collection</p>
      <h1 class="mt-3 text-3xl text-stone-900">Checkout</h1>
    </div>

    <div class="grid gap-6 lg:grid-cols-[1fr_320px]">
      <div class="border border-stone-200 bg-white p-6">
        <p class="mb-6 font-semibold text-stone-900">Shipping Details</p>

        <div v-if="errorMessage" class="mb-4 rounded bg-rose-50 border border-rose-200 p-3 text-sm text-rose-700">
          {{ errorMessage }}
        </div>

        <form @submit.prevent="submit" class="space-y-4">
          <div class="grid gap-4 sm:grid-cols-2">
            <div>
              <label class="block text-sm font-medium text-stone-700">Full Name</label>
              <input v-model="form.shipping_name" required type="text"
                class="mt-1 w-full border border-stone-300 px-3 py-2 text-sm focus:outline-none focus:border-stone-500" />
            </div>
            <div>
              <label class="block text-sm font-medium text-stone-700">Email</label>
              <input v-model="form.shipping_email" required type="email"
                class="mt-1 w-full border border-stone-300 px-3 py-2 text-sm focus:outline-none focus:border-stone-500" />
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-stone-700">Phone <span class="font-normal text-stone-400">(optional)</span></label>
            <input v-model="form.shipping_phone" type="tel"
              class="mt-1 w-full border border-stone-300 px-3 py-2 text-sm focus:outline-none focus:border-stone-500" />
          </div>

          <div>
            <label class="block text-sm font-medium text-stone-700">Address</label>
            <input v-model="form.shipping_address" required type="text"
              class="mt-1 w-full border border-stone-300 px-3 py-2 text-sm focus:outline-none focus:border-stone-500" />
          </div>

          <div class="grid gap-4 sm:grid-cols-3">
            <div>
              <label class="block text-sm font-medium text-stone-700">City</label>
              <input v-model="form.shipping_city" required type="text"
                class="mt-1 w-full border border-stone-300 px-3 py-2 text-sm focus:outline-none focus:border-stone-500" />
            </div>
            <div>
              <label class="block text-sm font-medium text-stone-700">Postal Code</label>
              <input v-model="form.shipping_postal_code" required type="text"
                class="mt-1 w-full border border-stone-300 px-3 py-2 text-sm focus:outline-none focus:border-stone-500" />
            </div>
            <div>
              <label class="block text-sm font-medium text-stone-700">Country</label>
              <input v-model="form.shipping_country" required type="text"
                class="mt-1 w-full border border-stone-300 px-3 py-2 text-sm focus:outline-none focus:border-stone-500" />
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-stone-700">Notes <span class="font-normal text-stone-400">(optional)</span></label>
            <textarea v-model="form.notes" rows="2"
              class="mt-1 w-full border border-stone-300 px-3 py-2 text-sm focus:outline-none focus:border-stone-500"></textarea>
          </div>

          <button
            type="submit"
            :disabled="submitting || cartStore.isEmpty"
            class="w-full bg-stone-900 py-3 text-sm font-semibold text-white transition hover:bg-stone-700 disabled:cursor-not-allowed disabled:bg-stone-400"
          >
            {{ submitting ? 'Placing order…' : 'Place Order' }}
          </button>
        </form>
      </div>

      <div class="border border-stone-200 bg-white p-6 self-start space-y-4">
        <p class="font-semibold text-stone-900">Order Summary</p>
        <div v-for="item in cartStore.items" :key="item.id" class="flex justify-between text-sm text-stone-600">
          <span class="truncate pr-2">{{ item.product.name }} × {{ item.quantity }}</span>
          <span class="flex-shrink-0">${{ item.line_total.toFixed(2) }}</span>
        </div>
        <div class="flex justify-between border-t border-stone-100 pt-4 font-semibold text-stone-900">
          <span>Total</span>
          <span>${{ cartStore.total.toFixed(2) }}</span>
        </div>
      </div>
    </div>
  </section>
</template>
