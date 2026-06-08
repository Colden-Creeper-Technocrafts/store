<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { fetchAnalyticsSummary, type AnalyticsSummary } from '../services/adminAnalytics'

const data = ref<AnalyticsSummary | null>(null)
const isLoading = ref(true)
const errorMessage = ref('')

onMounted(async () => {
  try {
    data.value = await fetchAnalyticsSummary()
  } catch {
    errorMessage.value = 'Failed to load analytics.'
  } finally {
    isLoading.value = false
  }
})

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

function fmt(val: number) {
  if (val >= 100000) return `₹${(val / 100000).toFixed(1)}L`
  if (val >= 1000)   return `₹${(val / 1000).toFixed(1)}K`
  return `₹${val.toFixed(2)}`
}

function fmtExact(val: number) {
  return `₹${val.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`
}

function fmtDate(iso: string) {
  return new Date(iso).toLocaleDateString('en-IN', { day: '2-digit', month: 'short' })
}

function changeBadge(pct: number | null) {
  if (pct === null) return { label: 'No prior data', cls: 'text-slate-400' }
  const sign = pct >= 0 ? '+' : ''
  return {
    label: `${sign}${pct}% vs prev 30d`,
    cls: pct >= 0 ? 'text-emerald-600' : 'text-rose-500',
  }
}

// Revenue bar chart: normalize bars relative to the max day
const trendBars = computed(() => {
  const points = data.value?.revenue_trend ?? []
  const max = Math.max(...points.map((p) => Number(p.revenue)), 1)
  return points.map((p) => ({
    ...p,
    pct: Math.max((Number(p.revenue) / max) * 100, 2),
  }))
})

// Order status totals for breakdown
const statusEntries = computed(() => {
  const breakdown = data.value?.order_status_breakdown ?? {}
  const order = ['pending', 'processing', 'shipped', 'delivered', 'cancelled']
  return order
    .filter((s) => breakdown[s] !== undefined)
    .map((s) => ({ status: s, count: breakdown[s] }))
})
</script>

<template>
  <div v-if="isLoading" class="flex h-64 items-center justify-center text-slate-400">
    Loading analytics…
  </div>

  <div v-else-if="errorMessage" class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
    {{ errorMessage }}
  </div>

  <template v-else-if="data">
    <!-- ── KPI Cards ───────────────────────────────────────────────────────── -->
    <section class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
      <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div>
          <p class="text-sm uppercase tracking-widest text-slate-500">Store analytics</p>
          <h1 class="mt-3 text-4xl font-semibold text-slate-900">Dashboard</h1>
          <p class="mt-2 text-sm text-slate-400">Last 30 days vs previous 30 days</p>
        </div>
      </div>

      <div class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <!-- Revenue -->
        <div class="rounded-3xl bg-slate-50 p-6">
          <p class="text-sm font-medium text-slate-500">Revenue (30d)</p>
          <p class="mt-4 text-3xl font-semibold text-slate-900">{{ fmt(data.kpis.revenue.period) }}</p>
          <p class="mt-1 text-xs text-slate-400">All-time: {{ fmt(data.kpis.revenue.total) }}</p>
          <p :class="['mt-2 text-sm font-medium', changeBadge(data.kpis.revenue.change_pct).cls]">
            {{ changeBadge(data.kpis.revenue.change_pct).label }}
          </p>
        </div>

        <!-- Orders -->
        <div class="rounded-3xl bg-slate-50 p-6">
          <p class="text-sm font-medium text-slate-500">Orders (30d)</p>
          <p class="mt-4 text-3xl font-semibold text-slate-900">{{ data.kpis.orders.period.toLocaleString() }}</p>
          <p class="mt-1 text-xs text-slate-400">All-time: {{ data.kpis.orders.total.toLocaleString() }}</p>
          <p :class="['mt-2 text-sm font-medium', changeBadge(data.kpis.orders.change_pct).cls]">
            {{ changeBadge(data.kpis.orders.change_pct).label }}
          </p>
        </div>

        <!-- Customers -->
        <div class="rounded-3xl bg-slate-50 p-6">
          <p class="text-sm font-medium text-slate-500">Customers</p>
          <p class="mt-4 text-3xl font-semibold text-slate-900">{{ data.kpis.customers.total.toLocaleString() }}</p>
          <p class="mt-1 text-xs text-slate-400">All registered</p>
          <p class="mt-2 text-sm font-medium text-emerald-600">
            +{{ data.kpis.customers.new_period }} new this period
          </p>
        </div>

        <!-- AOV -->
        <div class="rounded-3xl bg-slate-50 p-6">
          <p class="text-sm font-medium text-slate-500">Avg. Order Value</p>
          <p class="mt-4 text-3xl font-semibold text-slate-900">{{ fmt(data.kpis.aov.period) }}</p>
          <p class="mt-1 text-xs text-slate-400">30d avg (all-time: {{ fmt(data.kpis.aov.total) }})</p>
          <p class="mt-2 text-sm text-slate-400">per paid order</p>
        </div>
      </div>
    </section>

    <!-- ── Revenue Trend + Status Breakdown ──────────────────────────────── -->
    <section class="grid gap-4 xl:grid-cols-[2fr_1fr]">
      <!-- Bar chart -->
      <div class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
        <p class="text-sm uppercase tracking-widest text-slate-500">Revenue trend</p>
        <h2 class="mt-2 text-2xl font-semibold text-slate-900">Last 30 days</h2>

        <div v-if="trendBars.length === 0" class="mt-8 text-sm text-slate-400">
          No paid orders in this period yet.
        </div>
        <div v-else class="mt-8 flex h-40 items-end gap-1">
          <div
            v-for="bar in trendBars"
            :key="bar.date"
            class="group relative flex-1"
            :style="{ height: bar.pct + '%' }"
          >
            <div class="h-full w-full rounded-t-sm bg-slate-800 transition-colors group-hover:bg-emerald-500" />
            <!-- Tooltip -->
            <div class="pointer-events-none absolute bottom-full left-1/2 mb-2 hidden -translate-x-1/2 whitespace-nowrap rounded-lg border border-slate-200 bg-white px-2 py-1 text-xs shadow-md group-hover:block">
              <p class="font-semibold text-slate-900">{{ fmtExact(Number(bar.revenue)) }}</p>
              <p class="text-slate-500">{{ bar.orders }} order{{ bar.orders !== 1 ? 's' : '' }}</p>
              <p class="text-slate-400">{{ bar.date }}</p>
            </div>
          </div>
        </div>
        <div class="mt-2 flex justify-between text-xs text-slate-400">
          <span>{{ trendBars[0]?.date ?? '' }}</span>
          <span>{{ trendBars[trendBars.length - 1]?.date ?? '' }}</span>
        </div>
      </div>

      <!-- Order status breakdown -->
      <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <p class="text-sm uppercase tracking-widest text-slate-500">Order status</p>
        <h2 class="mt-2 text-xl font-semibold text-slate-900">All time</h2>

        <div v-if="statusEntries.length === 0" class="mt-6 text-sm text-slate-400">No orders yet.</div>
        <div v-else class="mt-6 space-y-3">
          <div
            v-for="entry in statusEntries"
            :key="entry.status"
            class="flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-3"
          >
            <span
              :class="['rounded-full px-2.5 py-1 text-xs font-medium capitalize', orderStatusColors[entry.status] ?? 'bg-slate-100 text-slate-700']"
            >
              {{ entry.status }}
            </span>
            <span class="text-sm font-semibold text-slate-900">{{ entry.count }}</span>
          </div>
        </div>

        <!-- Inventory alerts -->
        <div v-if="data.out_of_stock_count > 0 || data.low_stock.length > 0" class="mt-6 border-t border-slate-100 pt-4">
          <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Stock alerts</p>
          <p v-if="data.out_of_stock_count > 0" class="mt-2 rounded-xl bg-rose-50 px-3 py-2 text-xs font-medium text-rose-700">
            {{ data.out_of_stock_count }} product{{ data.out_of_stock_count !== 1 ? 's' : '' }} out of stock
          </p>
          <div v-for="p in data.low_stock" :key="p.id" class="mt-1 flex items-center justify-between rounded-xl bg-yellow-50 px-3 py-2 text-xs">
            <span class="text-yellow-900 truncate max-w-[160px]">{{ p.name }}</span>
            <span class="font-semibold text-yellow-700 ml-2">{{ p.quantity }} left</span>
          </div>
        </div>
      </div>
    </section>

    <!-- ── Recent Orders + Top Products ──────────────────────────────────── -->
    <section class="grid gap-4 xl:grid-cols-[3fr_2fr]">
      <!-- Recent orders -->
      <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <p class="text-sm uppercase tracking-widest text-slate-500">Recent orders</p>
        <h2 class="mt-2 text-xl font-semibold text-slate-900">Latest 8</h2>

        <div v-if="data.recent_orders.length === 0" class="mt-6 text-sm text-slate-400">No orders yet.</div>
        <div v-else class="mt-4 overflow-hidden rounded-2xl border border-slate-100">
          <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
              <tr>
                <th class="px-4 py-2">Order</th>
                <th class="px-4 py-2">Customer</th>
                <th class="px-4 py-2">Status</th>
                <th class="px-4 py-2 text-right">Total</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="order in data.recent_orders" :key="order.id" class="hover:bg-slate-50">
                <td class="px-4 py-3">
                  <p class="font-medium text-slate-900">#{{ order.id }}</p>
                  <p class="text-xs text-slate-400">{{ fmtDate(order.created_at) }}</p>
                </td>
                <td class="px-4 py-3 text-slate-600">{{ order.user?.name ?? order.shipping_name }}</td>
                <td class="px-4 py-3">
                  <span :class="['rounded-full px-2 py-0.5 text-xs font-medium capitalize', orderStatusColors[order.status] ?? 'bg-slate-100 text-slate-700']">
                    {{ order.status }}
                  </span>
                </td>
                <td class="px-4 py-3 text-right font-semibold text-slate-900">{{ fmtExact(order.total) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Top products -->
      <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <p class="text-sm uppercase tracking-widest text-slate-500">Top products</p>
        <h2 class="mt-2 text-xl font-semibold text-slate-900">Last 30 days</h2>

        <div v-if="data.top_products.length === 0" class="mt-6 text-sm text-slate-400">No sales data yet.</div>
        <div v-else class="mt-4 space-y-2">
          <div
            v-for="(product, i) in data.top_products"
            :key="product.name"
            class="flex items-center gap-3 rounded-2xl bg-slate-50 px-4 py-3"
          >
            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-slate-200 text-xs font-bold text-slate-600">
              {{ i + 1 }}
            </span>
            <div class="min-w-0 flex-1">
              <p class="truncate text-sm font-medium text-slate-900">{{ product.name }}</p>
              <p class="text-xs text-slate-500">{{ product.units_sold }} units sold</p>
            </div>
            <p class="shrink-0 text-sm font-semibold text-slate-900">{{ fmt(Number(product.revenue)) }}</p>
          </div>
        </div>
      </div>
    </section>
  </template>
</template>
