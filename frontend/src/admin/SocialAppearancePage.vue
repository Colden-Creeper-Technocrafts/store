<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import SocialIcon from '../components/SocialIcon.vue'
import {
  fetchSocialLinks, createSocialLink, updateSocialLink, deleteSocialLink,
  SOCIAL_PLATFORMS, type SocialLink,
} from '../services/adminSocialLinks'

const links = ref<SocialLink[]>([])
const isLoading = ref(false)
const isSaving = ref(false)
const errorMessage = ref('')
const successMessage = ref('')
const editingId = ref<number | null>(null)
const showForm = ref(false)

const form = reactive({ name: '', url: '', icon: 'instagram', sort_order: 0 })

function platformLabel(key: string) {
  return SOCIAL_PLATFORMS.find(p => p.key === key)?.label ?? key
}

function resetForm() {
  editingId.value = null
  form.name = ''
  form.url = ''
  form.icon = 'instagram'
  form.sort_order = 0
  errorMessage.value = ''
  showForm.value = false
}

function openAdd() {
  resetForm()
  form.sort_order = links.value.length
  showForm.value = true
}

function openEdit(link: SocialLink) {
  editingId.value = link.id
  form.name = link.name
  form.url = link.url
  form.icon = link.icon
  form.sort_order = link.sort_order
  errorMessage.value = ''
  showForm.value = true
}

function onPlatformChange() {
  if (!form.name || SOCIAL_PLATFORMS.some(p => p.label === form.name)) {
    form.name = platformLabel(form.icon)
  }
}

async function load() {
  isLoading.value = true
  try {
    links.value = await fetchSocialLinks()
  } catch {
    errorMessage.value = 'Failed to load social links.'
  } finally {
    isLoading.value = false
  }
}

async function save() {
  isSaving.value = true
  errorMessage.value = ''
  successMessage.value = ''
  try {
    const payload = { name: form.name, url: form.url, icon: form.icon, sort_order: form.sort_order }
    if (editingId.value) {
      await updateSocialLink(editingId.value, payload)
      successMessage.value = 'Link updated.'
    } else {
      await createSocialLink(payload)
      successMessage.value = 'Link added.'
    }
    await load()
    resetForm()
  } catch (e: any) {
    const errs = e?.response?.data?.errors
    if (errs) {
      const first = Object.values(errs).find((m): m is string[] => Array.isArray(m))
      errorMessage.value = first?.[0] ?? 'Unable to save.'
    } else {
      errorMessage.value = e?.response?.data?.message ?? 'Unable to save.'
    }
  } finally {
    isSaving.value = false
  }
}

async function remove(link: SocialLink) {
  if (!confirm(`Remove "${link.name}"?`)) return
  try {
    await deleteSocialLink(link.id)
    successMessage.value = 'Link removed.'
    await load()
  } catch {
    errorMessage.value = 'Unable to remove link.'
  }
}

onMounted(load)
</script>

<template>
  <section class="space-y-6">
    <div class="rounded-3xl bg-white p-8 shadow-sm border border-slate-200">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-semibold text-slate-900">Social Appearance</h1>
          <p class="mt-1 text-sm text-slate-500">Manage the social media links shown in the store footer.</p>
        </div>
        <button @click="openAdd" class="rounded-full bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-700">
          + Add link
        </button>
      </div>

      <div v-if="successMessage" class="mt-4 rounded-2xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-700">{{ successMessage }}</div>
      <div v-if="errorMessage && !showForm" class="mt-4 rounded-2xl bg-rose-50 border border-rose-200 px-4 py-3 text-sm text-rose-700">{{ errorMessage }}</div>

      <!-- List -->
      <div class="mt-8">
        <div v-if="isLoading" class="text-sm text-slate-500">Loading…</div>
        <div v-else-if="!links.length" class="rounded-2xl border border-dashed border-slate-200 py-12 text-center text-sm text-slate-400">
          No social links yet. Click "Add link" to get started.
        </div>
        <div v-else class="divide-y divide-slate-100 rounded-2xl border border-slate-200 overflow-hidden">
          <div
            v-for="link in links"
            :key="link.id"
            class="flex items-center gap-4 bg-white px-5 py-4 hover:bg-slate-50"
          >
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-slate-100 text-slate-700">
              <SocialIcon :icon="link.icon" class="h-5 w-5" />
            </div>
            <div class="min-w-0 flex-1">
              <p class="text-sm font-medium text-slate-900">{{ link.name }}</p>
              <a :href="link.url" target="_blank" class="text-xs text-slate-400 hover:text-slate-600 truncate block max-w-xs">{{ link.url }}</a>
            </div>
            <span class="shrink-0 rounded-full bg-slate-100 px-2.5 py-0.5 text-xs text-slate-500 font-mono">{{ link.icon }}</span>
            <div class="flex shrink-0 gap-2">
              <button @click="openEdit(link)" class="rounded-full border px-3 py-1 text-sm hover:bg-slate-50">Edit</button>
              <button @click="remove(link)" class="rounded-full border border-rose-200 bg-rose-50 px-3 py-1 text-sm text-rose-700">Remove</button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Add / Edit form -->
    <div v-if="showForm" class="rounded-3xl bg-white p-8 shadow-sm border border-slate-200">
      <p class="mb-6 text-sm font-semibold text-slate-900">{{ editingId ? 'Edit link' : 'Add social link' }}</p>

      <div v-if="errorMessage" class="mb-4 rounded-2xl bg-rose-50 border border-rose-200 px-4 py-3 text-sm text-rose-700">{{ errorMessage }}</div>

      <form @submit.prevent="save" class="space-y-4">
        <div class="grid gap-4 sm:grid-cols-2">
          <div>
            <label class="block text-sm font-medium text-slate-700">Platform</label>
            <select v-model="form.icon" @change="onPlatformChange" class="mt-2 w-full rounded-3xl border px-4 py-3 text-sm">
              <option v-for="p in SOCIAL_PLATFORMS" :key="p.key" :value="p.key">{{ p.label }}</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700">Display name</label>
            <input v-model="form.name" type="text" required placeholder="e.g. Our Instagram" class="mt-2 w-full rounded-3xl border px-4 py-3 text-sm" />
          </div>
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700">URL</label>
          <input v-model="form.url" type="url" required placeholder="https://instagram.com/yourhandle" class="mt-2 w-full rounded-3xl border px-4 py-3 text-sm" />
        </div>
        <div class="flex items-center gap-3 pt-2">
          <button type="submit" :disabled="isSaving" class="rounded-full bg-slate-900 px-6 py-3 text-sm font-semibold text-white disabled:opacity-50">
            {{ isSaving ? 'Saving…' : editingId ? 'Save changes' : 'Add link' }}
          </button>
          <button type="button" @click="resetForm" class="rounded-full border px-6 py-3 text-sm">Cancel</button>
        </div>
      </form>
    </div>
  </section>
</template>
