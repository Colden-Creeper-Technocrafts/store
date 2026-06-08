<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import type {
  ShippingProvider,
  ShippingZone,
  ShippingZoneLocation,
  ShippingMethod,
  ShippingRate,
} from '../services/adminShipping'
import {
  fetchProviders, updateProvider, validateProvider,
  fetchZones, createZone, updateZone, deleteZone,
  addZoneLocation, removeZoneLocation,
  fetchMethods, createMethod, updateMethod, deleteMethod,
  fetchRates, createRate, updateRate, deleteRate,
} from '../services/adminShipping'

// ── Tab state ─────────────────────────────────────────────────────────────────

const activeTab = ref<'providers' | 'zones' | 'methods'>('providers')

// ── Shared ────────────────────────────────────────────────────────────────────

const error   = ref('')
const success = ref('')
const isBusy  = ref(false)

function flash(msg: string, isError = false) {
  if (isError) { error.value = msg; success.value = '' }
  else         { success.value = msg; error.value = '' }
  setTimeout(() => { error.value = ''; success.value = '' }, 4000)
}

// ─────────────────────────────────────────────────────────────────────────────
// PROVIDERS TAB
// ─────────────────────────────────────────────────────────────────────────────

const providers      = ref<ShippingProvider[]>([])
const editingProvider = ref<ShippingProvider | null>(null)
const providerForm   = reactive<{ settings: string; credentials: string; is_active: boolean }>({
  settings: '{}', credentials: '{}', is_active: false,
})
const validatingId   = ref<number | null>(null)

const loadProviders = async () => {
  providers.value = await fetchProviders()
}

const openProviderEdit = (p: ShippingProvider) => {
  editingProvider.value = p
  providerForm.is_active   = p.is_active
  providerForm.settings    = JSON.stringify(p.settings ?? {}, null, 2)
  providerForm.credentials = '{}'
}

const saveProvider = async () => {
  if (!editingProvider.value) return
  isBusy.value = true
  try {
    let settings: Record<string, unknown> = {}
    let credentials: Record<string, unknown> = {}
    settings    = JSON.parse(providerForm.settings)
    credentials = JSON.parse(providerForm.credentials)

    const updated = await updateProvider(editingProvider.value.id, {
      is_active: providerForm.is_active,
      settings,
      ...(Object.keys(credentials).length ? { credentials } : {}),
    })
    providers.value = providers.value.map(p => p.id === updated.id ? updated : p)
    editingProvider.value = null
    flash('Provider saved.')
  } catch (e: unknown) {
    flash(e instanceof Error ? e.message : 'Invalid JSON in settings or credentials.', true)
  } finally {
    isBusy.value = false
  }
}

const testCredentials = async (id: number) => {
  validatingId.value = id
  try {
    const result = await validateProvider(id)
    flash(result.message, !result.valid)
  } catch {
    flash('Validation request failed.', true)
  } finally {
    validatingId.value = null
  }
}

// ─────────────────────────────────────────────────────────────────────────────
// ZONES TAB
// ─────────────────────────────────────────────────────────────────────────────

const zones          = ref<ShippingZone[]>([])
const showZoneForm   = ref(false)
const editingZone    = ref<ShippingZone | null>(null)
const expandedZoneId = ref<number | null>(null)
const zoneForm       = reactive({ name: '', description: '', is_active: true, sort_order: 0 })
const locationForm   = reactive({ type: 'pincode_prefix' as ShippingZoneLocation['type'], value: '' })

const loadZones = async () => { zones.value = await fetchZones() }

const openZoneCreate = () => {
  editingZone.value = null
  Object.assign(zoneForm, { name: '', description: '', is_active: true, sort_order: 0 })
  showZoneForm.value = true
}

const openZoneEdit = (z: ShippingZone) => {
  editingZone.value = z
  Object.assign(zoneForm, { name: z.name, description: z.description ?? '', is_active: z.is_active, sort_order: z.sort_order })
  showZoneForm.value = true
}

const saveZone = async () => {
  isBusy.value = true
  try {
    if (editingZone.value) {
      const updated = await updateZone(editingZone.value.id, { ...zoneForm })
      zones.value = zones.value.map(z => z.id === updated.id ? updated : z)
    } else {
      const created = await createZone({ ...zoneForm })
      zones.value.push(created)
    }
    showZoneForm.value = false
    flash('Zone saved.')
  } catch {
    flash('Failed to save zone.', true)
  } finally {
    isBusy.value = false
  }
}

const removeZone = async (id: number) => {
  if (!confirm('Delete this zone? This will also remove its locations.')) return
  await deleteZone(id)
  zones.value = zones.value.filter(z => z.id !== id)
  flash('Zone deleted.')
}

const addLocation = async (zoneId: number) => {
  if (!locationForm.value.trim()) return
  isBusy.value = true
  try {
    const loc = await addZoneLocation(zoneId, { type: locationForm.type, value: locationForm.value.trim() })
    const zone = zones.value.find(z => z.id === zoneId)
    if (zone) zone.locations.push(loc)
    locationForm.value = ''
    flash('Location added.')
  } catch {
    flash('Failed to add location.', true)
  } finally {
    isBusy.value = false
  }
}

const removeLocation = async (zoneId: number, loc: ShippingZoneLocation) => {
  await removeZoneLocation(zoneId, loc.id)
  const zone = zones.value.find(z => z.id === zoneId)
  if (zone) zone.locations = zone.locations.filter(l => l.id !== loc.id)
}

// ─────────────────────────────────────────────────────────────────────────────
// METHODS & RATES TAB
// ─────────────────────────────────────────────────────────────────────────────

const methods         = ref<ShippingMethod[]>([])
const showMethodForm  = ref(false)
const editingMethod   = ref<ShippingMethod | null>(null)
const expandedMethodId = ref<number | null>(null)
const methodRates     = ref<Record<number, ShippingRate[]>>({})
const methodForm      = reactive({
  shipping_provider_id: 0, name: '', code: '',
  description: '', min_days: 1, max_days: 7, is_active: true, sort_order: 0,
})

const rateForm = reactive({
  shipping_zone_id: null as number | null,
  min_weight_kg: 0, max_weight_kg: null as number | null,
  min_order_amount: null as number | null, max_order_amount: null as number | null,
  base_rate: 0, per_kg_rate: 0, is_free: false, sort_order: 0,
})
const editingRateId = ref<number | null>(null)

const loadMethods = async () => { methods.value = await fetchMethods() }

const openMethodCreate = () => {
  editingMethod.value = null
  Object.assign(methodForm, {
    shipping_provider_id: providers.value[0]?.id ?? 0,
    name: '', code: '', description: '',
    min_days: 1, max_days: 7, is_active: true, sort_order: 0,
  })
  showMethodForm.value = true
}

const openMethodEdit = (m: ShippingMethod) => {
  editingMethod.value = m
  Object.assign(methodForm, {
    shipping_provider_id: m.shipping_provider_id,
    name: m.name, code: m.code, description: m.description ?? '',
    min_days: m.min_days, max_days: m.max_days, is_active: m.is_active, sort_order: m.sort_order,
  })
  showMethodForm.value = true
}

const saveMethod = async () => {
  isBusy.value = true
  try {
    if (editingMethod.value) {
      const updated = await updateMethod(editingMethod.value.id, { ...methodForm })
      methods.value = methods.value.map(m => m.id === updated.id ? updated : m)
    } else {
      const created = await createMethod({ ...methodForm })
      methods.value.push(created)
    }
    showMethodForm.value = false
    flash('Method saved.')
  } catch {
    flash('Failed to save method.', true)
  } finally {
    isBusy.value = false
  }
}

const removeMethod = async (id: number) => {
  if (!confirm('Delete this shipping method?')) return
  await deleteMethod(id)
  methods.value = methods.value.filter(m => m.id !== id)
  flash('Method deleted.')
}

const toggleMethodRates = async (methodId: number) => {
  if (expandedMethodId.value === methodId) {
    expandedMethodId.value = null
    return
  }
  expandedMethodId.value = methodId
  if (!methodRates.value[methodId]) {
    methodRates.value[methodId] = await fetchRates(methodId)
  }
}

const resetRateForm = () => {
  Object.assign(rateForm, {
    shipping_zone_id: null, min_weight_kg: 0, max_weight_kg: null,
    min_order_amount: null, max_order_amount: null,
    base_rate: 0, per_kg_rate: 0, is_free: false, sort_order: 0,
  })
  editingRateId.value = null
}

const editRate = (r: ShippingRate) => {
  editingRateId.value = r.id
  Object.assign(rateForm, {
    shipping_zone_id: r.shipping_zone_id, min_weight_kg: r.min_weight_kg,
    max_weight_kg: r.max_weight_kg, min_order_amount: r.min_order_amount,
    max_order_amount: r.max_order_amount, base_rate: r.base_rate,
    per_kg_rate: r.per_kg_rate, is_free: r.is_free, sort_order: r.sort_order,
  })
}

const saveRate = async (methodId: number) => {
  isBusy.value = true
  try {
    if (editingRateId.value) {
      const updated = await updateRate(editingRateId.value, { ...rateForm })
      methodRates.value[methodId] = (methodRates.value[methodId] ?? []).map(r => r.id === updated.id ? updated : r)
    } else {
      const created = await createRate(methodId, { ...rateForm } as Omit<ShippingRate, 'id' | 'shipping_method_id' | 'zone'>)
      methodRates.value[methodId] = [...(methodRates.value[methodId] ?? []), created]
    }
    resetRateForm()
    flash('Rate saved.')
  } catch {
    flash('Failed to save rate.', true)
  } finally {
    isBusy.value = false
  }
}

const removeRate = async (methodId: number, rateId: number) => {
  await deleteRate(rateId)
  methodRates.value[methodId] = (methodRates.value[methodId] ?? []).filter(r => r.id !== rateId)
}

// ── Bootstrap ─────────────────────────────────────────────────────────────────

onMounted(async () => {
  await Promise.all([loadProviders(), loadZones(), loadMethods()])
})
</script>

<template>
  <div class="space-y-6 p-6">
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-bold text-slate-900">Shipping</h1>
    </div>

    <!-- Flash messages -->
    <div v-if="error" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ error }}</div>
    <div v-if="success" class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">{{ success }}</div>

    <!-- Tabs -->
    <div class="flex gap-1 rounded-xl border border-slate-200 bg-slate-100 p-1 w-fit">
      <button
        v-for="tab in ([{ key: 'providers', label: 'Providers' }, { key: 'zones', label: 'Zones' }, { key: 'methods', label: 'Methods & Rates' }] as const)"
        :key="tab.key"
        @click="activeTab = tab.key"
        :class="[
          'rounded-lg px-5 py-2 text-sm font-medium transition',
          activeTab === tab.key ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'
        ]"
      >{{ tab.label }}</button>
    </div>

    <!-- ── PROVIDERS TAB ───────────────────────────────────────────────────── -->
    <div v-if="activeTab === 'providers'" class="space-y-4">
      <div
        v-for="p in providers"
        :key="p.id"
        class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"
      >
        <div class="flex items-start justify-between gap-4">
          <div>
            <div class="flex items-center gap-3">
              <h3 class="text-lg font-semibold text-slate-900">{{ p.name }}</h3>
              <span :class="['rounded-full px-2 py-0.5 text-xs font-medium', p.is_active ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-500']">
                {{ p.is_active ? 'Active' : 'Inactive' }}
              </span>
              <span v-if="p.has_credentials" class="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700">Credentials set</span>
            </div>
            <p class="mt-1 text-sm text-slate-500">{{ p.description }}</p>
            <p class="mt-1 text-xs text-slate-400">{{ p.methods_count }} method(s)</p>
          </div>
          <div class="flex shrink-0 gap-2">
            <button
              @click="testCredentials(p.id)"
              :disabled="validatingId === p.id"
              class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-600 hover:bg-slate-50 disabled:opacity-50"
            >{{ validatingId === p.id ? 'Testing…' : 'Test' }}</button>
            <button
              @click="openProviderEdit(p)"
              class="rounded-lg bg-slate-900 px-3 py-1.5 text-sm font-medium text-white hover:bg-slate-700"
            >Configure</button>
          </div>
        </div>

        <!-- Provider edit form -->
        <div v-if="editingProvider?.id === p.id" class="mt-6 space-y-4 border-t border-slate-100 pt-4">
          <label class="flex items-center gap-3">
            <input type="checkbox" v-model="providerForm.is_active" class="h-4 w-4 rounded" />
            <span class="text-sm font-medium text-slate-700">Active</span>
          </label>

          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Settings (JSON)</label>
            <textarea
              v-model="providerForm.settings"
              rows="6"
              class="w-full rounded-xl border border-slate-200 px-3 py-2 font-mono text-xs text-slate-700 focus:border-slate-400 focus:outline-none"
            />
          </div>

          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">
              Credentials (JSON) — leave <code class="text-xs">{}</code> to keep existing
            </label>
            <textarea
              v-model="providerForm.credentials"
              rows="4"
              class="w-full rounded-xl border border-slate-200 px-3 py-2 font-mono text-xs text-slate-700 focus:border-slate-400 focus:outline-none"
              placeholder='{ "email": "...", "password": "..." }'
            />
          </div>

          <div class="flex gap-2">
            <button @click="saveProvider" :disabled="isBusy"
              class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700 disabled:opacity-50">
              {{ isBusy ? 'Saving…' : 'Save' }}
            </button>
            <button @click="editingProvider = null"
              class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">
              Cancel
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ── ZONES TAB ──────────────────────────────────────────────────────── -->
    <div v-if="activeTab === 'zones'" class="space-y-4">
      <div class="flex justify-end">
        <button @click="openZoneCreate"
          class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">
          + New Zone
        </button>
      </div>

      <!-- Zone form -->
      <div v-if="showZoneForm" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
        <h3 class="font-semibold text-slate-900">{{ editingZone ? 'Edit Zone' : 'New Zone' }}</h3>
        <div class="grid gap-4 sm:grid-cols-2">
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Name</label>
            <input v-model="zoneForm.name" type="text"
              class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none" />
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Sort Order</label>
            <input v-model.number="zoneForm.sort_order" type="number"
              class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none" />
          </div>
          <div class="sm:col-span-2">
            <label class="mb-1 block text-sm font-medium text-slate-700">Description</label>
            <input v-model="zoneForm.description" type="text"
              class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none" />
          </div>
          <label class="flex items-center gap-2 sm:col-span-2">
            <input type="checkbox" v-model="zoneForm.is_active" class="h-4 w-4 rounded" />
            <span class="text-sm text-slate-700">Active</span>
          </label>
        </div>
        <div class="flex gap-2">
          <button @click="saveZone" :disabled="isBusy"
            class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700 disabled:opacity-50">
            {{ isBusy ? 'Saving…' : 'Save' }}
          </button>
          <button @click="showZoneForm = false"
            class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">
            Cancel
          </button>
        </div>
      </div>

      <!-- Zones list -->
      <div v-for="z in zones" :key="z.id" class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between p-5">
          <div>
            <div class="flex items-center gap-2">
              <span class="font-semibold text-slate-900">{{ z.name }}</span>
              <span :class="['rounded-full px-2 py-0.5 text-xs font-medium', z.is_active ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-500']">
                {{ z.is_active ? 'Active' : 'Inactive' }}
              </span>
            </div>
            <p v-if="z.description" class="mt-0.5 text-sm text-slate-500">{{ z.description }}</p>
            <p class="mt-0.5 text-xs text-slate-400">{{ z.locations.length }} location(s)</p>
          </div>
          <div class="flex gap-2">
            <button @click="expandedZoneId = expandedZoneId === z.id ? null : z.id"
              class="rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-medium text-slate-600 hover:bg-slate-50">
              {{ expandedZoneId === z.id ? 'Hide' : 'Locations' }}
            </button>
            <button @click="openZoneEdit(z)"
              class="rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-medium text-slate-600 hover:bg-slate-50">Edit</button>
            <button @click="removeZone(z.id)"
              class="rounded-lg border border-red-200 px-3 py-1.5 text-sm font-medium text-red-600 hover:bg-red-50">Delete</button>
          </div>
        </div>

        <!-- Locations panel -->
        <div v-if="expandedZoneId === z.id" class="border-t border-slate-100 p-5 space-y-3">
          <div class="flex flex-wrap gap-2">
            <span
              v-for="loc in z.locations" :key="loc.id"
              class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs"
            >
              <span class="text-slate-400">{{ loc.type }}</span>
              <span class="font-medium text-slate-700">{{ loc.value }}</span>
              <button @click="removeLocation(z.id, loc)" class="text-slate-400 hover:text-red-500">×</button>
            </span>
            <span v-if="!z.locations.length" class="text-sm text-slate-400">No locations yet.</span>
          </div>

          <div class="flex gap-2">
            <select v-model="locationForm.type"
              class="rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none">
              <option value="pincode_prefix">Pincode prefix (3 digits)</option>
              <option value="pincode">Exact pincode</option>
              <option value="state">State name</option>
            </select>
            <input v-model="locationForm.value" type="text" placeholder="e.g. 110 or Delhi"
              class="flex-1 rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none" />
            <button @click="addLocation(z.id)" :disabled="isBusy"
              class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700 disabled:opacity-50">
              Add
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ── METHODS & RATES TAB ────────────────────────────────────────────── -->
    <div v-if="activeTab === 'methods'" class="space-y-4">
      <div class="flex justify-end">
        <button @click="openMethodCreate"
          class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">
          + New Method
        </button>
      </div>

      <!-- Method form -->
      <div v-if="showMethodForm" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
        <h3 class="font-semibold text-slate-900">{{ editingMethod ? 'Edit Method' : 'New Method' }}</h3>
        <div class="grid gap-4 sm:grid-cols-2">
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Provider</label>
            <select v-model.number="methodForm.shipping_provider_id"
              class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none">
              <option v-for="p in providers" :key="p.id" :value="p.id">{{ p.name }}</option>
            </select>
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Name</label>
            <input v-model="methodForm.name" type="text"
              class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none" />
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Code</label>
            <input v-model="methodForm.code" type="text" placeholder="e.g. standard"
              class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none" />
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="mb-1 block text-sm font-medium text-slate-700">Min days</label>
              <input v-model.number="methodForm.min_days" type="number" min="0"
                class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none" />
            </div>
            <div>
              <label class="mb-1 block text-sm font-medium text-slate-700">Max days</label>
              <input v-model.number="methodForm.max_days" type="number" min="0"
                class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none" />
            </div>
          </div>
          <div class="sm:col-span-2">
            <label class="mb-1 block text-sm font-medium text-slate-700">Description</label>
            <input v-model="methodForm.description" type="text"
              class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none" />
          </div>
          <label class="flex items-center gap-2">
            <input type="checkbox" v-model="methodForm.is_active" class="h-4 w-4 rounded" />
            <span class="text-sm text-slate-700">Active</span>
          </label>
        </div>
        <div class="flex gap-2">
          <button @click="saveMethod" :disabled="isBusy"
            class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700 disabled:opacity-50">
            {{ isBusy ? 'Saving…' : 'Save' }}
          </button>
          <button @click="showMethodForm = false"
            class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">
            Cancel
          </button>
        </div>
      </div>

      <!-- Methods list -->
      <div v-for="m in methods" :key="m.id" class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between p-5">
          <div>
            <div class="flex items-center gap-2">
              <span class="font-semibold text-slate-900">{{ m.name }}</span>
              <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-500">{{ m.provider?.name }}</span>
              <span :class="['rounded-full px-2 py-0.5 text-xs font-medium', m.is_active ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-500']">
                {{ m.is_active ? 'Active' : 'Inactive' }}
              </span>
            </div>
            <p class="mt-0.5 text-sm text-slate-500">{{ m.min_days }}–{{ m.max_days }} days · code: <code class="text-xs">{{ m.code }}</code></p>
          </div>
          <div class="flex gap-2">
            <button @click="toggleMethodRates(m.id)"
              class="rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-medium text-slate-600 hover:bg-slate-50">
              {{ expandedMethodId === m.id ? 'Hide Rates' : 'Rates' }}
            </button>
            <button @click="openMethodEdit(m)"
              class="rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-medium text-slate-600 hover:bg-slate-50">Edit</button>
            <button @click="removeMethod(m.id)"
              class="rounded-lg border border-red-200 px-3 py-1.5 text-sm font-medium text-red-600 hover:bg-red-50">Delete</button>
          </div>
        </div>

        <!-- Rates panel -->
        <div v-if="expandedMethodId === m.id" class="border-t border-slate-100 p-5 space-y-4">
          <!-- Existing rates -->
          <div class="overflow-x-auto">
            <table v-if="(methodRates[m.id] ?? []).length" class="w-full text-sm">
              <thead>
                <tr class="text-left text-xs text-slate-500">
                  <th class="pb-2 pr-4">Zone</th>
                  <th class="pb-2 pr-4">Weight (kg)</th>
                  <th class="pb-2 pr-4">Order (₹)</th>
                  <th class="pb-2 pr-4">Base ₹</th>
                  <th class="pb-2 pr-4">Per kg ₹</th>
                  <th class="pb-2 pr-4">Free?</th>
                  <th class="pb-2 pr-4">Sort</th>
                  <th class="pb-2"></th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="r in methodRates[m.id]" :key="r.id" class="text-slate-700">
                  <td class="py-2 pr-4">{{ r.zone?.name ?? 'All zones' }}</td>
                  <td class="py-2 pr-4">{{ r.min_weight_kg }}–{{ r.max_weight_kg ?? '∞' }}</td>
                  <td class="py-2 pr-4">{{ r.min_order_amount ?? '0' }}–{{ r.max_order_amount ?? '∞' }}</td>
                  <td class="py-2 pr-4">{{ r.base_rate }}</td>
                  <td class="py-2 pr-4">{{ r.per_kg_rate }}</td>
                  <td class="py-2 pr-4">{{ r.is_free ? 'Yes' : 'No' }}</td>
                  <td class="py-2 pr-4">{{ r.sort_order }}</td>
                  <td class="py-2">
                    <div class="flex gap-2">
                      <button @click="editRate(r)" class="text-xs text-slate-500 hover:text-slate-900">Edit</button>
                      <button @click="removeRate(m.id, r.id)" class="text-xs text-red-500 hover:text-red-700">Del</button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
            <p v-else class="text-sm text-slate-400">No rates yet.</p>
          </div>

          <!-- Rate form -->
          <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-3">
            <h4 class="text-sm font-semibold text-slate-700">{{ editingRateId ? 'Edit Rate' : 'Add Rate' }}</h4>
            <div class="grid gap-3 sm:grid-cols-3">
              <div>
                <label class="mb-1 block text-xs text-slate-600">Zone</label>
                <select v-model="rateForm.shipping_zone_id"
                  class="w-full rounded-lg border border-slate-200 px-2 py-1.5 text-sm focus:border-slate-400 focus:outline-none">
                  <option :value="null">All zones (catch-all)</option>
                  <option v-for="z in zones" :key="z.id" :value="z.id">{{ z.name }}</option>
                </select>
              </div>
              <div>
                <label class="mb-1 block text-xs text-slate-600">Min weight kg</label>
                <input v-model.number="rateForm.min_weight_kg" type="number" step="0.1" min="0"
                  class="w-full rounded-lg border border-slate-200 px-2 py-1.5 text-sm focus:border-slate-400 focus:outline-none" />
              </div>
              <div>
                <label class="mb-1 block text-xs text-slate-600">Max weight kg</label>
                <input v-model="rateForm.max_weight_kg" type="number" step="0.1" min="0" placeholder="∞"
                  class="w-full rounded-lg border border-slate-200 px-2 py-1.5 text-sm focus:border-slate-400 focus:outline-none" />
              </div>
              <div>
                <label class="mb-1 block text-xs text-slate-600">Min order ₹</label>
                <input v-model="rateForm.min_order_amount" type="number" min="0" placeholder="Any"
                  class="w-full rounded-lg border border-slate-200 px-2 py-1.5 text-sm focus:border-slate-400 focus:outline-none" />
              </div>
              <div>
                <label class="mb-1 block text-xs text-slate-600">Max order ₹</label>
                <input v-model="rateForm.max_order_amount" type="number" min="0" placeholder="Any"
                  class="w-full rounded-lg border border-slate-200 px-2 py-1.5 text-sm focus:border-slate-400 focus:outline-none" />
              </div>
              <div>
                <label class="mb-1 block text-xs text-slate-600">Base rate ₹</label>
                <input v-model.number="rateForm.base_rate" type="number" step="0.5" min="0"
                  class="w-full rounded-lg border border-slate-200 px-2 py-1.5 text-sm focus:border-slate-400 focus:outline-none" />
              </div>
              <div>
                <label class="mb-1 block text-xs text-slate-600">Per kg ₹</label>
                <input v-model.number="rateForm.per_kg_rate" type="number" step="0.5" min="0"
                  class="w-full rounded-lg border border-slate-200 px-2 py-1.5 text-sm focus:border-slate-400 focus:outline-none" />
              </div>
              <div>
                <label class="mb-1 block text-xs text-slate-600">Sort order</label>
                <input v-model.number="rateForm.sort_order" type="number" min="0"
                  class="w-full rounded-lg border border-slate-200 px-2 py-1.5 text-sm focus:border-slate-400 focus:outline-none" />
              </div>
              <label class="flex items-center gap-2 self-end pb-2">
                <input type="checkbox" v-model="rateForm.is_free" class="h-4 w-4 rounded" />
                <span class="text-sm text-slate-700">Free shipping</span>
              </label>
            </div>
            <div class="flex gap-2">
              <button @click="saveRate(m.id)" :disabled="isBusy"
                class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700 disabled:opacity-50">
                {{ isBusy ? 'Saving…' : (editingRateId ? 'Update Rate' : 'Add Rate') }}
              </button>
              <button v-if="editingRateId" @click="resetRateForm"
                class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">
                Cancel
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
