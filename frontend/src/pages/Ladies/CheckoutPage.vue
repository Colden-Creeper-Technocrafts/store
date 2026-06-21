<script setup lang="ts">
import { onMounted, reactive, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import { useCartStore } from '../../stores/cart'
import { placeOrder, placeGuestOrder, calculateShipping } from '../../services/orders'
import type { ShippingRate } from '../../services/orders'
import {
  createRazorpayOrder,
  openRazorpayModal,
  verifyRazorpayPayment,
  PaymentCancelledError,
} from '../../services/payment'
import { validateCoupon } from '../../services/coupon'
import type { CouponValidation } from '../../services/coupon'
import { sendOrderOtp, verifyOrderOtp } from '../../services/otp'
import { formatPrice } from '../../services/storefront'
import { fetchAddresses, type UserAddress } from '../../services/addresses'

const router = useRouter()
const authStore = useAuthStore()
const cartStore = useCartStore()

const submitting = ref(false)
const paymentStep = ref<'idle' | 'creating' | 'paying' | 'verifying'>('idle')
const errorMessage = ref('')

// Post-payment success state (guest checkout)
const orderPlaced = ref(false)
const isNewUser = ref(false)
const placedOrderId = ref<number | null>(null)

// OTP verification state (guest orders only)
const otpModal = ref(false)
const otpPhone = ref('')
const otpEmail = ref('')
const otpOrderId = ref(0)
const otpCode = ref('')
const otpSending = ref(false)
const otpVerifying = ref(false)
const otpError = ref('')
const otpSuccess = ref('')

// Holds the pending order so submitOtp can proceed to payment after OTP
let pendingOrder: { id: number } | null = null

const resendOtp = async () => {
  otpSending.value = true; otpError.value = ''
  try { await sendOrderOtp(otpPhone.value, otpEmail.value, otpOrderId.value) }
  catch { otpError.value = 'Failed to resend OTP.' }
  finally { otpSending.value = false }
}

const submitOtp = async () => {
  if (otpCode.value.length !== 6) return
  otpVerifying.value = true; otpError.value = ''
  try {
    // Verify OTP — registers account in background, no auto-login
    const result = await verifyOrderOtp(otpPhone.value, otpCode.value)
    otpModal.value = false

    if (!pendingOrder) return
    const order = pendingOrder
    submitting.value = true
    paymentStep.value = 'paying'
    const rzpOptions = await createRazorpayOrder(order.id)
    const payment = await openRazorpayModal(rzpOptions)

    paymentStep.value = 'verifying'
    await verifyRazorpayPayment({
      order_id: order.id,
      razorpay_payment_id: payment.razorpay_payment_id,
      razorpay_order_id: payment.razorpay_order_id,
      razorpay_signature: payment.razorpay_signature,
    })

    await cartStore.clear()
    placedOrderId.value = order.id
    isNewUser.value = result.is_new_user
    orderPlaced.value = true
  } catch (e: any) {
    if (e instanceof PaymentCancelledError) {
      otpError.value = 'Payment cancelled. Your order is saved — you can retry from your orders page after logging in.'
    } else {
      otpError.value = e?.response?.data?.message ?? 'Something went wrong.'
    }
  } finally {
    otpVerifying.value = false
    submitting.value = false
    paymentStep.value = 'idle'
  }
}

// Saved addresses (logged-in users)
const savedAddresses = ref<UserAddress[]>([])
const selectedAddressId = ref<number | null>(null)

const applySavedAddress = (addr: UserAddress) => {
  selectedAddressId.value = addr.id
  form.shipping_name    = addr.name
  form.shipping_phone   = addr.phone ?? ''
  form.shipping_address = addr.address
  form.shipping_city    = addr.city
  form.shipping_state   = addr.state ?? ''
  form.shipping_postal_code = addr.postal_code
  form.shipping_country = addr.country
}

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
  if (authStore.isCustomer) {
    savedAddresses.value = await fetchAddresses().catch(() => [])
    const def = savedAddresses.value.find(a => a.is_default) ?? savedAddresses.value[0]
    if (def) applySavedAddress(def)
  }
})

let rateTimer: ReturnType<typeof setTimeout> | null = null

const fetchRates = async () => {
  const pincode = form.shipping_postal_code.trim()
  if (pincode.length < 5) { shippingRates.value = []; selectedRate.value = null; return }
  loadingRates.value = true
  try {
    const rates = await calculateShipping({ pincode, state: form.shipping_state.trim() || undefined, order_amount: cartStore.total })
    shippingRates.value = rates
    const currentId = selectedRate.value?.method_id
    selectedRate.value = rates.find(r => r.method_id === currentId) ?? rates.find(() => true) ?? null
  } catch { shippingRates.value = []; selectedRate.value = null }
  finally { loadingRates.value = false }
}

watch([() => form.shipping_postal_code, () => form.shipping_state], () => {
  if (rateTimer) clearTimeout(rateTimer)
  rateTimer = setTimeout(fetchRates, 600)
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

const shippingCost = () => selectedRate.value?.cost ?? 0
const finalTotal = () => Math.max(0, cartStore.total - (appliedCoupon.value?.discount_amount ?? 0)) + shippingCost()

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
      shipping_method_id: selectedRate.value?.method_id ?? null,
    }

    // Step 1 — create order in DB (pending / unpaid)
    paymentStep.value = 'creating'
    let order
    if (authStore.isCustomer) {
      order = await placeOrder(shippingPayload)
    } else {
      order = await placeGuestOrder({ ...shippingPayload, items: cartStore.guestOrderItems })
    }

    // Step 2 — for guest orders with phone: verify mobile BEFORE payment
    if (!authStore.isCustomer && form.shipping_phone) {
      pendingOrder      = order
      otpPhone.value    = form.shipping_phone
      otpEmail.value    = form.shipping_email
      otpOrderId.value  = order.id
      await sendOrderOtp(form.shipping_phone, form.shipping_email, order.id)
      otpModal.value = true
      return  // payment continues inside submitOtp() after OTP verified
    }

    // Step 3 — authenticated users go straight to payment
    paymentStep.value = 'paying'
    const rzpOptions = await createRazorpayOrder(order.id)
    const payment = await openRazorpayModal(rzpOptions)

    paymentStep.value = 'verifying'
    await verifyRazorpayPayment({
      order_id: order.id,
      razorpay_payment_id: payment.razorpay_payment_id,
      razorpay_order_id: payment.razorpay_order_id,
      razorpay_signature: payment.razorpay_signature,
    })

    await cartStore.load()
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

        <!-- Saved address picker (logged-in users) -->
        <div v-if="savedAddresses.length > 0" class="mb-6 space-y-2">
          <p class="text-xs font-semibold uppercase tracking-[0.16em] text-stone-500">Saved Addresses</p>
          <div class="grid gap-2 sm:grid-cols-2">
            <button
              v-for="addr in savedAddresses"
              :key="addr.id"
              type="button"
              @click="applySavedAddress(addr)"
              :class="[
                'text-left border p-3 text-sm transition',
                selectedAddressId === addr.id
                  ? 'border-stone-900 bg-stone-50'
                  : 'border-stone-200 hover:border-stone-400'
              ]"
            >
              <span class="font-semibold text-stone-900 block">{{ addr.name }}</span>
              <span class="text-stone-500 text-xs">{{ addr.address }}, {{ addr.city }} — {{ addr.postal_code }}</span>
              <span v-if="addr.is_default" class="ml-1 text-[10px] font-bold uppercase text-stone-400">(Default)</span>
            </button>
          </div>
          <button type="button" @click="selectedAddressId = null"
            class="text-xs text-stone-400 hover:text-stone-700 underline underline-offset-2">
            Enter a different address
          </button>
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

          <div class="grid gap-4 sm:grid-cols-2">
            <div>
              <label class="block text-sm font-medium text-stone-700">City</label>
              <input v-model="form.shipping_city" required type="text"
                class="mt-1 w-full border border-stone-300 px-3 py-2 text-sm focus:outline-none focus:border-stone-500" />
            </div>
            <div>
              <label class="block text-sm font-medium text-stone-700">State</label>
              <input v-model="form.shipping_state" type="text" placeholder="e.g. Maharashtra"
                class="mt-1 w-full border border-stone-300 px-3 py-2 text-sm focus:outline-none focus:border-stone-500" />
            </div>
          </div>

          <div class="grid gap-4 sm:grid-cols-2">
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

          <!-- Shipping Method -->
          <div>
            <p class="text-sm font-medium text-stone-700 mb-2">Shipping Method</p>
            <div v-if="loadingRates" class="text-sm text-stone-400">Loading shipping options…</div>
            <div v-else-if="shippingRates.length > 0" class="space-y-2">
              <label
                v-for="rate in shippingRates" :key="rate.method_id"
                class="flex items-center gap-3 border p-3 cursor-pointer transition"
                :class="selectedRate?.method_id === rate.method_id ? 'border-stone-900 bg-stone-50' : 'border-stone-200 hover:border-stone-400'"
              >
                <input type="radio" :value="rate.method_id" :checked="selectedRate?.method_id === rate.method_id"
                  @change="selectedRate = rate" class="accent-stone-900" />
                <span class="flex-1 text-sm text-stone-800">
                  {{ rate.method_name }}
                  <span v-if="rate.delivery_estimate" class="text-stone-400 ml-1">({{ rate.delivery_estimate }})</span>
                </span>
                <span class="text-sm font-semibold text-stone-900">
                  {{ rate.is_free ? 'FREE' : formatPrice(rate.cost) }}
                </span>
              </label>
            </div>
            <p v-else-if="form.shipping_postal_code.length >= 5" class="text-sm text-stone-400">No shipping options available for this pincode.</p>
            <p v-else class="text-sm text-stone-400">Enter postal code to see shipping options.</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-stone-700">Notes <span class="font-normal text-stone-400">(optional)</span></label>
            <textarea v-model="form.notes" rows="2"
              class="mt-1 w-full border border-stone-300 px-3 py-2 text-sm focus:outline-none focus:border-stone-500"></textarea>
          </div>

          <button type="submit" :disabled="submitting"
            class="w-full bg-stone-900 py-3 text-sm font-semibold text-white transition hover:bg-stone-700 disabled:cursor-not-allowed disabled:bg-stone-400 flex items-center justify-center gap-2">
            <svg v-if="submitting" class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
            </svg>
            {{ stepLabel() }}
          </button>

          <!-- Razorpay branding note -->
          <p class="text-center text-xs text-stone-400">
            Secured by
            <span class="font-semibold text-stone-500">Razorpay</span>
            — UPI · Cards · Net Banking · Wallets
          </p>
        </form>
      </div>

      <div class="space-y-4 self-start">
        <!-- Coupon -->
        <div class="border border-stone-200 bg-white p-4 space-y-3">
          <p class="text-sm font-semibold text-stone-900">Coupon Code</p>
          <div v-if="appliedCoupon" class="flex items-center justify-between rounded bg-stone-50 border border-stone-200 px-3 py-2">
            <span class="font-mono text-sm font-semibold text-stone-800">{{ appliedCoupon.code }}</span>
            <button @click="removeCoupon" class="text-xs text-rose-500 hover:text-rose-700">Remove</button>
          </div>
          <div v-else class="flex gap-2">
            <input v-model="couponCode" type="text" placeholder="Enter code"
              class="flex-1 border border-stone-300 px-3 py-2 text-sm uppercase tracking-wider focus:outline-none focus:border-stone-500"
              @keyup.enter="applyCoupon" />
            <button type="button" @click="applyCoupon" :disabled="couponValidating || !couponCode.trim()"
              class="bg-stone-900 px-4 py-2 text-sm font-semibold text-white hover:bg-stone-700 transition disabled:opacity-50">
              {{ couponValidating ? '…' : 'Apply' }}
            </button>
          </div>
          <p v-if="couponError" class="text-xs text-rose-600">{{ couponError }}</p>
        </div>

        <!-- Order Summary -->
        <div class="border border-stone-200 bg-white p-6 space-y-4">
          <p class="font-semibold text-stone-900">Order Summary</p>
          <div v-for="item in cartStore.items" :key="item.id" class="flex justify-between text-sm text-stone-600">
            <span class="truncate pr-2">{{ item.product.name }} × {{ item.quantity }}</span>
            <span class="flex-shrink-0">{{ formatPrice(item.line_total) }}</span>
          </div>
          <div class="border-t border-stone-100 pt-3 space-y-2">
            <div class="flex justify-between text-sm text-stone-600">
              <span>Subtotal</span>
              <span>{{ formatPrice(cartStore.total) }}</span>
            </div>
            <div v-if="appliedCoupon" class="flex justify-between text-sm text-stone-600">
              <span>Discount ({{ appliedCoupon.code }})</span>
              <span class="text-stone-800">−{{ formatPrice(appliedCoupon.discount_amount) }}</span>
            </div>
            <div class="flex justify-between text-sm text-stone-600">
              <span>Shipping</span>
              <span v-if="selectedRate">{{ selectedRate.is_free ? 'FREE' : formatPrice(selectedRate.cost) }}</span>
              <span v-else class="text-stone-400">—</span>
            </div>
            <div class="flex justify-between pt-1 font-semibold text-stone-900">
              <span>Total</span>
              <span>{{ formatPrice(finalTotal()) }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Order Success Modal (guest OTP checkout) -->
  <Teleport to="body">
    <div v-if="orderPlaced" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4">
      <div class="w-full max-w-sm bg-white p-10 text-center space-y-5 shadow-2xl">
        <div class="flex items-center justify-center w-16 h-16 mx-auto bg-emerald-100">
          <svg class="w-8 h-8 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
          </svg>
        </div>
        <div>
          <h2 class="text-2xl font-semibold text-stone-900">Order Placed!</h2>
          <p class="mt-1 text-sm text-stone-400 font-mono">Order #{{ placedOrderId }}</p>
        </div>
        <p v-if="isNewUser" class="text-stone-600 text-sm leading-relaxed">
          Your account has been created using your mobile number.<br>
          Log in anytime to track this order and view your order history.
        </p>
        <p v-else class="text-stone-600 text-sm leading-relaxed">
          Thank you for your order!<br>
          Log in to track this order and view your order history.
        </p>
        <div class="flex flex-col gap-3">
          <router-link
            to="/auth"
            class="block bg-stone-900 px-8 py-3 text-sm font-semibold uppercase tracking-[0.1em] text-white transition hover:bg-stone-700"
          >
            Log In
          </router-link>
          <router-link
            to="/store"
            class="block border border-stone-300 px-8 py-3 text-sm font-semibold uppercase tracking-[0.1em] text-stone-700 transition hover:border-stone-500 hover:bg-stone-50"
          >
            Go to Store
          </router-link>
        </div>
      </div>
    </div>
  </Teleport>

  <!-- OTP Verification Modal (guest orders) -->
  <Teleport to="body">
    <div v-if="otpModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4">
      <div class="w-full max-w-sm rounded-2xl bg-white p-8 shadow-xl">
        <h2 class="text-xl font-semibold text-stone-900">Verify your mobile</h2>
        <p class="mt-2 text-sm text-stone-500">
          We sent a 6-digit OTP to <span class="font-medium text-stone-800">{{ otpPhone }}</span>.
          Enter it below to confirm your order and create your account.
        </p>

        <div v-if="otpSuccess" class="mt-4 rounded-xl bg-emerald-50 border border-emerald-200 p-3 text-sm text-emerald-700">
          {{ otpSuccess }}
        </div>
        <div v-if="otpError" class="mt-4 rounded-xl bg-rose-50 border border-rose-200 p-3 text-sm text-rose-700">
          {{ otpError }}
        </div>

        <input
          v-model="otpCode"
          type="text"
          maxlength="6"
          inputmode="numeric"
          placeholder="Enter 6-digit OTP"
          class="mt-4 w-full rounded-xl border border-stone-300 px-4 py-3 text-center text-2xl tracking-[0.4em] outline-none focus:border-stone-500"
        />

        <button
          type="button"
          :disabled="otpVerifying || otpCode.length !== 6"
          @click="submitOtp"
          class="mt-4 w-full rounded-full bg-stone-900 py-3 text-sm font-semibold text-white transition hover:bg-stone-700 disabled:opacity-50"
        >
          {{ otpVerifying ? 'Verifying…' : 'Verify & Continue' }}
        </button>

        <button
          type="button"
          :disabled="otpSending"
          @click="resendOtp"
          class="mt-3 w-full text-center text-sm text-stone-500 hover:text-stone-800 disabled:opacity-50"
        >
          {{ otpSending ? 'Sending…' : 'Resend OTP' }}
        </button>
      </div>
    </div>
  </Teleport>
</template>
