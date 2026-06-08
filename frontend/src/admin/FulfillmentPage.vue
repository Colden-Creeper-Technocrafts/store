<script setup lang="ts">
import { ref, reactive, watch } from 'vue'
import { fetchAdminOrders, updateOrderStatus, updateOrderTracking, type AdminOrder } from '../services/adminOrders'

const orders = ref<AdminOrder[]>([])
const meta = ref({ total: 0, per_page: 20, current_page: 1, last_page: 1 })
const isLoading = ref(false)
const errorMessage = ref('')
const successMessage = ref('')

const shippingOrderId = ref<number | null>(null)
const trackingNumber = ref('')
const trackingUrl = ref('')
const isSaving = ref(false)

const filters = reactive({
  status: 'processing',
  search: '',
  page: 1,
  per_page: 20,
})

const orderStatusColors: Record<string, string> = {
  pending:    'bg-yellow-100 text-yellow-800',
  processing: 'bg-blue-100 text-blue-800',
  shipped:    'bg-indigo-100 text-indigo-800',
  delivered:  'bg-emerald-100 text-emerald-800',
  cancelled:  'bg-rose-100 text-rose-800',
}

async function load() {
  isLoading.value = true
  errorMessage.value = ''
  try {
    const data = await fetchAdminOrders(filters)
    orders.value = data.orders
    meta.value = data.meta
  } catch {
    errorMessage.value = 'Failed to load orders.'
  } finally {
    isLoading.value = false
  }
}

function openShipRow(order: AdminOrder) {
  shippingOrderId.value = order.id
  trackingNumber.value = order.tracking_number ?? ''
  trackingUrl.value = order.tracking_url ?? ''
}

function cancelShip() {
  shippingOrderId.value = null
}

async function markShipped(order: AdminOrder) {
  isSaving.value = true
  errorMessage.value = ''
  successMessage.value = ''
  try {
    if (trackingNumber.value || trackingUrl.value) {
      await updateOrderTracking(order.id, trackingNumber.value || null, trackingUrl.value || null)
    }
    await updateOrderStatus(order.id, 'shipped')
    successMessage.value = `Order #${order.id} marked as shipped.`
    shippingOrderId.value = null
    await load()
  } catch {
    errorMessage.value = 'Failed to update order.'
  } finally {
    isSaving.value = false
  }
}

function fmt(val: number) {
  return `₹${Number(val).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`
}

function fmtDate(iso: string) {
  return new Date(iso).toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' })
}

function prevPage() {
  if (meta.value.current_page > 1) filters.page = meta.value.current_page - 1
}

function nextPage() {
  if (meta.value.current_page < meta.value.last_page) filters.page = meta.value.current_page + 1
}

watch(() => [filters.page, filters.per_page, filters.search], load, { immediate: true })
</script>

<template>
  <div class="space-y-6 pt-6">
    <!-- Header -->
    <div>
      <h1 class="text-2xl font-bold text-slate-900">Fulfillment</h1>
      <p class="mt-1 text-sm text-slate-500">{{ meta.total }} order{{ meta.total !== 1 ? 's' : '' }} awaiting shipment</p>
    </div>

    <!-- Messages -->
    <div v-if="errorMessage" class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ errorMessage }}</div>
    <div v-if="successMessage" class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ successMessage }}</div>

    <!-- Search + per page -->
    <div class="flex items-center gap-3">
      <input
        v-model="filters.search"
        type="text"
        placeholder="Search by order ID or customer…"
        class="w-64 rounded-xl border border-slate-200 px-4 py-2 text-sm focus:outline-none"
        @change="filters.page = 1"
      />
      <select
        v-model="filters.per_page"
        class="ml-auto rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none"
        @change="filters.page = 1"
      >
        <option :value="10">10 / page</option>
        <option :value="20">20 / page</option>
        <option :value="50">50 / page</option>
      </select>
    </div>

    <!-- Table -->
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
      <table class="w-full text-sm">
        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
          <tr>
            <th class="px-6 py-3">Order</th>
            <th class="px-6 py-3">Customer</th>
            <th class="px-6 py-3">Ship to</th>
            <th class="px-6 py-3">Items</th>
            <th class="px-6 py-3 text-right">Total</th>
            <th class="px-6 py-3"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-if="isLoading">
            <td colspan="6" class="px-6 py-10 text-center text-slate-400">Loading…</td>
          </tr>
          <tr v-else-if="orders.length === 0">
            <td colspan="6" class="px-6 py-10 text-center text-slate-400">No orders awaiting fulfillment.</td>
          </tr>
          <template v-for="order in orders" :key="order.id">
            <tr class="transition hover:bg-slate-50">
              <td class="px-6 py-4">
                <p class="font-medium text-slate-900">#{{ order.id }}</p>
                <p class="text-xs text-slate-400">{{ fmtDate(order.created_at) }}</p>
              </td>
              <td class="px-6 py-4">
                <p class="text-slate-700">{{ order.user?.name ?? order.shipping_name }}</p>
                <p class="text-xs text-slate-400">{{ order.user?.email ?? order.shipping_email }}</p>
              </td>
              <td class="px-6 py-4 text-slate-600">
                <p>{{ order.shipping_address }}</p>
                <p class="text-xs text-slate-400">{{ order.shipping_city }}, {{ order.shipping_postal_code }}</p>
              </td>
              <td class="px-6 py-4">
                <div v-for="item in order.items" :key="item.id" class="text-slate-600">
                  {{ item.name }} × {{ item.quantity }}
                </div>
              </td>
              <td class="px-6 py-4 text-right font-semibold text-slate-900">{{ fmt(order.total) }}</td>
              <td class="px-6 py-4 text-right">
                <button
                  @click="openShipRow(order)"
                  class="rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-slate-700"
                >
                  Ship
                </button>
              </td>
            </tr>

            <!-- Inline ship row -->
            <tr v-if="shippingOrderId === order.id" class="bg-blue-50 border-b">
              <td colspan="6" class="px-6 py-4">
                <div class="flex flex-wrap items-end gap-3">
                  <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Tracking number</label>
                    <input
                      v-model="trackingNumber"
                      type="text"
                      placeholder="e.g. 1234567890"
                      class="rounded-xl border border-slate-300 px-3 py-1.5 text-sm w-44 focus:outline-none"
                    />
                  </div>
                  <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Tracking URL (optional)</label>
                    <input
                      v-model="trackingUrl"
                      type="url"
                      placeholder="https://…"
                      class="rounded-xl border border-slate-300 px-3 py-1.5 text-sm w-56 focus:outline-none"
                    />
                  </div>
                  <button
                    :disabled="isSaving"
                    @click="markShipped(order)"
                    class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50"
                  >
                    Confirm Shipped
                  </button>
                  <button @click="cancelShip" class="rounded-xl border px-4 py-2 text-sm text-slate-600">
                    Cancel
                  </button>
                </div>
              </td>
            </tr>
          </template>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div v-if="meta.last_page > 1" class="flex items-center justify-between text-sm text-slate-600">
      <span>Page {{ meta.current_page }} of {{ meta.last_page }}</span>
      <div class="flex gap-2">
        <button :disabled="meta.current_page === 1" @click="prevPage" class="rounded-xl border border-slate-200 px-4 py-2 transition hover:bg-slate-50 disabled:opacity-40">Previous</button>
        <button :disabled="meta.current_page === meta.last_page" @click="nextPage" class="rounded-xl border border-slate-200 px-4 py-2 transition hover:bg-slate-50 disabled:opacity-40">Next</button>
      </div>
    </div>
  </div>
</template>
