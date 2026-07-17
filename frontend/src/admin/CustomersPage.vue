<script setup lang="ts">
import { ref, reactive, watch } from 'vue'
import {
  fetchAdminCustomers,
  fetchAdminCustomer,
  createAdminCustomer,
  type AdminCustomer,
  type AdminCustomerDetail,
  type CustomerFilters,
} from '../services/adminCustomers'

const customers = ref<AdminCustomer[]>([])
const meta = ref({ total: 0, per_page: 20, current_page: 1, last_page: 1 })
const isLoading = ref(false)
const errorMessage = ref('')

const selectedCustomer = ref<AdminCustomerDetail | null>(null)
const detailLoading = ref(false)

const filters = reactive<CustomerFilters>({
  search: '',
  page: 1,
  per_page: 20,
})

const searchInput = ref('')

const orderStatusColors: Record<string, string> = {
  pending:    'bg-yellow-100 text-yellow-800',
  processing: 'bg-blue-100 text-blue-800',
  shipped:    'bg-indigo-100 text-indigo-800',
  delivered:  'bg-emerald-100 text-emerald-800',
  cancelled:  'bg-rose-100 text-rose-800',
}

const paymentStatusColors: Record<string, string> = {
  pending:  'bg-yellow-100 text-yellow-800',
  paid:     'bg-emerald-100 text-emerald-800',
  failed:   'bg-rose-100 text-rose-800',
  refunded: 'bg-slate-100 text-slate-800',
}

async function load() {
  isLoading.value = true
  errorMessage.value = ''
  try {
    const data = await fetchAdminCustomers(filters)
    customers.value = data.customers
    meta.value = data.meta
  } catch {
    errorMessage.value = 'Failed to load customers.'
  } finally {
    isLoading.value = false
  }
}

function search() {
  filters.search = searchInput.value
  filters.page = 1
}

function clearSearch() {
  searchInput.value = ''
  filters.search = ''
  filters.page = 1
}

function prevPage() {
  if (meta.value.current_page > 1) {
    filters.page = meta.value.current_page - 1
  }
}

function nextPage() {
  if (meta.value.current_page < meta.value.last_page) {
    filters.page = meta.value.current_page + 1
  }
}

async function openDetail(id: number) {
  selectedCustomer.value = null
  detailLoading.value = true
  try {
    selectedCustomer.value = await fetchAdminCustomer(id)
  } finally {
    detailLoading.value = false
  }
}

function closeDetail() {
  selectedCustomer.value = null
}

// ── Create customer modal ──────────────────────────────────────────────────
const showCreateModal = ref(false)
const createForm = reactive({ name: '', email: '', phone: '' })
const createSaving = ref(false)
const createError = ref('')
const createSuccess = ref('')

function openCreateModal() {
  createForm.name = ''
  createForm.email = ''
  createForm.phone = ''
  createError.value = ''
  createSuccess.value = ''
  showCreateModal.value = true
}

function closeCreateModal() {
  showCreateModal.value = false
}

async function submitCreate() {
  createError.value = ''
  createSaving.value = true
  try {
    const payload: { name: string; email: string; phone?: string } = {
      name:  createForm.name.trim(),
      email: createForm.email.trim(),
    }
    if (createForm.phone.trim()) payload.phone = createForm.phone.trim()

    await createAdminCustomer(payload)
    createSuccess.value = `Account created. Login credentials sent to ${payload.email}.`
    await load()
  } catch (err: unknown) {
    const e = err as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } }
    if (e.response?.data?.errors) {
      createError.value = Object.values(e.response.data.errors).flat().join(' ')
    } else {
      createError.value = e.response?.data?.message ?? 'Failed to create customer.'
    }
  } finally {
    createSaving.value = false
  }
}

function fmt(val: number | string | null) {
  return val != null ? `₹${Number(val).toFixed(2)}` : '₹0.00'
}

function fmtDate(iso: string) {
  return new Date(iso).toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' })
}

watch(() => [filters.page, filters.per_page, filters.search], load, { immediate: true })
</script>

<template>
  <div class="space-y-6 pt-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Customers</h1>
        <p class="mt-1 text-sm text-slate-500">{{ meta.total }} registered customers</p>
      </div>
      <button
        @click="openCreateModal"
        class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-700"
      >
        + Create Customer
      </button>
    </div>

    <!-- Error -->
    <div v-if="errorMessage" class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
      {{ errorMessage }}
    </div>

    <!-- Search -->
    <div class="flex items-center gap-3">
      <input
        v-model="searchInput"
        type="text"
        placeholder="Search by name or email…"
        class="w-72 rounded-xl border border-slate-200 px-4 py-2 text-sm text-slate-900 placeholder-slate-400 focus:border-slate-400 focus:outline-none"
        @keyup.enter="search"
      />
      <button
        @click="search"
        class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-700"
      >
        Search
      </button>
      <button
        v-if="filters.search"
        @click="clearSearch"
        class="rounded-xl border border-slate-200 px-4 py-2 text-sm text-slate-600 transition hover:bg-slate-50"
      >
        Clear
      </button>
      <select
        v-model="filters.per_page"
        class="ml-auto rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:outline-none"
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
            <th class="px-6 py-3">Customer</th>
            <th class="px-6 py-3">Joined</th>
            <th class="px-6 py-3 text-right">Orders</th>
            <th class="px-6 py-3 text-right">Total Spent</th>
            <th class="px-6 py-3"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-if="isLoading">
            <td colspan="5" class="px-6 py-10 text-center text-slate-400">Loading…</td>
          </tr>
          <tr v-else-if="customers.length === 0">
            <td colspan="5" class="px-6 py-10 text-center text-slate-400">No customers found.</td>
          </tr>
          <tr
            v-for="c in customers"
            :key="c.id"
            class="transition hover:bg-slate-50"
          >
            <td class="px-6 py-4">
              <p class="font-medium text-slate-900">{{ c.name }}</p>
              <p class="text-xs text-slate-500">{{ c.email }}</p>
            </td>
            <td class="px-6 py-4 text-slate-600">{{ fmtDate(c.created_at) }}</td>
            <td class="px-6 py-4 text-right font-medium text-slate-900">{{ c.orders_count }}</td>
            <td class="px-6 py-4 text-right font-medium text-slate-900">{{ fmt(c.orders_sum_total) }}</td>
            <td class="px-6 py-4 text-right">
              <button
                @click="openDetail(c.id)"
                class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:border-slate-300 hover:bg-slate-50"
              >
                View
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
        <button
          :disabled="meta.current_page === 1"
          @click="prevPage"
          class="rounded-xl border border-slate-200 px-4 py-2 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"
        >
          Previous
        </button>
        <button
          :disabled="meta.current_page === meta.last_page"
          @click="nextPage"
          class="rounded-xl border border-slate-200 px-4 py-2 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"
        >
          Next
        </button>
      </div>
    </div>
  </div>

  <!-- Create Customer Modal -->
  <transition name="fade">
    <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4">
      <div class="w-full max-w-md rounded-2xl bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-200 px-6 py-5">
          <h2 class="text-lg font-semibold text-slate-900">Create Customer</h2>
          <button @click="closeCreateModal" class="text-slate-400 transition hover:text-slate-700">✕</button>
        </div>

        <div class="space-y-4 p-6">
          <!-- Success state -->
          <div v-if="createSuccess" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ createSuccess }}
          </div>

          <template v-if="!createSuccess">
            <!-- Error -->
            <div v-if="createError" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
              {{ createError }}
            </div>

            <!-- Name -->
            <div>
              <label class="mb-1 block text-xs font-medium text-slate-600">Full Name <span class="text-rose-500">*</span></label>
              <input
                v-model="createForm.name"
                type="text"
                placeholder="e.g. Priya Sharma"
                class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:border-slate-400 focus:outline-none"
              />
            </div>

            <!-- Email -->
            <div>
              <label class="mb-1 block text-xs font-medium text-slate-600">Email <span class="text-rose-500">*</span></label>
              <input
                v-model="createForm.email"
                type="email"
                placeholder="e.g. priya@example.com"
                class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:border-slate-400 focus:outline-none"
              />
            </div>

            <!-- Phone -->
            <div>
              <label class="mb-1 block text-xs font-medium text-slate-600">Phone <span class="text-slate-400">(optional)</span></label>
              <input
                v-model="createForm.phone"
                type="tel"
                placeholder="10-digit mobile number"
                class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:border-slate-400 focus:outline-none"
              />
            </div>

            <p class="text-xs text-slate-400">A strong password will be auto-generated and emailed to the customer.</p>
          </template>
        </div>

        <div class="flex justify-end gap-3 border-t border-slate-100 px-6 py-4">
          <button
            @click="closeCreateModal"
            class="rounded-xl border border-slate-200 px-4 py-2 text-sm text-slate-600 transition hover:bg-slate-50"
          >
            {{ createSuccess ? 'Close' : 'Cancel' }}
          </button>
          <button
            v-if="!createSuccess"
            @click="submitCreate"
            :disabled="createSaving"
            class="rounded-xl bg-slate-900 px-5 py-2 text-sm font-medium text-white transition hover:bg-slate-700 disabled:opacity-50"
          >
            {{ createSaving ? 'Creating…' : 'Create Customer' }}
          </button>
        </div>
      </div>
    </div>
  </transition>

  <!-- Slide-over detail panel -->
  <transition name="slideover">
    <div
      v-if="selectedCustomer || detailLoading"
      class="fixed inset-0 z-50 flex"
    >
      <!-- Backdrop -->
      <div class="flex-1 bg-black/30" @click="closeDetail" />

      <!-- Panel -->
      <div class="flex h-full w-full max-w-lg flex-col overflow-y-auto bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-200 px-6 py-5">
          <h2 class="text-lg font-semibold text-slate-900">Customer Profile</h2>
          <button @click="closeDetail" class="text-slate-400 transition hover:text-slate-700">✕</button>
        </div>

        <div v-if="detailLoading" class="flex flex-1 items-center justify-center text-slate-400">
          Loading…
        </div>

        <div v-else-if="selectedCustomer" class="space-y-6 p-6">
          <!-- Basic info -->
          <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 space-y-2">
            <p class="text-xl font-bold text-slate-900">{{ selectedCustomer.name }}</p>
            <p class="text-sm text-slate-500">{{ selectedCustomer.email }}</p>
            <p class="text-xs text-slate-400">Member since {{ fmtDate(selectedCustomer.created_at) }}</p>
          </div>

          <!-- Stats -->
          <div class="grid grid-cols-2 gap-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-4 text-center">
              <p class="text-2xl font-bold text-slate-900">{{ selectedCustomer.orders_count }}</p>
              <p class="mt-1 text-xs text-slate-500 uppercase tracking-wide">Total Orders</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4 text-center">
              <p class="text-2xl font-bold text-slate-900">{{ fmt(selectedCustomer.orders_sum_total) }}</p>
              <p class="mt-1 text-xs text-slate-500 uppercase tracking-wide">Total Spent</p>
            </div>
          </div>

          <!-- Order history -->
          <div>
            <h3 class="mb-3 text-sm font-semibold text-slate-700 uppercase tracking-wide">Recent Orders</h3>
            <div v-if="selectedCustomer.orders.length === 0" class="text-sm text-slate-400">
              No orders yet.
            </div>
            <div v-else class="space-y-2">
              <div
                v-for="order in selectedCustomer.orders"
                :key="order.id"
                class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-4 py-3"
              >
                <div>
                  <p class="text-sm font-medium text-slate-900">#{{ order.id }}</p>
                  <p class="text-xs text-slate-400">{{ fmtDate(order.created_at) }}</p>
                </div>
                <div class="flex items-center gap-2">
                  <span
                    :class="['rounded-full px-2 py-0.5 text-xs font-medium', orderStatusColors[order.status] ?? 'bg-slate-100 text-slate-700']"
                  >
                    {{ order.status }}
                  </span>
                  <span
                    :class="['rounded-full px-2 py-0.5 text-xs font-medium', paymentStatusColors[order.payment_status] ?? 'bg-slate-100 text-slate-700']"
                  >
                    {{ order.payment_status }}
                  </span>
                  <span class="text-sm font-semibold text-slate-900">{{ fmt(order.total) }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </transition>
</template>

<style scoped>
.slideover-enter-active,
.slideover-leave-active,
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}
.slideover-enter-from,
.slideover-leave-to,
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
