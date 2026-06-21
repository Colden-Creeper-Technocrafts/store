<script setup lang="ts">
import { ref, reactive, watch } from 'vue'
import {
  fetchAdminOrders,
  updateOrderStatus,
  updateOrderTracking,
  fulfillOrder,
  getLiveTracking,
  type AdminOrder,
  type Shipment,
  type TrackingResult,
} from '../services/adminOrders'

const orders = ref<AdminOrder[]>([])
const meta = ref({ total: 0, per_page: 20, current_page: 1, last_page: 1 })
const isLoading = ref(false)
const errorMessage = ref('')
const successMessage = ref('')

const shippingOrderId = ref<number | null>(null)
const trackingNumber = ref('')
const trackingUrl = ref('')
const isSaving = ref(false)

// Auto-fulfill state
const isFulfilling = ref(false)
const fulfilledShipment = ref<Shipment | null>(null)
const fulfillError = ref('')

// Live tracking state
const trackingOrderId = ref<number | null>(null)
const trackingResult = ref<TrackingResult | null>(null)
const isTrackingLoading = ref(false)
const trackingError = ref('')

const filters = reactive({
  status: 'processing',
  search: '',
  page: 1,
  per_page: 20,
})

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
  fulfilledShipment.value = null
  fulfillError.value = ''
  trackingOrderId.value = null
  trackingResult.value = null
}

function cancelShip() {
  shippingOrderId.value = null
  fulfilledShipment.value = null
  fulfillError.value = ''
}

async function autoFulfill(order: AdminOrder) {
  isFulfilling.value = true
  fulfillError.value = ''
  fulfilledShipment.value = null
  try {
    const shipment = await fulfillOrder(order.id)
    fulfilledShipment.value = shipment
    successMessage.value = `Order #${order.id} fulfilled — AWB: ${shipment.awb_number}`
    await load()
  } catch (err: any) {
    const msg = err?.response?.data?.message ?? 'Auto-fulfill failed. Check Shiprocket credentials in Shipping → Providers.'
    fulfillError.value = msg
  } finally {
    isFulfilling.value = false
  }
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

async function openTracking(order: AdminOrder) {
  trackingOrderId.value = order.id
  trackingResult.value = null
  trackingError.value = ''
  isTrackingLoading.value = true
  try {
    trackingResult.value = await getLiveTracking(order.id)
  } catch (err: any) {
    trackingError.value = err?.response?.data?.message ?? 'Could not load tracking.'
  } finally {
    isTrackingLoading.value = false
  }
}

function closeTracking() {
  trackingOrderId.value = null
  trackingResult.value = null
}

function prevPage() {
  if (meta.value.current_page > 1) filters.page = meta.value.current_page - 1
}

function nextPage() {
  if (meta.value.current_page < meta.value.last_page) filters.page = meta.value.current_page + 1
}

function fmt(val: number) {
  return `₹${Number(val).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`
}

function fmtDate(iso: string) {
  return new Date(iso).toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' })
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
                <div class="flex items-center justify-end gap-2">
                  <!-- Tracking button — only if order has AWB -->
                  <button
                    v-if="order.tracking_number"
                    @click="openTracking(order)"
                    class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-50"
                  >
                    Track
                  </button>
                  <button
                    @click="openShipRow(order)"
                    class="rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-slate-700"
                  >
                    Ship
                  </button>
                </div>
              </td>
            </tr>

            <!-- Inline ship drawer -->
            <tr v-if="shippingOrderId === order.id" class="bg-slate-50 border-b">
              <td colspan="6" class="px-6 py-5">

                <!-- Success after auto-fulfill -->
                <div v-if="fulfilledShipment" class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 space-y-2">
                  <p class="text-sm font-semibold text-emerald-800">Shipment created successfully</p>
                  <div class="grid grid-cols-2 gap-x-6 gap-y-1 text-sm text-emerald-700">
                    <span class="font-medium">AWB Number</span>
                    <span>{{ fulfilledShipment.awb_number }}</span>
                    <span class="font-medium">Tracking URL</span>
                    <a
                      v-if="fulfilledShipment.tracking_url"
                      :href="fulfilledShipment.tracking_url"
                      target="_blank"
                      class="underline break-all"
                    >{{ fulfilledShipment.tracking_url }}</a>
                    <span v-else class="text-emerald-500">—</span>
                    <span class="font-medium">Shipping Label</span>
                    <a
                      v-if="fulfilledShipment.label_url"
                      :href="fulfilledShipment.label_url"
                      target="_blank"
                      class="underline"
                    >Download PDF</a>
                    <span v-else class="text-emerald-500">Not available yet</span>
                    <span class="font-medium">Weight</span>
                    <span>{{ fulfilledShipment.weight_kg }} kg</span>
                  </div>
                  <button @click="cancelShip" class="mt-2 rounded-lg border border-emerald-300 px-3 py-1.5 text-xs font-medium text-emerald-700 hover:bg-emerald-100">
                    Close
                  </button>
                </div>

                <!-- Ship form -->
                <div v-else class="space-y-4">
                  <!-- Error from auto-fulfill -->
                  <div v-if="fulfillError" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    {{ fulfillError }}
                  </div>

                  <!-- Auto-fulfill via Shiprocket -->
                  <div class="rounded-xl border border-indigo-100 bg-indigo-50 p-4">
                    <p class="mb-1 text-sm font-semibold text-indigo-900">Auto-fulfill via Shiprocket</p>
                    <p class="mb-3 text-xs text-indigo-600">Creates a Shiprocket order, assigns an AWB automatically, and marks this order as shipped.</p>
                    <button
                      :disabled="isFulfilling"
                      @click="autoFulfill(order)"
                      class="rounded-xl bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50 flex items-center gap-2"
                    >
                      <svg v-if="isFulfilling" class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                      </svg>
                      {{ isFulfilling ? 'Creating shipment…' : 'Auto-fulfill via Shiprocket' }}
                    </button>
                  </div>

                  <!-- Divider -->
                  <div class="flex items-center gap-3 text-xs text-slate-400">
                    <div class="flex-1 border-t border-slate-200"></div>
                    <span>or enter tracking manually</span>
                    <div class="flex-1 border-t border-slate-200"></div>
                  </div>

                  <!-- Manual tracking entry -->
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
                      class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700 disabled:opacity-50"
                    >
                      Confirm Shipped
                    </button>
                    <button @click="cancelShip" class="rounded-xl border px-4 py-2 text-sm text-slate-600">
                      Cancel
                    </button>
                  </div>
                </div>
              </td>
            </tr>

            <!-- Live tracking panel -->
            <tr v-if="trackingOrderId === order.id" class="bg-slate-50 border-b">
              <td colspan="6" class="px-6 py-5">
                <div class="flex items-center justify-between mb-3">
                  <p class="text-sm font-semibold text-slate-800">Live Tracking — Order #{{ order.id }}</p>
                  <button @click="closeTracking" class="text-xs text-slate-400 hover:text-slate-600">Close</button>
                </div>

                <div v-if="isTrackingLoading" class="text-sm text-slate-400">Loading tracking events…</div>
                <div v-else-if="trackingError" class="text-sm text-rose-600">{{ trackingError }}</div>
                <div v-else-if="trackingResult">
                  <div class="mb-3 flex flex-wrap gap-4 text-sm">
                    <span>
                      <span class="font-medium text-slate-600">AWB:</span>
                      <span class="ml-1 text-slate-800">{{ trackingResult.awb_number }}</span>
                    </span>
                    <span v-if="trackingResult.current_status">
                      <span class="font-medium text-slate-600">Status:</span>
                      <span class="ml-1 text-slate-800">{{ trackingResult.current_status }}</span>
                    </span>
                    <span v-if="trackingResult.estimated_delivery">
                      <span class="font-medium text-slate-600">EDD:</span>
                      <span class="ml-1 text-slate-800">{{ trackingResult.estimated_delivery }}</span>
                    </span>
                  </div>

                  <div v-if="trackingResult.history.length === 0" class="text-sm text-slate-400">No tracking events yet.</div>
                  <ol v-else class="relative border-l border-slate-200 ml-2 space-y-3">
                    <li
                      v-for="(event, idx) in trackingResult.history"
                      :key="idx"
                      class="ml-4"
                    >
                      <div class="absolute -left-1.5 mt-1.5 h-3 w-3 rounded-full border border-white bg-slate-400"></div>
                      <p class="text-xs font-semibold text-slate-700">{{ event.status }}</p>
                      <p v-if="event.location" class="text-xs text-slate-500">{{ event.location }}</p>
                      <p v-if="event.description" class="text-xs text-slate-600 mt-0.5">{{ event.description }}</p>
                      <p class="text-xs text-slate-400">{{ event.timestamp ?? '—' }}</p>
                    </li>
                  </ol>
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
