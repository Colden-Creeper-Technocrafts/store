<script setup lang="ts">
import { ref, reactive, watch } from 'vue'
import { fetchAdminOrders, updateOrderReturnStatus, type AdminOrder, type OrderFilters } from '../services/adminOrders'

const orders = ref<AdminOrder[]>([])
const meta = ref({ total: 0, per_page: 20, current_page: 1, last_page: 1 })
const isLoading = ref(false)
const errorMessage = ref('')
const successMessage = ref('')

const selectedOrder = ref<AdminOrder | null>(null)
const savingId = ref<number | null>(null)
const rejectReason = ref('')
const showRejectInput = ref(false)

const filters = reactive<OrderFilters>({
  has_return: true,
  return_status: '',
  search: '',
  page: 1,
  per_page: 20,
})

const returnStatusColors: Record<string, string> = {
  requested: 'bg-yellow-100 text-yellow-800',
  approved:  'bg-blue-100 text-blue-800',
  rejected:  'bg-rose-100 text-rose-800',
  refunded:  'bg-emerald-100 text-emerald-800',
}

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
    errorMessage.value = 'Failed to load return requests.'
  } finally {
    isLoading.value = false
  }
}

async function doAction(order: AdminOrder, status: string, reason?: string) {
  savingId.value = order.id
  errorMessage.value = ''
  successMessage.value = ''
  try {
    const updated = await updateOrderReturnStatus(order.id, status, reason ?? null)
    const idx = orders.value.findIndex((o) => o.id === order.id)
    if (idx !== -1) orders.value[idx] = updated
    if (selectedOrder.value?.id === order.id) selectedOrder.value = updated
    successMessage.value = `Return marked as ${status}.`
    showRejectInput.value = false
    rejectReason.value = ''
  } catch {
    errorMessage.value = 'Failed to update return status.'
  } finally {
    savingId.value = null
  }
}

function openDetail(order: AdminOrder) {
  selectedOrder.value = order
  showRejectInput.value = false
  rejectReason.value = ''
}

function closeDetail() {
  selectedOrder.value = null
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

watch(() => [filters.page, filters.per_page, filters.return_status, filters.search], load, { immediate: true })
</script>

<template>
  <div class="space-y-6 pt-6">
    <!-- Header -->
    <div>
      <h1 class="text-2xl font-bold text-slate-900">Returns</h1>
      <p class="mt-1 text-sm text-slate-500">{{ meta.total }} return request{{ meta.total !== 1 ? 's' : '' }}</p>
    </div>

    <!-- Messages -->
    <div v-if="errorMessage" class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ errorMessage }}</div>
    <div v-if="successMessage" class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ successMessage }}</div>

    <!-- Filters -->
    <div class="flex flex-wrap items-center gap-3">
      <input
        v-model="filters.search"
        type="text"
        placeholder="Search by order ID or customer…"
        class="w-64 rounded-xl border border-slate-200 px-4 py-2 text-sm focus:outline-none"
        @change="filters.page = 1"
      />
      <select
        v-model="filters.return_status"
        class="rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none"
        @change="filters.page = 1"
      >
        <option value="">All return statuses</option>
        <option value="requested">Requested</option>
        <option value="approved">Approved</option>
        <option value="rejected">Rejected</option>
        <option value="refunded">Refunded</option>
      </select>
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
            <th class="px-6 py-3">Order status</th>
            <th class="px-6 py-3">Return status</th>
            <th class="px-6 py-3 text-right">Total</th>
            <th class="px-6 py-3"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-if="isLoading">
            <td colspan="6" class="px-6 py-10 text-center text-slate-400">Loading…</td>
          </tr>
          <tr v-else-if="orders.length === 0">
            <td colspan="6" class="px-6 py-10 text-center text-slate-400">No return requests found.</td>
          </tr>
          <tr v-for="order in orders" :key="order.id" class="transition hover:bg-slate-50">
            <td class="px-6 py-4">
              <p class="font-medium text-slate-900">#{{ order.id }}</p>
              <p class="text-xs text-slate-400">{{ fmtDate(order.created_at) }}</p>
            </td>
            <td class="px-6 py-4 text-slate-600">
              <p>{{ order.user?.name ?? order.shipping_name }}</p>
              <p class="text-xs text-slate-400">{{ order.user?.email ?? order.shipping_email }}</p>
            </td>
            <td class="px-6 py-4">
              <span :class="['rounded-full px-2 py-0.5 text-xs font-medium capitalize', orderStatusColors[order.status] ?? 'bg-slate-100 text-slate-700']">
                {{ order.status }}
              </span>
            </td>
            <td class="px-6 py-4">
              <span :class="['rounded-full px-2 py-0.5 text-xs font-medium capitalize', returnStatusColors[order.return_status ?? ''] ?? 'bg-slate-100 text-slate-700']">
                {{ order.return_status ?? '—' }}
              </span>
            </td>
            <td class="px-6 py-4 text-right font-semibold text-slate-900">{{ fmt(order.total) }}</td>
            <td class="px-6 py-4 text-right">
              <button
                @click="openDetail(order)"
                class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-slate-50"
              >
                Review
              </button>
            </td>
          </tr>
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

  <!-- Slide-over detail + actions -->
  <transition name="slideover">
    <div v-if="selectedOrder" class="fixed inset-0 z-50 flex">
      <div class="flex-1 bg-black/30" @click="closeDetail" />

      <div class="flex h-full w-full max-w-lg flex-col overflow-y-auto bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-200 px-6 py-5">
          <h2 class="text-lg font-semibold text-slate-900">Return #{{ selectedOrder.id }}</h2>
          <button @click="closeDetail" class="text-slate-400 transition hover:text-slate-700">✕</button>
        </div>

        <div class="space-y-5 p-6">
          <!-- Status badges -->
          <div class="flex flex-wrap gap-2">
            <span :class="['rounded-full px-3 py-1 text-xs font-medium capitalize', orderStatusColors[selectedOrder.status] ?? 'bg-slate-100 text-slate-700']">
              Order: {{ selectedOrder.status }}
            </span>
            <span :class="['rounded-full px-3 py-1 text-xs font-medium capitalize', returnStatusColors[selectedOrder.return_status ?? ''] ?? 'bg-slate-100 text-slate-700']">
              Return: {{ selectedOrder.return_status ?? 'none' }}
            </span>
          </div>

          <!-- Customer -->
          <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 space-y-1">
            <p class="font-semibold text-slate-900">{{ selectedOrder.user?.name ?? selectedOrder.shipping_name }}</p>
            <p class="text-sm text-slate-500">{{ selectedOrder.user?.email ?? selectedOrder.shipping_email }}</p>
            <p class="text-sm text-slate-500">Order placed: {{ fmtDate(selectedOrder.created_at) }}</p>
            <p class="text-sm font-semibold text-slate-900">Total: {{ fmt(selectedOrder.total) }}</p>
          </div>

          <!-- Return reason -->
          <div v-if="selectedOrder.return_reason" class="rounded-2xl border border-yellow-200 bg-yellow-50 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-yellow-700">Return reason</p>
            <p class="mt-1 text-sm text-yellow-900">{{ selectedOrder.return_reason }}</p>
          </div>

          <!-- Items -->
          <div>
            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Items</p>
            <div class="space-y-2">
              <div v-for="item in selectedOrder.items" :key="item.id" class="flex items-center justify-between rounded-xl bg-slate-50 px-4 py-2 text-sm">
                <span class="text-slate-700">{{ item.name }} × {{ item.quantity }}</span>
                <span class="font-medium text-slate-900">{{ fmt(item.subtotal) }}</span>
              </div>
            </div>
          </div>

          <!-- Actions -->
          <div class="border-t border-slate-100 pt-4 space-y-3">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Actions</p>

            <!-- Requested → Approve or Reject -->
            <template v-if="selectedOrder.return_status === 'requested'">
              <button
                :disabled="savingId === selectedOrder.id"
                @click="doAction(selectedOrder, 'approved')"
                class="w-full rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700 disabled:opacity-50"
              >
                Approve Return
              </button>

              <div v-if="!showRejectInput">
                <button
                  @click="showRejectInput = true"
                  class="w-full rounded-xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-sm font-semibold text-rose-700 transition hover:bg-rose-100"
                >
                  Reject Return
                </button>
              </div>
              <div v-else class="space-y-2">
                <textarea
                  v-model="rejectReason"
                  rows="2"
                  placeholder="Reason for rejection (optional)…"
                  class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none"
                />
                <div class="flex gap-2">
                  <button
                    :disabled="savingId === selectedOrder.id"
                    @click="doAction(selectedOrder, 'rejected', rejectReason)"
                    class="flex-1 rounded-xl bg-rose-600 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50"
                  >Confirm Reject</button>
                  <button @click="showRejectInput = false" class="rounded-xl border px-4 py-2 text-sm text-slate-600">Cancel</button>
                </div>
              </div>
            </template>

            <!-- Approved → Mark Refunded -->
            <template v-else-if="selectedOrder.return_status === 'approved'">
              <button
                :disabled="savingId === selectedOrder.id"
                @click="doAction(selectedOrder, 'refunded')"
                class="w-full rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-700 disabled:opacity-50"
              >
                Mark as Refunded
              </button>
            </template>

            <p v-else class="text-sm text-slate-400 text-center">No further actions available for this return.</p>
          </div>
        </div>
      </div>
    </div>
  </transition>
</template>

<style scoped>
.slideover-enter-active,
.slideover-leave-active { transition: opacity 0.2s ease; }
.slideover-enter-from,
.slideover-leave-to     { opacity: 0; }
</style>
