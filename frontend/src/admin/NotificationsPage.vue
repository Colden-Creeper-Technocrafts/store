<script setup lang="ts">
import { ref, onMounted } from 'vue'
import {
  fetchNotificationSettings,
  updateNotificationSettings,
  fetchNotificationLogs,
  type NotificationConfig,
  type NotificationLog,
  type LogsMeta,
} from '../services/adminNotifications'

type Tab = 'email' | 'sms' | 'whatsapp' | 'logs'
const activeTab = ref<Tab>('email')

const isLoading = ref(true)
const isSaving  = ref(false)
const error     = ref('')
const success   = ref('')

const form = ref<NotificationConfig>({
  email: {
    enabled: false,
    events: { order_placed: true, status_changed: true, return_updated: true },
  },
  sms: {
    enabled: false,
    account_sid: '',
    auth_token: '',
    auth_token_masked: '',
    from_number: '',
    events: { order_placed: true, status_changed: true, return_updated: false },
  },
  whatsapp: {
    enabled: false,
    from_number: '',
    events: { order_placed: true, status_changed: false, return_updated: false },
  },
})

// Track whether the admin has typed a new auth token
const newAuthToken = ref('')

// Logs state
const logs     = ref<NotificationLog[]>([])
const logsMeta = ref<LogsMeta | null>(null)
const logsLoading  = ref(false)
const logsPage     = ref(1)
const logsFilter   = ref({ channel: '', event: '', status: '' })

onMounted(async () => {
  try {
    const config = await fetchNotificationSettings()
    form.value = config
  } catch {
    error.value = 'Failed to load notification settings.'
  } finally {
    isLoading.value = false
  }
})

async function save() {
  isSaving.value = true
  error.value   = ''
  success.value = ''
  try {
    const payload: any = {
      email:    form.value.email,
      sms:      { ...form.value.sms },
      whatsapp: form.value.whatsapp,
    }
    // Only send new auth token if the admin typed one
    if (newAuthToken.value.trim()) {
      payload.sms.auth_token = newAuthToken.value.trim()
    } else {
      payload.sms.auth_token = ''
    }
    const updated = await updateNotificationSettings(payload)
    form.value    = updated
    newAuthToken.value = ''
    success.value = 'Notification settings saved.'
  } catch (e: any) {
    error.value = e?.response?.data?.message ?? 'Failed to save settings.'
  } finally {
    isSaving.value = false
  }
}

async function loadLogs() {
  logsLoading.value = true
  try {
    const res = await fetchNotificationLogs({
      ...logsFilter.value,
      page: logsPage.value,
      per_page: 25,
    })
    logs.value     = res.logs
    logsMeta.value = res.meta
  } catch {
    /* silently ignore */
  } finally {
    logsLoading.value = false
  }
}

function openLogsTab() {
  activeTab.value = 'logs'
  if (!logs.value.length) loadLogs()
}

function applyLogsFilter() {
  logsPage.value = 1
  loadLogs()
}

function logsPageChange(p: number) {
  logsPage.value = p
  loadLogs()
}

function channelBadge(channel: string) {
  const map: Record<string, string> = {
    email:     'bg-blue-100 text-blue-700',
    sms:       'bg-violet-100 text-violet-700',
    whatsapp:  'bg-emerald-100 text-emerald-700',
  }
  return map[channel] ?? 'bg-slate-100 text-slate-600'
}

function eventLabel(event: string) {
  const map: Record<string, string> = {
    order_placed:   'Order Placed',
    status_changed: 'Status Changed',
    return_updated: 'Return Updated',
  }
  return map[event] ?? event
}

function formatDate(d: string) {
  return new Date(d).toLocaleString('en-IN', {
    day: '2-digit', month: 'short', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  })
}
</script>

<template>
  <div class="space-y-8">
    <!-- Header -->
    <section class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
      <h1 class="text-4xl font-semibold text-slate-900">Notifications</h1>
      <p class="mt-3 text-slate-600">Configure email and SMS/WhatsApp alerts sent to customers on key order events.</p>
    </section>

    <div v-if="isLoading" class="flex h-32 items-center justify-center text-slate-400">Loading…</div>

    <template v-else>
      <!-- Alerts -->
      <div v-if="error"   class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ error }}</div>
      <div v-if="success" class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ success }}</div>

      <!-- Tabs -->
      <div class="flex gap-2 flex-wrap">
        <button
          v-for="tab in (['email', 'sms', 'whatsapp'] as Tab[])"
          :key="tab"
          @click="activeTab = tab"
          :class="[
            'rounded-full px-5 py-2 text-sm font-semibold transition',
            activeTab === tab
              ? 'bg-slate-900 text-white'
              : 'bg-white border border-slate-200 text-slate-600 hover:border-slate-400'
          ]"
        >{{ { email: 'Email', sms: 'SMS', whatsapp: 'WhatsApp' }[tab] }}</button>
        <button
          @click="openLogsTab"
          :class="[
            'rounded-full px-5 py-2 text-sm font-semibold transition',
            activeTab === 'logs'
              ? 'bg-slate-900 text-white'
              : 'bg-white border border-slate-200 text-slate-600 hover:border-slate-400'
          ]"
        >Notification Logs</button>
      </div>

      <!-- ─── EMAIL TAB ─── -->
      <template v-if="activeTab === 'email'">
        <section class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm space-y-6">
          <div class="flex items-start justify-between gap-4">
            <div>
              <h2 class="text-2xl font-semibold text-slate-900">Email Notifications</h2>
              <p class="mt-1 text-sm text-slate-500">Sent to the customer's email address provided at checkout.</p>
            </div>
            <label class="flex cursor-pointer items-center gap-3">
              <span class="text-sm font-semibold text-slate-700">{{ form.email.enabled ? 'Enabled' : 'Disabled' }}</span>
              <div
                @click="form.email.enabled = !form.email.enabled"
                :class="['relative h-6 w-11 rounded-full transition', form.email.enabled ? 'bg-slate-900' : 'bg-slate-300']"
              >
                <div :class="['absolute top-0.5 left-0.5 h-5 w-5 rounded-full bg-white shadow transition-transform', form.email.enabled ? 'translate-x-5' : 'translate-x-0']"></div>
              </div>
            </label>
          </div>

          <!-- SMTP hint -->
          <div class="rounded-2xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-700">
            Configure SMTP credentials in <code class="font-mono bg-blue-100 px-1 rounded">.env</code> (MAIL_MAILER, MAIL_HOST, MAIL_USERNAME, MAIL_PASSWORD, MAIL_FROM_ADDRESS).
          </div>

          <div v-if="form.email.enabled" class="space-y-3">
            <h3 class="font-semibold text-slate-800">Events</h3>
            <label class="flex cursor-pointer items-center gap-4 rounded-2xl border border-slate-200 p-4 hover:bg-slate-50">
              <input v-model="form.email.events.order_placed" type="checkbox" class="h-5 w-5 rounded border-slate-300" />
              <div>
                <p class="font-semibold text-slate-900">Order Placed</p>
                <p class="text-sm text-slate-500">Sent immediately after a successful order is created</p>
              </div>
            </label>
            <label class="flex cursor-pointer items-center gap-4 rounded-2xl border border-slate-200 p-4 hover:bg-slate-50">
              <input v-model="form.email.events.status_changed" type="checkbox" class="h-5 w-5 rounded border-slate-300" />
              <div>
                <p class="font-semibold text-slate-900">Order Status Changed</p>
                <p class="text-sm text-slate-500">Processing, Shipped (with tracking), Delivered, Cancelled</p>
              </div>
            </label>
            <label class="flex cursor-pointer items-center gap-4 rounded-2xl border border-slate-200 p-4 hover:bg-slate-50">
              <input v-model="form.email.events.return_updated" type="checkbox" class="h-5 w-5 rounded border-slate-300" />
              <div>
                <p class="font-semibold text-slate-900">Return Status Updated</p>
                <p class="text-sm text-slate-500">Approved, Rejected, or Refunded</p>
              </div>
            </label>
          </div>
        </section>
      </template>

      <!-- ─── SMS TAB ─── -->
      <template v-if="activeTab === 'sms'">
        <section class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm space-y-6">
          <div class="flex items-start justify-between gap-4">
            <div>
              <h2 class="text-2xl font-semibold text-slate-900">SMS Notifications</h2>
              <p class="mt-1 text-sm text-slate-500">Sent via Twilio SMS to the customer's phone number.</p>
            </div>
            <label class="flex cursor-pointer items-center gap-3">
              <span class="text-sm font-semibold text-slate-700">{{ form.sms.enabled ? 'Enabled' : 'Disabled' }}</span>
              <div
                @click="form.sms.enabled = !form.sms.enabled"
                :class="['relative h-6 w-11 rounded-full transition', form.sms.enabled ? 'bg-slate-900' : 'bg-slate-300']"
              >
                <div :class="['absolute top-0.5 left-0.5 h-5 w-5 rounded-full bg-white shadow transition-transform', form.sms.enabled ? 'translate-x-5' : 'translate-x-0']"></div>
              </div>
            </label>
          </div>

          <!-- Twilio credentials -->
          <div class="rounded-2xl border border-slate-100 bg-slate-50 p-6 space-y-4">
            <h3 class="font-semibold text-slate-800">Twilio Credentials</h3>
            <div class="grid gap-4 sm:grid-cols-2">
              <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">Account SID</label>
                <input
                  v-model="form.sms.account_sid"
                  type="text"
                  placeholder="ACxxxxxxxxxxxxxxxx"
                  class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-slate-900"
                />
              </div>
              <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">Auth Token</label>
                <input
                  v-model="newAuthToken"
                  type="password"
                  :placeholder="form.sms.auth_token_masked ? form.sms.auth_token_masked + ' (leave blank to keep)' : 'Enter auth token'"
                  class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-slate-900"
                />
              </div>
            </div>
            <div>
              <label class="block text-sm font-semibold text-slate-700 mb-1">From Number</label>
              <input
                v-model="form.sms.from_number"
                type="text"
                placeholder="+15017122661"
                class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-slate-900"
              />
              <p class="mt-1 text-xs text-slate-400">Your Twilio phone number in E.164 format (e.g. +15017122661)</p>
            </div>
          </div>

          <div v-if="form.sms.enabled" class="space-y-3">
            <h3 class="font-semibold text-slate-800">Events</h3>
            <label class="flex cursor-pointer items-center gap-4 rounded-2xl border border-slate-200 p-4 hover:bg-slate-50">
              <input v-model="form.sms.events.order_placed" type="checkbox" class="h-5 w-5 rounded border-slate-300" />
              <div>
                <p class="font-semibold text-slate-900">Order Placed</p>
                <p class="text-sm text-slate-500">Send a confirmation SMS when an order is created</p>
              </div>
            </label>
            <label class="flex cursor-pointer items-center gap-4 rounded-2xl border border-slate-200 p-4 hover:bg-slate-50">
              <input v-model="form.sms.events.status_changed" type="checkbox" class="h-5 w-5 rounded border-slate-300" />
              <div>
                <p class="font-semibold text-slate-900">Order Status Changed</p>
                <p class="text-sm text-slate-500">Notify customers of processing, shipping, delivery, and cancellation</p>
              </div>
            </label>
            <label class="flex cursor-pointer items-center gap-4 rounded-2xl border border-slate-200 p-4 hover:bg-slate-50">
              <input v-model="form.sms.events.return_updated" type="checkbox" class="h-5 w-5 rounded border-slate-300" />
              <div>
                <p class="font-semibold text-slate-900">Return Status Updated</p>
                <p class="text-sm text-slate-500">Notify on return approval, rejection, or refund</p>
              </div>
            </label>
          </div>
        </section>
      </template>

      <!-- ─── WHATSAPP TAB ─── -->
      <template v-if="activeTab === 'whatsapp'">
        <section class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm space-y-6">
          <div class="flex items-start justify-between gap-4">
            <div>
              <h2 class="text-2xl font-semibold text-slate-900">WhatsApp Notifications</h2>
              <p class="mt-1 text-sm text-slate-500">Sent via Twilio WhatsApp API. Uses the same Twilio Account SID / Auth Token as SMS.</p>
            </div>
            <label class="flex cursor-pointer items-center gap-3">
              <span class="text-sm font-semibold text-slate-700">{{ form.whatsapp.enabled ? 'Enabled' : 'Disabled' }}</span>
              <div
                @click="form.whatsapp.enabled = !form.whatsapp.enabled"
                :class="['relative h-6 w-11 rounded-full transition', form.whatsapp.enabled ? 'bg-slate-900' : 'bg-slate-300']"
              >
                <div :class="['absolute top-0.5 left-0.5 h-5 w-5 rounded-full bg-white shadow transition-transform', form.whatsapp.enabled ? 'translate-x-5' : 'translate-x-0']"></div>
              </div>
            </label>
          </div>

          <div class="rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            WhatsApp uses the Twilio sandbox by default (<code class="font-mono">whatsapp:+14155238886</code>). For production, register your own WhatsApp sender.
          </div>

          <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1">WhatsApp From Number</label>
            <input
              v-model="form.whatsapp.from_number"
              type="text"
              placeholder="whatsapp:+14155238886"
              class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-slate-900"
            />
            <p class="mt-1 text-xs text-slate-400">Include the <code class="font-mono">whatsapp:</code> prefix (e.g. whatsapp:+14155238886)</p>
          </div>

          <div v-if="form.whatsapp.enabled" class="space-y-3">
            <h3 class="font-semibold text-slate-800">Events</h3>
            <label class="flex cursor-pointer items-center gap-4 rounded-2xl border border-slate-200 p-4 hover:bg-slate-50">
              <input v-model="form.whatsapp.events.order_placed" type="checkbox" class="h-5 w-5 rounded border-slate-300" />
              <div>
                <p class="font-semibold text-slate-900">Order Placed</p>
                <p class="text-sm text-slate-500">Send a WhatsApp confirmation when an order is created</p>
              </div>
            </label>
            <label class="flex cursor-pointer items-center gap-4 rounded-2xl border border-slate-200 p-4 hover:bg-slate-50">
              <input v-model="form.whatsapp.events.status_changed" type="checkbox" class="h-5 w-5 rounded border-slate-300" />
              <div>
                <p class="font-semibold text-slate-900">Order Status Changed</p>
                <p class="text-sm text-slate-500">Notify on shipping and delivery via WhatsApp</p>
              </div>
            </label>
            <label class="flex cursor-pointer items-center gap-4 rounded-2xl border border-slate-200 p-4 hover:bg-slate-50">
              <input v-model="form.whatsapp.events.return_updated" type="checkbox" class="h-5 w-5 rounded border-slate-300" />
              <div>
                <p class="font-semibold text-slate-900">Return Status Updated</p>
                <p class="text-sm text-slate-500">Notify on return changes via WhatsApp</p>
              </div>
            </label>
          </div>
        </section>
      </template>

      <!-- Save button (not shown on logs tab) -->
      <div v-if="activeTab !== 'logs'" class="flex justify-end">
        <button
          @click="save"
          :disabled="isSaving"
          class="rounded-full bg-slate-900 px-8 py-3 font-semibold text-white transition hover:bg-slate-700 disabled:cursor-not-allowed disabled:opacity-50"
        >{{ isSaving ? 'Saving…' : 'Save Settings' }}</button>
      </div>

      <!-- ─── LOGS TAB ─── -->
      <template v-if="activeTab === 'logs'">
        <section class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm space-y-6">
          <h2 class="text-2xl font-semibold text-slate-900">Notification Logs</h2>

          <!-- Filters -->
          <div class="flex flex-wrap gap-3">
            <select
              v-model="logsFilter.channel"
              class="rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900"
            >
              <option value="">All Channels</option>
              <option value="email">Email</option>
              <option value="sms">SMS</option>
              <option value="whatsapp">WhatsApp</option>
            </select>
            <select
              v-model="logsFilter.event"
              class="rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900"
            >
              <option value="">All Events</option>
              <option value="order_placed">Order Placed</option>
              <option value="status_changed">Status Changed</option>
              <option value="return_updated">Return Updated</option>
            </select>
            <select
              v-model="logsFilter.status"
              class="rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900"
            >
              <option value="">All Statuses</option>
              <option value="sent">Sent</option>
              <option value="failed">Failed</option>
            </select>
            <button
              @click="applyLogsFilter"
              class="rounded-full bg-slate-900 px-5 py-2 text-sm font-semibold text-white hover:bg-slate-700"
            >Filter</button>
          </div>

          <div v-if="logsLoading" class="flex h-24 items-center justify-center text-slate-400">Loading logs…</div>
          <div v-else-if="!logs.length" class="flex h-24 items-center justify-center text-slate-400">No notification logs found.</div>
          <div v-else class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="border-b border-slate-100 text-left text-xs font-semibold uppercase tracking-wider text-slate-400">
                  <th class="pb-3 pr-4">Time</th>
                  <th class="pb-3 pr-4">Channel</th>
                  <th class="pb-3 pr-4">Event</th>
                  <th class="pb-3 pr-4">Order</th>
                  <th class="pb-3 pr-4">Recipient</th>
                  <th class="pb-3">Status</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-50">
                <tr v-for="log in logs" :key="log.id" class="group hover:bg-slate-50">
                  <td class="py-3 pr-4 text-slate-500 whitespace-nowrap">{{ formatDate(log.created_at) }}</td>
                  <td class="py-3 pr-4">
                    <span :class="['rounded-full px-2 py-0.5 text-xs font-semibold', channelBadge(log.channel)]">
                      {{ log.channel.toUpperCase() }}
                    </span>
                  </td>
                  <td class="py-3 pr-4 text-slate-700">{{ eventLabel(log.event) }}</td>
                  <td class="py-3 pr-4 text-slate-500">{{ log.order_id ? '#' + log.order_id : '—' }}</td>
                  <td class="py-3 pr-4 text-slate-600 font-mono text-xs">{{ log.recipient }}</td>
                  <td class="py-3">
                    <span v-if="log.status === 'sent'"   class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-700">Sent</span>
                    <span v-else class="rounded-full bg-rose-100 px-2 py-0.5 text-xs font-semibold text-rose-700" :title="log.error_message ?? ''">Failed</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <div v-if="logsMeta && logsMeta.last_page > 1" class="flex items-center justify-between pt-2">
            <p class="text-sm text-slate-400">
              {{ (logsMeta.current_page - 1) * logsMeta.per_page + 1 }}–{{ Math.min(logsMeta.current_page * logsMeta.per_page, logsMeta.total) }} of {{ logsMeta.total }}
            </p>
            <div class="flex gap-2">
              <button
                :disabled="logsMeta.current_page <= 1"
                @click="logsPageChange(logsMeta!.current_page - 1)"
                class="rounded-full border border-slate-200 px-4 py-1.5 text-sm font-semibold text-slate-600 hover:bg-slate-50 disabled:opacity-40"
              >Prev</button>
              <button
                :disabled="logsMeta.current_page >= logsMeta.last_page"
                @click="logsPageChange(logsMeta!.current_page + 1)"
                class="rounded-full border border-slate-200 px-4 py-1.5 text-sm font-semibold text-slate-600 hover:bg-slate-50 disabled:opacity-40"
              >Next</button>
            </div>
          </div>
        </section>
      </template>
    </template>
  </div>
</template>
