<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import { fetchOrders, type Order } from '../../services/orders'

const route = useRoute()
const authStore = useAuthStore()

const orders = ref<Order[]>([])
const loading = ref(false)
const error = ref('')
const placedId = ref<number | null>(
  route.query.placed ? Number(route.query.placed) : null
)

const formatDate = (iso: string) =>
  new Date(iso).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' })

const statusColor = (s: string) => {
  if (s === 'pending') return 'bg-amber-100 text-amber-800'
  if (s === 'processing') return 'bg-blue-100 text-blue-800'
  if (s === 'shipped') return 'bg-violet-100 text-violet-800'
  if (s === 'delivered') return 'bg-emerald-100 text-emerald-800'
  if (s === 'cancelled') return 'bg-rose-100 text-rose-800'
  return 'bg-stone-100 text-stone-700'
}

onMounted(async () => {
  if (!authStore.isCustomer) return
  loading.value = true
  try {
    orders.value = await fetchOrders()
  } catch {
    error.value = 'Failed to load orders.'
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <section class="space-y-6">
    <div class="border border-stone-200 bg-white p-8">
      <p class="text-xs font-semibold uppercase tracking-[0.18em] text-stone-500">Ladies Collection</p>
      <h1 class="mt-3 text-3xl text-stone-900">Your Orders</h1>
    </div>

    <div v-if="placedId" class="border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
      Order #{{ placedId }} placed successfully. We'll keep you updated!
    </div>

    <div v-if="route.query.guest === '1' && !authStore.isCustomer" class="border border-stone-200 bg-white p-12 text-center space-y-4">
      <p class="text-stone-600">Create an account to track this and future orders.</p>
      <div class="flex justify-center gap-3">
        <router-link to="/register" class="border border-stone-900 bg-stone-900 px-6 py-3 text-sm font-semibold text-white transition hover:bg-stone-700">
          Create Account
        </router-link>
        <router-link to="/store" class="border border-stone-300 px-6 py-3 text-sm font-semibold text-stone-900 transition hover:bg-stone-50">
          Continue Shopping
        </router-link>
      </div>
    </div>

    <div v-else-if="loading" class="border border-stone-200 bg-white p-10 text-center text-stone-500">
      Loading orders...
    </div>

    <div v-else-if="error" class="border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
      {{ error }}
    </div>

    <div v-else-if="!authStore.isCustomer" class="border border-stone-200 bg-white p-12 text-center space-y-4">
      <p class="text-stone-500">Please log in to view your order history.</p>
      <router-link to="/login" class="inline-block border border-stone-300 px-6 py-3 text-sm font-semibold text-stone-900 transition hover:bg-stone-50">
        Login
      </router-link>
    </div>

    <div v-else-if="orders.length === 0" class="border border-stone-200 bg-white p-12 text-center">
      <p class="text-stone-500">You haven't placed any orders yet.</p>
      <router-link to="/store" class="mt-6 inline-block border border-stone-300 px-6 py-3 text-sm font-semibold text-stone-900 transition hover:bg-stone-50">
        Start Shopping
      </router-link>
    </div>

    <div v-else class="space-y-4">
      <div
        v-for="order in orders"
        :key="order.id"
        class="border border-stone-200 bg-white p-6 space-y-4"
      >
        <div class="flex flex-wrap items-center justify-between gap-4">
          <div>
            <p class="font-semibold text-stone-900">Order #{{ order.id }}</p>
            <p class="text-sm text-stone-500">{{ formatDate(order.created_at) }}</p>
          </div>
          <div class="flex items-center gap-4">
            <span :class="['px-3 py-1 text-xs font-semibold capitalize rounded-full', statusColor(order.status)]">
              {{ order.status }}
            </span>
            <span class="font-semibold text-stone-900">${{ Number(order.total).toFixed(2) }}</span>
          </div>
        </div>

        <div class="divide-y divide-stone-100">
          <div
            v-for="item in order.items"
            :key="item.id"
            class="flex items-center justify-between py-2 text-sm"
          >
            <span class="text-stone-700">{{ item.name }} <span class="text-stone-400">× {{ item.quantity }}</span></span>
            <span class="text-stone-700">${{ Number(item.subtotal).toFixed(2) }}</span>
          </div>
        </div>

        <div class="border-t border-stone-100 pt-3 text-sm text-stone-500">
          Ship to: {{ order.shipping_name }}, {{ order.shipping_address }}, {{ order.shipping_city }}
        </div>
      </div>
    </div>
  </section>
</template>
