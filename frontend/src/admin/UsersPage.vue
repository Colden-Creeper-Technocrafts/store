<script setup lang="ts">
import { ref, reactive, watch } from 'vue'
import { fetchAdminUsers, createAdminUser, type AdminUser } from '../services/adminUsers'

const users = ref<AdminUser[]>([])
const meta = ref({ total: 0, per_page: 20, current_page: 1, last_page: 1 })
const isLoading = ref(false)
const errorMessage = ref('')

const filters = reactive({ search: '', page: 1, per_page: 20 })
const searchInput = ref('')

async function load() {
  isLoading.value = true
  errorMessage.value = ''
  try {
    const data = await fetchAdminUsers(filters)
    users.value = data.users
    meta.value = data.meta
  } catch {
    errorMessage.value = 'Failed to load users.'
  } finally {
    isLoading.value = false
  }
}

function search() { filters.search = searchInput.value; filters.page = 1 }
function clearSearch() { searchInput.value = ''; filters.search = ''; filters.page = 1 }
function prevPage() { if (meta.value.current_page > 1) filters.page = meta.value.current_page - 1 }
function nextPage() { if (meta.value.current_page < meta.value.last_page) filters.page = meta.value.current_page + 1 }

function fmtDate(iso: string) {
  return new Date(iso).toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' })
}

// ── Create user modal ──────────────────────────────────────────────────────
const showModal = ref(false)
const form = reactive({ name: '', email: '', phone: '', role: 'Customer' as 'Admin' | 'Customer' })
const saving = ref(false)
const saveError = ref('')
const saveSuccess = ref('')

function openModal() {
  form.name = ''; form.email = ''; form.phone = ''; form.role = 'Customer'
  saveError.value = ''; saveSuccess.value = ''
  showModal.value = true
}

function closeModal() { showModal.value = false }

async function submitCreate() {
  saveError.value = ''
  saving.value = true
  try {
    const payload: { name: string; email: string; role: 'Admin' | 'Customer'; phone?: string } = {
      name:  form.name.trim(),
      email: form.email.trim(),
      role:  form.role,
    }
    if (form.phone.trim()) payload.phone = form.phone.trim()
    await createAdminUser(payload)
    saveSuccess.value = `Account created. Login credentials sent to ${payload.email}.`
    await load()
  } catch (err: unknown) {
    const e = err as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } }
    if (e.response?.data?.errors) {
      saveError.value = Object.values(e.response.data.errors).flat().join(' ')
    } else {
      saveError.value = e.response?.data?.message ?? 'Failed to create user.'
    }
  } finally {
    saving.value = false
  }
}

const roleStyle: Record<string, string> = {
  Admin:    'bg-purple-100 text-purple-700',
  Customer: 'bg-blue-100 text-blue-700',
}

watch(() => [filters.page, filters.per_page, filters.search], load, { immediate: true })
</script>

<template>
  <div class="space-y-6 pt-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Users</h1>
        <p class="mt-1 text-sm text-slate-500">{{ meta.total }} total users</p>
      </div>
      <button
        @click="openModal"
        class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-700"
      >
        + Create User
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
      <button @click="search" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-700">Search</button>
      <button v-if="filters.search" @click="clearSearch" class="rounded-xl border border-slate-200 px-4 py-2 text-sm text-slate-600 transition hover:bg-slate-50">Clear</button>
      <select v-model="filters.per_page" class="ml-auto rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:outline-none" @change="filters.page = 1">
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
            <th class="px-6 py-3">User</th>
            <th class="px-6 py-3">Role</th>
            <th class="px-6 py-3">Joined</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-if="isLoading">
            <td colspan="3" class="px-6 py-10 text-center text-slate-400">Loading…</td>
          </tr>
          <tr v-else-if="users.length === 0">
            <td colspan="3" class="px-6 py-10 text-center text-slate-400">No users found.</td>
          </tr>
          <tr v-for="u in users" :key="u.id" class="transition hover:bg-slate-50">
            <td class="px-6 py-4">
              <p class="font-medium text-slate-900">{{ u.name }}</p>
              <p class="text-xs text-slate-500">{{ u.email }}</p>
            </td>
            <td class="px-6 py-4">
              <span :class="['rounded-full px-2.5 py-0.5 text-xs font-semibold', roleStyle[u.role] ?? 'bg-slate-100 text-slate-600']">
                {{ u.role }}
              </span>
            </td>
            <td class="px-6 py-4 text-slate-600">{{ fmtDate(u.created_at) }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div v-if="meta.last_page > 1" class="flex items-center justify-between text-sm text-slate-600">
      <span>Page {{ meta.current_page }} of {{ meta.last_page }}</span>
      <div class="flex gap-2">
        <button :disabled="meta.current_page === 1" @click="prevPage" class="rounded-xl border border-slate-200 px-4 py-2 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40">Previous</button>
        <button :disabled="meta.current_page === meta.last_page" @click="nextPage" class="rounded-xl border border-slate-200 px-4 py-2 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40">Next</button>
      </div>
    </div>
  </div>

  <!-- Create User Modal -->
  <transition name="fade">
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4">
      <div class="w-full max-w-md rounded-2xl bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-200 px-6 py-5">
          <h2 class="text-lg font-semibold text-slate-900">Create User</h2>
          <button @click="closeModal" class="text-slate-400 transition hover:text-slate-700">✕</button>
        </div>

        <div class="space-y-4 p-6">
          <div v-if="saveSuccess" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ saveSuccess }}
          </div>

          <template v-if="!saveSuccess">
            <div v-if="saveError" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
              {{ saveError }}
            </div>

            <!-- Role -->
            <div>
              <label class="mb-1 block text-xs font-medium text-slate-600">Role <span class="text-rose-500">*</span></label>
              <div class="flex gap-3">
                <label class="flex flex-1 cursor-pointer items-center gap-2 rounded-xl border px-4 py-2.5 text-sm transition"
                  :class="form.role === 'Customer' ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-200 text-slate-600 hover:bg-slate-50'">
                  <input v-model="form.role" type="radio" value="Customer" class="hidden" />
                  Customer
                </label>
                <label class="flex flex-1 cursor-pointer items-center gap-2 rounded-xl border px-4 py-2.5 text-sm transition"
                  :class="form.role === 'Admin' ? 'border-purple-700 bg-purple-700 text-white' : 'border-slate-200 text-slate-600 hover:bg-slate-50'">
                  <input v-model="form.role" type="radio" value="Admin" class="hidden" />
                  Admin
                </label>
              </div>
            </div>

            <!-- Name -->
            <div>
              <label class="mb-1 block text-xs font-medium text-slate-600">Full Name <span class="text-rose-500">*</span></label>
              <input v-model="form.name" type="text" placeholder="e.g. Priya Sharma"
                class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:border-slate-400 focus:outline-none" />
            </div>

            <!-- Email -->
            <div>
              <label class="mb-1 block text-xs font-medium text-slate-600">Email <span class="text-rose-500">*</span></label>
              <input v-model="form.email" type="email" placeholder="e.g. priya@example.com"
                class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:border-slate-400 focus:outline-none" />
            </div>

            <!-- Phone -->
            <div>
              <label class="mb-1 block text-xs font-medium text-slate-600">Phone <span class="text-slate-400">(optional)</span></label>
              <input v-model="form.phone" type="tel" placeholder="10-digit mobile number"
                class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:border-slate-400 focus:outline-none" />
            </div>

            <p class="text-xs text-slate-400">A strong password will be auto-generated and emailed to the user.</p>
          </template>
        </div>

        <div class="flex justify-end gap-3 border-t border-slate-100 px-6 py-4">
          <button @click="closeModal" class="rounded-xl border border-slate-200 px-4 py-2 text-sm text-slate-600 transition hover:bg-slate-50">
            {{ saveSuccess ? 'Close' : 'Cancel' }}
          </button>
          <button v-if="!saveSuccess" @click="submitCreate" :disabled="saving"
            class="rounded-xl bg-slate-900 px-5 py-2 text-sm font-medium text-white transition hover:bg-slate-700 disabled:opacity-50">
            {{ saving ? 'Creating…' : 'Create User' }}
          </button>
        </div>
      </div>
    </div>
  </transition>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
