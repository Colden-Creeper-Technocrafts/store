<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { fetchAdminCarts, clearAdminCart, type AdminCart } from '../services/adminCarts'

const carts = ref<AdminCart[]>([])
const loading = ref(false)
const error = ref('')
const expandedCart = ref<number | null>(null)
const clearingId = ref<number | null>(null)

async function load() {
  loading.value = true
  error.value = ''
  try {
    carts.value = await fetchAdminCarts()
  } catch {
    error.value = 'Failed to load carts.'
  } finally {
    loading.value = false
  }
}

async function clearCart(cart: AdminCart) {
  if (!confirm(`Clear ${cart.item_count} item(s) from ${cart.user.name}'s cart?`)) return
  clearingId.value = cart.id
  try {
    await clearAdminCart(cart.id)
    carts.value = carts.value.filter(c => c.id !== cart.id)
  } catch {
    error.value = 'Failed to clear cart.'
  } finally {
    clearingId.value = null
  }
}

function toggleExpand(id: number) {
  expandedCart.value = expandedCart.value === id ? null : id
}

function timeAgo(iso: string): string {
  const diff = Date.now() - new Date(iso).getTime()
  const mins = Math.floor(diff / 60000)
  if (mins < 60) return `${mins}m ago`
  const hrs = Math.floor(mins / 60)
  if (hrs < 24) return `${hrs}h ago`
  return `${Math.floor(hrs / 24)}d ago`
}

function formatPrice(v: number): string {
  return '₹' + v.toFixed(2)
}

onMounted(load)
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">Admin</p>
        <h1 class="text-2xl font-bold text-slate-900">Active Carts</h1>
        <p class="mt-1 text-sm text-slate-500">Carts with items that have not been converted to orders yet.</p>
      </div>
      <button @click="load" :disabled="loading"
        class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-50">
        {{ loading ? 'Loading…' : 'Refresh' }}
      </button>
    </div>

    <div v-if="error" class="rounded-lg border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">{{ error }}</div>

    <div v-if="loading && !carts.length" class="rounded-xl border border-slate-200 bg-white p-12 text-center text-slate-400">
      Loading carts…
    </div>

    <div v-else-if="!carts.length" class="rounded-xl border border-slate-200 bg-white p-12 text-center">
      <p class="text-slate-500">No active carts right now.</p>
    </div>

    <div v-else class="overflow-hidden rounded-xl border border-slate-200 bg-white">
      <table class="w-full text-sm">
        <thead class="border-b border-slate-200 bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500">
          <tr>
            <th class="px-5 py-3 text-left">Customer</th>
            <th class="px-5 py-3 text-left">Items</th>
            <th class="px-5 py-3 text-left">Subtotal</th>
            <th class="px-5 py-3 text-left">Last Active</th>
            <th class="px-5 py-3 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <template v-for="cart in carts" :key="cart.id">
            <tr class="hover:bg-slate-50 transition-colors">
              <td class="px-5 py-4">
                <p class="font-medium text-slate-900">{{ cart.user.name }}</p>
                <p class="text-slate-400">{{ cart.user.email }}</p>
              </td>
              <td class="px-5 py-4">
                <button @click="toggleExpand(cart.id)"
                  class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-200 transition-colors">
                  {{ cart.item_count }} item{{ cart.item_count !== 1 ? 's' : '' }}
                  <span class="text-slate-400">{{ expandedCart === cart.id ? '▲' : '▼' }}</span>
                </button>
              </td>
              <td class="px-5 py-4 font-semibold text-slate-900">{{ formatPrice(cart.subtotal) }}</td>
              <td class="px-5 py-4 text-slate-400">{{ timeAgo(cart.updated_at) }}</td>
              <td class="px-5 py-4 text-right">
                <button @click="clearCart(cart)" :disabled="clearingId === cart.id"
                  class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 transition hover:bg-rose-100 disabled:opacity-50">
                  {{ clearingId === cart.id ? 'Clearing…' : 'Clear Cart' }}
                </button>
              </td>
            </tr>
            <!-- Expanded items row -->
            <tr v-if="expandedCart === cart.id" class="bg-slate-50">
              <td colspan="5" class="px-5 py-3">
                <table class="w-full text-xs">
                  <thead>
                    <tr class="text-slate-400 uppercase tracking-wider">
                      <th class="pb-2 text-left font-semibold">Product</th>
                      <th class="pb-2 text-left font-semibold">Price</th>
                      <th class="pb-2 text-left font-semibold">Qty</th>
                      <th class="pb-2 text-left font-semibold">Line Total</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-slate-200">
                    <tr v-for="item in cart.items" :key="item.id">
                      <td class="py-1.5 text-slate-700">{{ item.product_name }}</td>
                      <td class="py-1.5 text-slate-500">{{ formatPrice(item.price) }}</td>
                      <td class="py-1.5 text-slate-500">{{ item.quantity }}</td>
                      <td class="py-1.5 text-slate-700 font-medium">{{ formatPrice(item.price * item.quantity) }}</td>
                    </tr>
                  </tbody>
                </table>
              </td>
            </tr>
          </template>
        </tbody>
      </table>
    </div>
  </div>
</template>
