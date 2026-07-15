<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import { useCartStore } from '../../stores/cart'
import { placeOrder, placeGuestOrder } from '../../services/orders'
import { useStorefront } from '../../services/storefront'
import {
  createRazorpayOrder,
  openRazorpayModal,
  verifyRazorpayPayment,
  PaymentCancelledError,
} from '../../services/payment'
import { validateCoupon } from '../../services/coupon'
import type { CouponValidation } from '../../services/coupon'

const router = useRouter()
const authStore = useAuthStore()
const cartStore = useCartStore()

const submitting = ref(false)
const paymentStep = ref<'idle' | 'creating' | 'paying' | 'verifying'>('idle')
const errorMessage = ref('')

const couponCode = ref('')
const couponValidating = ref(false)
const couponError = ref('')
const appliedCoupon = ref<CouponValidation | null>(null)

const { storeShippingCharge, storeFreeShippingThreshold } = useStorefront()

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

const applyCoupon = async () => {
  const code = couponCode.value.trim()
  if (!code) return
  couponValidating.value = true; couponError.value = ''; appliedCoupon.value = null
  try {
    appliedCoupon.value = await validateCoupon(code, cartStore.total)
  } catch (e: any) {
    const errors = e?.response?.data?.errors
    couponError.value = errors ? Object.values(errors).flat().join(' ') : e?.response?.data?.message ?? 'Invalid coupon.'
  } finally { couponValidating.value = false }
}

const removeCoupon = () => { appliedCoupon.value = null; couponCode.value = ''; couponError.value = '' }

const shippingCost = computed(() => {
  const t = cartStore.total
  if (storeFreeShippingThreshold.value !== null && t >= storeFreeShippingThreshold.value) return 0
  return storeShippingCharge.value
})
const finalTotal = computed(() => Math.max(0, cartStore.total - (appliedCoupon.value?.discount_amount ?? 0)) + shippingCost.value)

const stepLabel = () => {
  if (paymentStep.value === 'creating') return 'Creating order…'
  if (paymentStep.value === 'paying') return 'Waiting for payment…'
  if (paymentStep.value === 'verifying') return 'Verifying payment…'
  return 'Pay with Razorpay'
}

const submit = async () => {
  submitting.value = true
  errorMessage.value = ''
  try {
    const shippingPayload = {
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
    }

    // Step 1 — create order in our DB (pending / unpaid)
    paymentStep.value = 'creating'
    let order
    if (authStore.isCustomer) {
      order = await placeOrder(shippingPayload)
    } else {
      order = await placeGuestOrder({ ...shippingPayload, items: cartStore.guestOrderItems })
    }

    // Step 2 — create Razorpay order + open modal
    paymentStep.value = 'paying'
    const rzpOptions = await createRazorpayOrder(order.id)
    const payment = await openRazorpayModal(rzpOptions)

    // Step 3 — verify signature server-side
    paymentStep.value = 'verifying'
    await verifyRazorpayPayment({
      order_id: order.id,
      razorpay_payment_id: payment.razorpay_payment_id,
      razorpay_order_id: payment.razorpay_order_id,
      razorpay_signature: payment.razorpay_signature,
    })

    // Success
    if (authStore.isCustomer) {
      await cartStore.load()
    } else {
      await cartStore.clear()
    }
    router.push({ path: '/orders', query: { placed: String(order.id) } })
  } catch (e: any) {
    if (e instanceof PaymentCancelledError) {
      errorMessage.value = 'Payment was cancelled. Your order has been saved — you can retry payment from your orders page.'
    } else {
      const errors = e?.response?.data?.errors
      errorMessage.value = errors
        ? Object.values(errors).flat().join(' ')
        : e?.response?.data?.message ?? e?.message ?? 'Unable to complete payment. Please try again.'
    }
  } finally {
    submitting.value = false
    paymentStep.value = 'idle'
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


          <div>
            <label class="block text-sm font-medium text-emerald-800">Notes <span class="font-normal text-emerald-400">(optional)</span></label>
            <textarea v-model="form.notes" rows="2"
              class="mt-1 w-full border border-emerald-200 px-3 py-2 text-sm focus:outline-none focus:border-emerald-500"></textarea>
          </div>

          <button type="submit" :disabled="submitting || cartStore.isEmpty"
            class="w-full bg-emerald-800 py-3 text-sm font-semibold text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:bg-emerald-300 flex items-center justify-center gap-2">
            <svg v-if="submitting" class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
            </svg>
            {{ stepLabel() }}
          </button>

          <p class="text-center text-xs text-emerald-400">
            Secured by
            <span class="font-semibold text-emerald-600">Razorpay</span>
            — UPI · Cards · Net Banking · Wallets
          </p>
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
            <input v-model="couponCode" type="text" placeholder="Enter code"
              class="flex-1 border border-emerald-200 px-3 py-2 text-sm uppercase tracking-wider focus:outline-none focus:border-emerald-500"
              @keyup.enter="applyCoupon" />
            <button type="button" @click="applyCoupon" :disabled="couponValidating || !couponCode.trim()"
              class="bg-emerald-800 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 transition disabled:opacity-50">
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
              <span>{{ shippingCost === 0 ? 'FREE' : `₹${shippingCost.toFixed(2)}` }}</span>
            </div>
            <div class="flex justify-between pt-1 font-semibold text-emerald-950">
              <span>Total</span>
              <span>₹{{ finalTotal.toFixed(2) }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>
