<script setup lang="ts">
import { onMounted, reactive, ref, watch } from 'vue'
import type { AdminOrder, OrderFilters } from '../services/adminOrders'
import {
  fetchAdminOrders,
  fetchAdminOrder,
  updateOrderStatus,
  updateOrderPaymentStatus,
  updateOrderTracking,
  updateOrderNotes,
} from '../services/adminOrders'

const orders = ref<AdminOrder[]>([])
const meta = ref({ total: 0, per_page: 20, current_page: 1, last_page: 1 })
const isLoading = ref(false)
const selectedOrder = ref<AdminOrder | null>(null)
const detailLoading = ref(false)
const errorMessage = ref('')
const successMessage = ref('')

const filters = reactive<OrderFilters>({
  status: '',
  payment_status: '',
  search: '',
  date_from: '',
  date_to: '',
  page: 1,
  per_page: 20,
})

const statusNote = ref('')
const trackingNumber = ref('')
const trackingUrl = ref('')
const adminNotes = ref('')
const savingField = ref<string | null>(null)

const ORDER_STATUSES = ['pending', 'processing', 'shipped', 'delivered', 'cancelled']
const PAYMENT_STATUSES = ['pending', 'paid', 'failed', 'refunded']

const load = async () => {
  isLoading.value = true
  errorMessage.value = ''
  try {
    const params: OrderFilters = {}
    if (filters.status) params.status = filters.status
    if (filters.payment_status) params.payment_status = filters.payment_status
    if (filters.search) params.search = filters.search
    if (filters.date_from) params.date_from = filters.date_from
    if (filters.date_to) params.date_to = filters.date_to
    params.page = filters.page
    params.per_page = filters.per_page

    const result = await fetchAdminOrders(params)
    orders.value = result.orders
    meta.value = result.meta
  } catch {
    errorMessage.value = 'Failed to load orders.'
  } finally {
    isLoading.value = false
  }
}

onMounted(load)

watch(() => [filters.status, filters.payment_status, filters.date_from, filters.date_to], () => {
  filters.page = 1
  load()
})

const search = () => {
  filters.page = 1
  load()
}

const clearFilters = () => {
  filters.status = ''
  filters.payment_status = ''
  filters.search = ''
  filters.date_from = ''
  filters.date_to = ''
  filters.page = 1
  load()
}

const openDetail = async (order: AdminOrder) => {
  detailLoading.value = true
  selectedOrder.value = order
  trackingNumber.value = order.tracking_number ?? ''
  trackingUrl.value = order.tracking_url ?? ''
  adminNotes.value = order.admin_notes ?? ''
  statusNote.value = ''
  try {
    selectedOrder.value = await fetchAdminOrder(order.id)
    trackingNumber.value = selectedOrder.value.tracking_number ?? ''
    trackingUrl.value = selectedOrder.value.tracking_url ?? ''
    adminNotes.value = selectedOrder.value.admin_notes ?? ''
  } finally {
    detailLoading.value = false
  }
}

const closeDetail = () => {
  selectedOrder.value = null
}

const doUpdateStatus = async (newStatus: string) => {
  if (!selectedOrder.value) return
  if (!confirm(`Change status to "${newStatus}"?`)) return
  savingField.value = 'status'
  try {
    selectedOrder.value = await updateOrderStatus(selectedOrder.value.id, newStatus, statusNote.value || undefined)
    statusNote.value = ''
    successMessage.value = 'Status updated.'
    const idx = orders.value.findIndex(o => o.id === selectedOrder.value!.id)
    if (idx !== -1) orders.value[idx] = { ...orders.value[idx], status: newStatus }
  } catch (e: any) {
    errorMessage.value = e?.response?.data?.message ?? 'Failed to update status.'
  } finally {
    savingField.value = null
  }
}

const doUpdatePaymentStatus = async (ps: string) => {
  if (!selectedOrder.value) return
  savingField.value = 'payment_status'
  try {
    selectedOrder.value = await updateOrderPaymentStatus(selectedOrder.value.id, ps)
    successMessage.value = 'Payment status updated.'
    const idx = orders.value.findIndex(o => o.id === selectedOrder.value!.id)
    if (idx !== -1) orders.value[idx] = { ...orders.value[idx], payment_status: ps }
  } catch {
    errorMessage.value = 'Failed to update payment status.'
  } finally {
    savingField.value = null
  }
}

const saveTracking = async () => {
  if (!selectedOrder.value) return
  savingField.value = 'tracking'
  try {
    selectedOrder.value = await updateOrderTracking(
      selectedOrder.value.id,
      trackingNumber.value || null,
      trackingUrl.value || null
    )
    successMessage.value = 'Tracking info saved.'
  } catch {
    errorMessage.value = 'Failed to save tracking.'
  } finally {
    savingField.value = null
  }
}

const saveAdminNotes = async () => {
  if (!selectedOrder.value) return
  savingField.value = 'notes'
  try {
    selectedOrder.value = await updateOrderNotes(selectedOrder.value.id, adminNotes.value || null)
    successMessage.value = 'Notes saved.'
  } catch {
    errorMessage.value = 'Failed to save notes.'
  } finally {
    savingField.value = null
  }
}

const formatDate = (d: string) =>
  new Date(d).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })

const statusBadge = (s: string) => {
  const map: Record<string, string> = {
    pending: 'bg-amber-100 text-amber-800',
    processing: 'bg-blue-100 text-blue-800',
    shipped: 'bg-violet-100 text-violet-800',
    delivered: 'bg-emerald-100 text-emerald-800',
    cancelled: 'bg-rose-100 text-rose-800',
  }
  return map[s] ?? 'bg-slate-100 text-slate-700'
}

const paymentBadge = (s: string) => {
  const map: Record<string, string> = {
    pending: 'bg-amber-100 text-amber-700',
    paid: 'bg-emerald-100 text-emerald-700',
    failed: 'bg-rose-100 text-rose-700',
    refunded: 'bg-slate-100 text-slate-700',
  }
  return map[s] ?? 'bg-slate-100 text-slate-600'
}
</script>

<template>
  <div class="space-y-6 py-6 px-2">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Orders</h1>
        <p class="text-sm text-slate-500 mt-1">{{ meta.total }} total orders</p>
      </div>
    </div>

    <div v-if="successMessage" class="rounded-2xl bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-800">
      {{ successMessage }}
    </div>
    <div v-if="errorMessage" class="rounded-2xl bg-rose-50 border border-rose-200 p-4 text-sm text-rose-700">
      {{ errorMessage }}
    </div>

    <!-- Filters -->
    <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
      <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
        <div class="lg:col-span-2 flex gap-2">
          <input
            v-model="filters.search"
            type="text"
            placeholder="Search name, email, or #ID"
            class="flex-1 rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:border-slate-400"
            @keyup.enter="search"
          />
          <button @click="search" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700 transition">
            Search
          </button>
        </div>
        <select
          v-model="filters.status"
          class="rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:border-slate-400"
        >
          <option value="">All statuses</option>
          <option v-for="s in ORDER_STATUSES" :key="s" :value="s" class="capitalize">{{ s }}</option>
        </select>
        <select
          v-model="filters.payment_status"
          class="rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:border-slate-400"
        >
          <option value="">All payments</option>
          <option v-for="s in PAYMENT_STATUSES" :key="s" :value="s" class="capitalize">{{ s }}</option>
        </select>
        <button @click="clearFilters" class="rounded-xl border border-slate-200 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50 transition">
          Clear filters
        </button>
      </div>
      <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div class="flex items-center gap-2">
          <label class="text-xs text-slate-500">From</label>
          <input v-model="filters.date_from" type="date" class="flex-1 rounded-xl border border-slate-200 px-3 py-1.5 text-sm focus:outline-none focus:border-slate-400" />
        </div>
        <div class="flex items-center gap-2">
          <label class="text-xs text-slate-500">To</label>
          <input v-model="filters.date_to" type="date" class="flex-1 rounded-xl border border-slate-200 px-3 py-1.5 text-sm focus:outline-none focus:border-slate-400" />
        </div>
      </div>
    </div>

    <!-- Order list -->
    <div class="rounded-3xl border border-slate-200 bg-white shadow-sm overflow-hidden">
      <div v-if="isLoading" class="p-10 text-center text-sm text-slate-400">Loading orders…</div>
      <div v-else-if="orders.length === 0" class="p-10 text-center text-sm text-slate-400">No orders found.</div>
      <table v-else class="w-full text-sm">
        <thead class="border-b border-slate-100 bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
          <tr>
            <th class="px-4 py-3 text-left">Order</th>
            <th class="px-4 py-3 text-left">Customer</th>
            <th class="px-4 py-3 text-left">Status</th>
            <th class="px-4 py-3 text-left">Payment</th>
            <th class="px-4 py-3 text-right">Total</th>
            <th class="px-4 py-3 text-left">Date</th>
            <th class="px-4 py-3 text-right">Action</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="order in orders" :key="order.id" class="hover:bg-slate-50 transition">
            <td class="px-4 py-3 font-semibold text-slate-900">#{{ order.id }}</td>
            <td class="px-4 py-3">
              <p class="text-slate-800">{{ order.shipping_name }}</p>
              <p class="text-xs text-slate-400">{{ order.shipping_email }}</p>
            </td>
            <td class="px-4 py-3">
              <span :class="['rounded-full px-2.5 py-0.5 text-xs font-medium capitalize', statusBadge(order.status)]">
                {{ order.status }}
              </span>
            </td>
            <td class="px-4 py-3">
              <span :class="['rounded-full px-2.5 py-0.5 text-xs font-medium capitalize', paymentBadge(order.payment_status)]">
                {{ order.payment_status }}
              </span>
            </td>
            <td class="px-4 py-3 text-right font-semibold text-slate-900">
              ₹{{ Number(order.total).toFixed(2) }}
            </td>
            <td class="px-4 py-3 text-slate-500 text-xs">{{ formatDate(order.created_at) }}</td>
            <td class="px-4 py-3 text-right">
              <button @click="openDetail(order)" class="text-xs font-medium text-slate-600 hover:text-slate-900 underline">
                Manage
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div v-if="meta.last_page > 1" class="flex items-center justify-between text-sm">
      <p class="text-slate-500">Page {{ meta.current_page }} of {{ meta.last_page }}</p>
      <div class="flex gap-2">
        <button
          :disabled="meta.current_page <= 1"
          @click="filters.page = meta.current_page - 1; load()"
          class="rounded-xl border border-slate-200 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 disabled:opacity-40"
        >Previous</button>
        <button
          :disabled="meta.current_page >= meta.last_page"
          @click="filters.page = meta.current_page + 1; load()"
          class="rounded-xl border border-slate-200 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 disabled:opacity-40"
        >Next</button>
      </div>
    </div>

    <!-- Order detail slide-over -->
    <div v-if="selectedOrder" class="fixed inset-0 z-50 flex justify-end bg-black/30" @click.self="closeDetail">
      <div class="h-full w-full max-w-2xl overflow-y-auto bg-white shadow-2xl flex flex-col">
        <div class="sticky top-0 bg-white border-b border-slate-200 px-6 py-4 flex items-center justify-between">
          <h2 class="font-bold text-slate-900 text-lg">Order #{{ selectedOrder.id }}</h2>
          <button @click="closeDetail" class="text-slate-400 hover:text-slate-700 text-2xl leading-none">&times;</button>
        </div>

        <div v-if="detailLoading" class="flex-1 flex items-center justify-center text-slate-400">Loading…</div>

        <div v-else class="p-6 space-y-6">

          <!-- Overview row -->
          <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
              <p class="text-xs font-medium text-slate-500 mb-1">Customer</p>
              <p class="text-slate-800">{{ selectedOrder.shipping_name }}</p>
              <p class="text-slate-500">{{ selectedOrder.shipping_email }}</p>
              <p v-if="selectedOrder.shipping_phone" class="text-slate-500">{{ selectedOrder.shipping_phone }}</p>
            </div>
            <div>
              <p class="text-xs font-medium text-slate-500 mb-1">Ship to</p>
              <p class="text-slate-700">{{ selectedOrder.shipping_address }}</p>
              <p class="text-slate-700">{{ selectedOrder.shipping_city }}, {{ selectedOrder.shipping_postal_code }}</p>
              <p class="text-slate-700">{{ selectedOrder.shipping_country }}</p>
            </div>
          </div>

          <!-- Items -->
          <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">Items</p>
            <div class="rounded-2xl border border-slate-100 overflow-hidden">
              <table class="w-full text-sm">
                <tbody class="divide-y divide-slate-50">
                  <tr v-for="item in selectedOrder.items" :key="item.id">
                    <td class="px-4 py-2 text-slate-700">{{ item.name }}</td>
                    <td class="px-4 py-2 text-slate-500 text-xs">{{ item.sku }}</td>
                    <td class="px-4 py-2 text-center text-slate-600">×{{ item.quantity }}</td>
                    <td class="px-4 py-2 text-right font-medium text-slate-800">₹{{ Number(item.subtotal).toFixed(2) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div class="mt-2 text-right space-y-1 text-sm text-slate-600">
              <div class="flex justify-end gap-8">
                <span>Subtotal</span>
                <span>₹{{ Number(selectedOrder.subtotal).toFixed(2) }}</span>
              </div>
              <div v-if="selectedOrder.discount_amount > 0" class="flex justify-end gap-8 text-emerald-700">
                <span>Discount <span v-if="selectedOrder.coupon_code" class="font-mono">({{ selectedOrder.coupon_code }})</span></span>
                <span>−₹{{ Number(selectedOrder.discount_amount).toFixed(2) }}</span>
              </div>
              <div class="flex justify-end gap-8 font-bold text-slate-900">
                <span>Total</span>
                <span>₹{{ Number(selectedOrder.total).toFixed(2) }}</span>
              </div>
            </div>
          </div>

          <!-- Status update -->
          <div class="rounded-2xl border border-slate-200 p-4 space-y-3">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Order Status</p>
            <div class="flex flex-wrap gap-2">
              <button
                v-for="s in ORDER_STATUSES"
                :key="s"
                @click="doUpdateStatus(s)"
                :disabled="selectedOrder.status === s || savingField === 'status'"
                :class="[
                  'rounded-full px-3 py-1 text-xs font-medium capitalize transition',
                  selectedOrder.status === s ? statusBadge(s) : 'bg-slate-100 text-slate-600 hover:bg-slate-200',
                  'disabled:cursor-not-allowed'
                ]"
              >{{ s }}</button>
            </div>
            <input
              v-model="statusNote"
              type="text"
              placeholder="Optional note for this status change…"
              class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:border-slate-400"
            />
          </div>

          <!-- Payment status -->
          <div class="rounded-2xl border border-slate-200 p-4 space-y-3">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Payment Status</p>
            <div class="flex flex-wrap gap-2">
              <button
                v-for="ps in PAYMENT_STATUSES"
                :key="ps"
                @click="doUpdatePaymentStatus(ps)"
                :disabled="selectedOrder.payment_status === ps || savingField === 'payment_status'"
                :class="[
                  'rounded-full px-3 py-1 text-xs font-medium capitalize transition',
                  selectedOrder.payment_status === ps ? paymentBadge(ps) : 'bg-slate-100 text-slate-600 hover:bg-slate-200',
                  'disabled:cursor-not-allowed'
                ]"
              >{{ ps }}</button>
            </div>
          </div>

          <!-- Tracking -->
          <div class="rounded-2xl border border-slate-200 p-4 space-y-3">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tracking</p>
            <div>
              <label class="block text-xs text-slate-500 mb-1">Tracking Number</label>
              <input v-model="trackingNumber" type="text" placeholder="e.g. 1Z999AA10123456784"
                class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:border-slate-400" />
            </div>
            <div>
              <label class="block text-xs text-slate-500 mb-1">Tracking URL</label>
              <input v-model="trackingUrl" type="url" placeholder="https://track.carrier.com/..."
                class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:border-slate-400" />
            </div>
            <button
              @click="saveTracking"
              :disabled="savingField === 'tracking'"
              class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700 transition disabled:opacity-50"
            >{{ savingField === 'tracking' ? 'Saving…' : 'Save Tracking' }}</button>
          </div>

          <!-- Admin notes -->
          <div class="rounded-2xl border border-slate-200 p-4 space-y-3">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Internal Notes</p>
            <textarea
              v-model="adminNotes"
              rows="3"
              placeholder="Private notes visible to admins only…"
              class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:border-slate-400"
            ></textarea>
            <button
              @click="saveAdminNotes"
              :disabled="savingField === 'notes'"
              class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700 transition disabled:opacity-50"
            >{{ savingField === 'notes' ? 'Saving…' : 'Save Notes' }}</button>
          </div>

          <!-- Status history -->
          <div v-if="selectedOrder.status_history && selectedOrder.status_history.length">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">Status History</p>
            <ol class="space-y-2">
              <li
                v-for="h in [...(selectedOrder.status_history ?? [])].reverse()"
                :key="h.id"
                class="flex items-start gap-3 text-sm"
              >
                <span class="mt-1 h-2 w-2 flex-shrink-0 rounded-full bg-slate-300"></span>
                <div>
                  <span class="text-slate-700 capitalize">{{ h.from_status ?? 'created' }} → {{ h.to_status }}</span>
                  <span v-if="h.changed_by" class="ml-2 text-slate-400 text-xs">by {{ h.changed_by.name }}</span>
                  <p v-if="h.note" class="text-xs text-slate-500 mt-0.5">{{ h.note }}</p>
                  <p class="text-xs text-slate-400">{{ formatDate(h.created_at) }}</p>
                </div>
              </li>
            </ol>
          </div>

          <div v-if="selectedOrder.notes" class="rounded-2xl bg-slate-50 border border-slate-200 p-4 text-sm text-slate-700">
            <p class="text-xs font-semibold text-slate-500 mb-1">Customer Notes</p>
            {{ selectedOrder.notes }}
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
