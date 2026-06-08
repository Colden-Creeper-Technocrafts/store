<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import type { AdminCoupon, CouponPayload } from '../services/adminCoupons'
import {
  loadAdminCoupons,
  createAdminCoupon,
  updateAdminCoupon,
  deleteAdminCoupon,
} from '../services/adminCoupons'

const coupons = ref<AdminCoupon[]>([])
const isLoading = ref(false)
const isSaving = ref(false)
const errorMessage = ref('')
const successMessage = ref('')
const editingId = ref<number | null>(null)
const showForm = ref(false)

const blankForm = (): CouponPayload & { is_active: boolean } => ({
  code: '',
  description: '',
  discount_type: 'percentage',
  discount_value: 10,
  min_order_amount: null,
  max_discount_amount: null,
  usage_limit: null,
  starts_at: null,
  expires_at: null,
  is_active: true,
})

const form = reactive(blankForm())

const resetForm = () => {
  Object.assign(form, blankForm())
  editingId.value = null
}

const load = async () => {
  isLoading.value = true
  try {
    coupons.value = await loadAdminCoupons()
  } catch {
    errorMessage.value = 'Unable to load coupons.'
  } finally {
    isLoading.value = false
  }
}

onMounted(load)

const openCreate = () => {
  resetForm()
  showForm.value = true
}

const openEdit = (c: AdminCoupon) => {
  editingId.value = c.id
  Object.assign(form, {
    code: c.code,
    description: c.description ?? '',
    discount_type: c.discount_type,
    discount_value: c.discount_value,
    min_order_amount: c.min_order_amount,
    max_discount_amount: c.max_discount_amount,
    usage_limit: c.usage_limit,
    starts_at: c.starts_at ? c.starts_at.slice(0, 16) : null,
    expires_at: c.expires_at ? c.expires_at.slice(0, 16) : null,
    is_active: c.is_active,
  })
  showForm.value = true
}

const save = async () => {
  isSaving.value = true
  errorMessage.value = ''
  successMessage.value = ''
  try {
    const payload: CouponPayload = {
      ...form,
      code: form.code.toUpperCase().trim(),
      description: form.description || null,
      min_order_amount: form.min_order_amount || null,
      max_discount_amount: form.max_discount_amount || null,
      usage_limit: form.usage_limit || null,
      starts_at: form.starts_at || null,
      expires_at: form.expires_at || null,
    }

    if (editingId.value !== null) {
      await updateAdminCoupon(editingId.value, payload)
      successMessage.value = 'Coupon updated.'
    } else {
      await createAdminCoupon(payload)
      successMessage.value = 'Coupon created.'
    }

    showForm.value = false
    resetForm()
    await load()
  } catch (e: any) {
    const errors = e?.response?.data?.errors
    if (errors) {
      errorMessage.value = Object.values(errors).flat().join(' ')
    } else {
      errorMessage.value = e?.response?.data?.message ?? 'Failed to save coupon.'
    }
  } finally {
    isSaving.value = false
  }
}

const toggleActive = async (c: AdminCoupon) => {
  try {
    await updateAdminCoupon(c.id, { is_active: !c.is_active })
    await load()
  } catch {
    errorMessage.value = 'Failed to update coupon status.'
  }
}

const extendExpiry = async (c: AdminCoupon) => {
  const input = prompt(
    'New expiry date (YYYY-MM-DD or YYYY-MM-DDTHH:MM):',
    c.expires_at ? c.expires_at.slice(0, 16) : ''
  )
  if (!input) return
  try {
    await updateAdminCoupon(c.id, { expires_at: input })
    successMessage.value = 'Expiry updated.'
    await load()
  } catch {
    errorMessage.value = 'Failed to update expiry.'
  }
}

const changeUsageLimit = async (c: AdminCoupon) => {
  const input = prompt('New usage limit (leave blank for unlimited):', String(c.usage_limit ?? ''))
  if (input === null) return
  const limit = input.trim() === '' ? null : parseInt(input, 10)
  try {
    await updateAdminCoupon(c.id, { usage_limit: limit })
    successMessage.value = 'Usage limit updated.'
    await load()
  } catch {
    errorMessage.value = 'Failed to update usage limit.'
  }
}

const remove = async (c: AdminCoupon) => {
  if (!confirm(`Delete coupon "${c.code}"? This cannot be undone.`)) return
  try {
    await deleteAdminCoupon(c.id)
    successMessage.value = 'Coupon deleted.'
    await load()
  } catch {
    errorMessage.value = 'Failed to delete coupon.'
  }
}

const formatDate = (d: string | null) => {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' })
}

const statusLabel = (c: AdminCoupon): { text: string; cls: string } => {
  if (!c.is_active) return { text: 'Inactive', cls: 'bg-slate-100 text-slate-600' }
  if (c.expires_at && new Date(c.expires_at) < new Date()) return { text: 'Expired', cls: 'bg-rose-100 text-rose-700' }
  if (c.usage_limit !== null && c.used_count >= c.usage_limit) return { text: 'Exhausted', cls: 'bg-amber-100 text-amber-700' }
  return { text: 'Active', cls: 'bg-emerald-100 text-emerald-700' }
}
</script>

<template>
  <div class="space-y-6 py-6 px-2">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Coupons</h1>
        <p class="text-sm text-slate-500 mt-1">Manage discount codes for your store</p>
      </div>
      <button
        @click="openCreate"
        class="rounded-2xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-700 transition"
      >
        + New Coupon
      </button>
    </div>

    <div v-if="successMessage" class="rounded-2xl bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-800">
      {{ successMessage }}
    </div>
    <div v-if="errorMessage" class="rounded-2xl bg-rose-50 border border-rose-200 p-4 text-sm text-rose-700">
      {{ errorMessage }}
    </div>

    <!-- Create / Edit Form -->
    <div v-if="showForm" class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
      <h2 class="font-semibold text-slate-900">{{ editingId ? 'Edit Coupon' : 'New Coupon' }}</h2>

      <div class="grid gap-4 sm:grid-cols-2">
        <div>
          <label class="block text-xs font-medium text-slate-600 mb-1">Code</label>
          <input
            v-model="form.code"
            required
            type="text"
            placeholder="e.g. SAVE20"
            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm uppercase tracking-wider focus:outline-none focus:border-slate-400"
          />
        </div>
        <div>
          <label class="block text-xs font-medium text-slate-600 mb-1">Description</label>
          <input
            v-model="form.description"
            type="text"
            placeholder="Optional internal note"
            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:border-slate-400"
          />
        </div>
      </div>

      <div class="grid gap-4 sm:grid-cols-2">
        <div>
          <label class="block text-xs font-medium text-slate-600 mb-1">Discount Type</label>
          <select
            v-model="form.discount_type"
            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:border-slate-400"
          >
            <option value="percentage">Percentage (%)</option>
            <option value="fixed">Fixed Amount</option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-medium text-slate-600 mb-1">
            {{ form.discount_type === 'percentage' ? 'Percentage (%)' : 'Discount Amount' }}
          </label>
          <input
            v-model.number="form.discount_value"
            required
            type="number"
            min="0.01"
            step="0.01"
            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:border-slate-400"
          />
        </div>
      </div>

      <div class="grid gap-4 sm:grid-cols-2">
        <div>
          <label class="block text-xs font-medium text-slate-600 mb-1">Min Order Amount <span class="font-normal text-slate-400">(optional)</span></label>
          <input
            v-model.number="form.min_order_amount"
            type="number"
            min="0"
            step="0.01"
            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:border-slate-400"
          />
        </div>
        <div>
          <label class="block text-xs font-medium text-slate-600 mb-1">
            Max Discount Cap <span class="font-normal text-slate-400">(optional, for %)</span>
          </label>
          <input
            v-model.number="form.max_discount_amount"
            type="number"
            min="0"
            step="0.01"
            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:border-slate-400"
          />
        </div>
      </div>

      <div class="grid gap-4 sm:grid-cols-3">
        <div>
          <label class="block text-xs font-medium text-slate-600 mb-1">Usage Limit <span class="font-normal text-slate-400">(optional)</span></label>
          <input
            v-model.number="form.usage_limit"
            type="number"
            min="1"
            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:border-slate-400"
          />
        </div>
        <div>
          <label class="block text-xs font-medium text-slate-600 mb-1">Valid From <span class="font-normal text-slate-400">(optional)</span></label>
          <input
            v-model="form.starts_at"
            type="datetime-local"
            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:border-slate-400"
          />
        </div>
        <div>
          <label class="block text-xs font-medium text-slate-600 mb-1">Expires At <span class="font-normal text-slate-400">(optional)</span></label>
          <input
            v-model="form.expires_at"
            type="datetime-local"
            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:border-slate-400"
          />
        </div>
      </div>

      <div class="flex items-center gap-2">
        <input id="is_active" v-model="form.is_active" type="checkbox" class="h-4 w-4 accent-slate-900" />
        <label for="is_active" class="text-sm text-slate-700">Active</label>
      </div>

      <div class="flex gap-3 pt-2">
        <button
          @click="save"
          :disabled="isSaving"
          class="rounded-2xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-700 transition disabled:opacity-50"
        >
          {{ isSaving ? 'Saving…' : editingId ? 'Update Coupon' : 'Create Coupon' }}
        </button>
        <button
          @click="showForm = false; resetForm()"
          class="rounded-2xl border border-slate-200 px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition"
        >
          Cancel
        </button>
      </div>
    </div>

    <!-- Table -->
    <div class="rounded-3xl border border-slate-200 bg-white shadow-sm overflow-hidden">
      <div v-if="isLoading" class="p-8 text-center text-sm text-slate-400">Loading…</div>
      <div v-else-if="coupons.length === 0" class="p-8 text-center text-sm text-slate-400">No coupons yet. Create one above.</div>
      <table v-else class="w-full text-sm">
        <thead class="border-b border-slate-100 bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
          <tr>
            <th class="px-4 py-3 text-left">Code</th>
            <th class="px-4 py-3 text-left">Discount</th>
            <th class="px-4 py-3 text-left">Usage</th>
            <th class="px-4 py-3 text-left">Valid Until</th>
            <th class="px-4 py-3 text-left">Status</th>
            <th class="px-4 py-3 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="c in coupons" :key="c.id" class="hover:bg-slate-50 transition">
            <td class="px-4 py-3">
              <span class="font-mono font-semibold text-slate-900">{{ c.code }}</span>
              <p v-if="c.description" class="text-xs text-slate-400 mt-0.5">{{ c.description }}</p>
            </td>
            <td class="px-4 py-3 text-slate-700">
              <span v-if="c.discount_type === 'percentage'">{{ c.discount_value }}%</span>
              <span v-else>₹{{ c.discount_value.toFixed(2) }} off</span>
              <p v-if="c.min_order_amount" class="text-xs text-slate-400">Min ₹{{ c.min_order_amount }}</p>
              <p v-if="c.max_discount_amount && c.discount_type === 'percentage'" class="text-xs text-slate-400">
                Cap ₹{{ c.max_discount_amount }}
              </p>
            </td>
            <td class="px-4 py-3 text-slate-600">
              {{ c.used_count }} / {{ c.usage_limit ?? '∞' }}
              <button
                @click="changeUsageLimit(c)"
                class="ml-2 text-xs text-slate-400 hover:text-slate-700 underline"
              >edit</button>
            </td>
            <td class="px-4 py-3 text-slate-600">
              {{ formatDate(c.expires_at) }}
              <button
                @click="extendExpiry(c)"
                class="ml-2 text-xs text-slate-400 hover:text-slate-700 underline"
              >extend</button>
            </td>
            <td class="px-4 py-3">
              <span :class="['rounded-full px-2.5 py-0.5 text-xs font-medium', statusLabel(c).cls]">
                {{ statusLabel(c).text }}
              </span>
            </td>
            <td class="px-4 py-3 text-right space-x-2">
              <button
                @click="toggleActive(c)"
                class="text-xs text-slate-500 hover:text-slate-900 underline"
              >{{ c.is_active ? 'Deactivate' : 'Activate' }}</button>
              <button
                @click="openEdit(c)"
                class="text-xs text-slate-500 hover:text-slate-900 underline"
              >Edit</button>
              <button
                @click="remove(c)"
                class="text-xs text-rose-500 hover:text-rose-700 underline"
              >Delete</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
