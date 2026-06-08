<script setup lang="ts">
import { onMounted, reactive, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import { useCartStore } from '../../stores/cart'
import { placeOrder, placeGuestOrder, calculateShipping } from '../../services/orders'
import type { ShippingRate } from '../../services/orders'
import { validateCoupon } from '../../services/coupon'
import type { CouponValidation } from '../../services/coupon'

const router = useRouter()
const authStore = useAuthStore()
const cartStore = useCartStore()

const submitting = ref(false)
const errorMessage = ref('')

const couponCode = ref('')
const couponValidating = ref(false)
const couponError = ref('')
const appliedCoupon = ref<CouponValidation | null>(null)

const shippingRates = ref<ShippingRate[]>([])
const loadingRates = ref(false)
const selectedRate = ref<ShippingRate | null>(null)

const form = reactive({
  shipping_name: authStore.user?.name ?? '',
  shipping_email: authStore.user?.email ?? '',
  shipping_phone: '',
  shipping_address: '',
  shipping_city: '',
  shipping_state: '',
  shipping_postal_code: '',
  shipping_country: 'India',
  notes: '',
})

onMounted(async () => {
  if (!cartStore.loaded) await cartStore.load()
  if (cartStore.isEmpty) router.push('/cart')
})

let rateTimer: ReturnType<typeof setTimeout> | null = null

const fetchRates = async () => {
  const pincode = form.shipping_postal_code.trim()
  if (pincode.length < 5) {
    shippingRates.value = []
    selectedRate.value = null
    return
  }
  loadingRates.value = true
  try {
    const rates = await calculateShipping({
      pincode,
      state: form.shipping_state.trim() || undefined,
      order_amount: cartStore.total,
    })
    shippingRates.value = rates
    if (rates.length > 0 && (!selectedRate.value || !rates.find(r => r.method_id === selectedRate.value!.method_id))) {
      selectedRate.value = rates[0]
    } else if (rates.length === 0) {
      selectedRate.value = null
    }
  } catch {
    shippingRates.value = []
    selectedRate.value = null
  } finally {
    loadingRates.value = false
  }
}

watch([() => form.shipping_postal_code, () => form.shipping_state], () => {
  if (rateTimer) clearTimeout(rateTimer)
  rateTimer = setTimeout(fetchRates, 600)
})

const applyCoupon = async () => {
  const code = couponCode.value.trim()
  if (!code) return
  couponValidating.value = true
  couponError.value = ''
  appliedCoupon.value = null
  try {
    appliedCoupon.value = await validateCoupon(code, cartStore.total)
  } catch (e: any) {
    const errors = e?.response?.data?.errors
    couponError.value = errors
      ? Object.values(errors).flat().join(' ')
      : e?.response?.data?.message ?? 'Invalid coupon.'
  } finally {
    couponValidating.value = false
  }
}

const removeCoupon = () => {
  appliedCoupon.value = null
  couponCode.value = ''
  couponError.value = ''
}

const shippingCost = () => selectedRate.value?.cost ?? 0

const finalTotal = () => {
  const base = cartStore.total - (appliedCoupon.value?.discount_amount ?? 0)
  return Math.max(0, base) + shippingCost()
}

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
      shipping_state: form.shipping_state || null,
      shipping_postal_code: form.shipping_postal_code,
      shipping_country: form.shipping_country,
      notes: form.notes || null,
      coupon_code: appliedCoupon.value?.code ?? null,
      shipping_method_id: selectedRate.value?.method_id ?? null,
    }

    if (authStore.isCustomer) {
      const order = await placeOrder(shipping)
      await cartStore.load()
      router.push({ path: '/orders', query: { placed: String(order.id) } })
    } else {
      const order = await placeGuestOrder({ ...shipping, items: cartStore.guestOrderItems })
      await cartStore.clear()
      router.push({ path: '/orders', query: { placed: String(order.id), guest: '1' } })
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
    <div class="border border-emerald-200 bg-white p-8 text-emerald-950">
      <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Grocery Collection</p>
      <h1 class="mt-3 text-3xl">Checkout</h1>
    </div>

    <div class="grid gap-6 lg:grid-cols-[1fr_320px]">
      <div class="border border-emerald-200 bg-white p-6">
        <p class="mb-6 font-semibold text-emerald-950">Shipping Details</p>

        <div v-if="errorMessage" class="mb-4 rounded bg-rose-50 border border-rose-200 p-3 text-sm text-rose-700">
          {{ errorMessage }}
        </div>

        <form @submit.prevent="submit" class="space-y-4">
          <div class="grid gap-4 sm:grid-cols-2">
            <div>
              <label class="block text-sm font-medium text-emerald-800">Full Name</label>
              <input v-model="form.shipping_name" required type="text"
                class="mt-1 w-full border border-emerald-200 px-3 py-2 text-sm focus:outline-none focus:border-emerald-500" />
            </div>
            <div>
              <label class="block text-sm font-medium text-emerald-800">Email</label>
              <input v-model="form.shipping_email" required type="email"
                class="mt-1 w-full border border-emerald-200 px-3 py-2 text-sm focus:outline-none focus:border-emerald-500" />
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-emerald-800">Phone <span class="font-normal text-emerald-400">(optional)</span></label>
            <input v-model="form.shipping_phone" type="tel"
              class="mt-1 w-full border border-emerald-200 px-3 py-2 text-sm focus:outline-none focus:border-emerald-500" />
          </div>

          <div>
            <label class="block text-sm font-medium text-emerald-800">Address</label>
            <input v-model="form.shipping_address" required type="text"
              class="mt-1 w-full border border-emerald-200 px-3 py-2 text-sm focus:outline-none focus:border-emerald-500" />
          </div>

          <div class="grid gap-4 sm:grid-cols-2">
            <div>
              <label class="block text-sm font-medium text-emerald-800">City</label>
              <input v-model="form.shipping_city" required type="text"
                class="mt-1 w-full border border-emerald-200 px-3 py-2 text-sm focus:outline-none focus:border-emerald-500" />
            </div>
            <div>
              <label class="block text-sm font-medium text-emerald-800">State</label>
              <input v-model="form.shipping_state" type="text" placeholder="e.g. Maharashtra"
                class="mt-1 w-full border border-emerald-200 px-3 py-2 text-sm focus:outline-none focus:border-emerald-500" />
            </div>
          </div>

          <div class="grid gap-4 sm:grid-cols-2">
            <div>
              <label class="block text-sm font-medium text-emerald-800">Postal Code</label>
              <input v-model="form.shipping_postal_code" required type="text"
                class="mt-1 w-full border border-emerald-200 px-3 py-2 text-sm focus:outline-none focus:border-emerald-500" />
            </div>
            <div>
              <label class="block text-sm font-medium text-emerald-800">Country</label>
              <input v-model="form.shipping_country" required type="text"
                class="mt-1 w-full border border-emerald-200 px-3 py-2 text-sm focus:outline-none focus:border-emerald-500" />
            </div>
          </div>

          <!-- Shipping Method -->
          <div>
            <p class="text-sm font-medium text-emerald-800 mb-2">Shipping Method</p>
            <div v-if="loadingRates" class="text-sm text-emerald-400">Loading shipping options…</div>
            <div v-else-if="shippingRates.length > 0" class="space-y-2">
              <label
                v-for="rate in shippingRates"
                :key="rate.method_id"
                class="flex items-center gap-3 border p-3 cursor-pointer transition"
                :class="selectedRate?.method_id === rate.method_id
                  ? 'border-emerald-700 bg-emerald-50'
                  : 'border-emerald-200 hover:border-emerald-400'"
              >
                <input
                  type="radio"
                  :value="rate.method_id"
                  :checked="selectedRate?.method_id === rate.method_id"
                  @change="selectedRate = rate"
                  class="accent-emerald-700"
                />
                <span class="flex-1 text-sm text-emerald-900">
                  {{ rate.method_name }}
                  <span v-if="rate.delivery_estimate" class="text-emerald-400 ml-1">({{ rate.delivery_estimate }})</span>
                </span>
                <span class="text-sm font-semibold text-emerald-900">
                  {{ rate.is_free ? 'FREE' : `₹${rate.cost.toFixed(2)}` }}
                </span>
              </label>
            </div>
            <p v-else-if="form.shipping_postal_code.length >= 5" class="text-sm text-emerald-400">
              No shipping options available for this pincode.
            </p>
            <p v-else class="text-sm text-emerald-400">Enter postal code to see shipping options.</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-emerald-800">Notes <span class="font-normal text-emerald-400">(optional)</span></label>
            <textarea v-model="form.notes" rows="2"
              class="mt-1 w-full border border-emerald-200 px-3 py-2 text-sm focus:outline-none focus:border-emerald-500"></textarea>
          </div>

          <button
            type="submit"
            :disabled="submitting || cartStore.isEmpty"
            class="w-full bg-emerald-800 py-3 text-sm font-semibold text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:bg-emerald-300"
          >
            {{ submitting ? 'Placing order…' : 'Place Order' }}
          </button>
        </form>
      </div>

      <div class="space-y-4 self-start">
        <!-- Coupon -->
        <div class="border border-emerald-200 bg-white p-4 space-y-3">
          <p class="text-sm font-semibold text-emerald-950">Coupon Code</p>
          <div v-if="appliedCoupon" class="flex items-center justify-between rounded bg-emerald-50 border border-emerald-200 px-3 py-2">
            <span class="font-mono text-sm font-semibold text-emerald-800">{{ appliedCoupon.code }}</span>
            <button @click="removeCoupon" class="text-xs text-rose-500 hover:text-rose-700">Remove</button>
          </div>
          <div v-else class="flex gap-2">
            <input
              v-model="couponCode"
              type="text"
              placeholder="Enter code"
              class="flex-1 border border-emerald-200 px-3 py-2 text-sm uppercase tracking-wider focus:outline-none focus:border-emerald-500"
              @keyup.enter="applyCoupon"
            />
            <button
              type="button"
              @click="applyCoupon"
              :disabled="couponValidating || !couponCode.trim()"
              class="bg-emerald-800 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 transition disabled:opacity-50"
            >
              {{ couponValidating ? '…' : 'Apply' }}
            </button>
          </div>
          <p v-if="couponError" class="text-xs text-rose-600">{{ couponError }}</p>
        </div>

        <!-- Order Summary -->
        <div class="border border-emerald-200 bg-white p-6 space-y-4">
          <p class="font-semibold text-emerald-950">Order Summary</p>
          <div v-for="item in cartStore.items" :key="item.id" class="flex justify-between text-sm text-emerald-700">
            <span class="truncate pr-2">{{ item.product.name }} × {{ item.quantity }}</span>
            <span class="flex-shrink-0">₹{{ item.line_total.toFixed(2) }}</span>
          </div>
          <div class="border-t border-emerald-100 pt-3 space-y-2">
            <div class="flex justify-between text-sm text-emerald-700">
              <span>Subtotal</span>
              <span>₹{{ cartStore.total.toFixed(2) }}</span>
            </div>
            <div v-if="appliedCoupon" class="flex justify-between text-sm text-emerald-600">
              <span>Discount ({{ appliedCoupon.code }})</span>
              <span class="text-emerald-700">−₹{{ appliedCoupon.discount_amount.toFixed(2) }}</span>
            </div>
            <div class="flex justify-between text-sm text-emerald-700">
              <span>Shipping</span>
              <span v-if="selectedRate">
                {{ selectedRate.is_free ? 'FREE' : `₹${selectedRate.cost.toFixed(2)}` }}
              </span>
              <span v-else class="text-emerald-300">—</span>
            </div>
            <div class="flex justify-between pt-1 font-semibold text-emerald-950">
              <span>Total</span>
              <span>₹{{ finalTotal().toFixed(2) }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>
