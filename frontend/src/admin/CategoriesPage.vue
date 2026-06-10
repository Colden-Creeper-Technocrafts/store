<script setup lang="ts">
import { computed, onMounted, reactive, ref, watchEffect } from 'vue'
import CategoryTreeItem from './CategoryTreeItem.vue'
import type { AdminCategory } from '../services/adminCategories'
import {
  createAdminCategory,
  deleteAdminCategory,
  loadAdminCategories,
  updateAdminCategory,
  uploadCategoryImage,
} from '../services/adminCategories'

const categories = ref<AdminCategory[]>([])
const searchQuery = ref('')
const isLoading = ref(false)
const isSaving = ref(false)
const errorMessage = ref('')
const successMessage = ref('')
const editingCategoryId = ref<number | null>(null)
const form = reactive({
  name: '',
  slug: '',
  description: '',
  image: '',
  parent_category_id: null as number | null,
  sort_order: 0,
  is_active: true,
})

// Image upload state
const imageFileInput = ref<HTMLInputElement | null>(null)
const imageFile      = ref<File | null>(null)
const imagePreview   = ref<string | null>(null)
const imageCleared   = ref(false)
const displayImage   = computed(() => imagePreview.value || form.image || null)

function onImageSelect(e: Event) {
  const file = (e.target as HTMLInputElement).files?.[0] ?? null
  imageFile.value    = file
  imageCleared.value = false
  if (file) imagePreview.value = URL.createObjectURL(file)
}

function removeImage() {
  imageFile.value    = null
  imagePreview.value = null
  imageCleared.value = true
  form.image         = ''
  if (imageFileInput.value) imageFileInput.value.value = ''
}

const loadCategories = async (): Promise<void> => {
  isLoading.value = true
  errorMessage.value = ''

  try {
    categories.value = await loadAdminCategories()
  } catch (error) {
    errorMessage.value = 'Unable to load categories.'
  } finally {
    isLoading.value = false
  }
}

const flattenCategories = (items: AdminCategory[], prefix = ''): AdminCategory[] => {
  return items.flatMap((item) => [
    {
      ...item,
      name: prefix ? `${prefix} / ${item.name}` : item.name,
    },
    ...flattenCategories(item.children ?? [], prefix ? `${prefix} / ${item.name}` : item.name),
  ])
}

const normalizeSearchQuery = (value: string) => value.trim().toLowerCase()

const categoryMatches = (item: AdminCategory, query: string): boolean => {
  const text = `${item.name} ${item.slug} ${item.description ?? ''}`.toLowerCase()
  return text.includes(query)
}

const filterCategories = (items: AdminCategory[], query: string): AdminCategory[] => {
  return items.flatMap((item) => {
    const children = filterCategories(item.children ?? [], query)
    if (categoryMatches(item, query) || children.length) {
      return [{ ...item, children }]
    }
    return []
  })
}

const filteredCategories = computed(() => {
  const query = normalizeSearchQuery(searchQuery.value)
  return query ? filterCategories(categories.value, query) : categories.value
})

const parentOptions = computed(() => flattenCategories(categories.value).filter((option) => option.id !== editingCategoryId.value))

const resetForm = (): void => {
  editingCategoryId.value = null
  form.name = ''
  form.slug = ''
  form.description = ''
  form.image = ''
  form.parent_category_id = null
  form.sort_order = 0
  form.is_active = true
  successMessage.value = ''
  errorMessage.value = ''
  imageFile.value    = null
  imagePreview.value = null
  imageCleared.value = false
  if (imageFileInput.value) imageFileInput.value.value = ''
}

const fillForm = (category: AdminCategory): void => {
  editingCategoryId.value = category.id
  form.name = category.name
  form.slug = category.slug
  form.description = category.description ?? ''
  form.image = category.image ?? ''
  form.parent_category_id = category.parent_category_id ?? null
  form.sort_order = category.sort_order ?? 0
  form.is_active = category.is_active
  successMessage.value = ''
  errorMessage.value = ''
  imageFile.value    = null
  imagePreview.value = null
  imageCleared.value = false
  if (imageFileInput.value) imageFileInput.value.value = ''
}

const saveCategory = async (): Promise<void> => {
  isSaving.value = true
  errorMessage.value = ''
  successMessage.value = ''

  try {
    const cleared = imageCleared.value

    const payload = {
      name: form.name,
      slug: form.slug,
      description: form.description,
      parent_category_id: form.parent_category_id,
      sort_order: form.sort_order,
      is_active: form.is_active,
      ...(cleared ? { image: null } : {}),
    }

    let savedId: number

    if (editingCategoryId.value) {
      const updated = await updateAdminCategory(editingCategoryId.value, payload)
      savedId = updated.id
      successMessage.value = 'Category updated successfully.'
    } else {
      const created = await createAdminCategory(payload)
      savedId = created.id
      successMessage.value = 'Category created successfully.'
    }

    // Upload image after save so we always have the category ID
    if (imageFile.value) {
      await uploadCategoryImage(savedId, imageFile.value)
    }

    await loadCategories()
    resetForm()
  } catch (error) {
    errorMessage.value = 'Unable to save category. Please check your input.'
  } finally {
    isSaving.value = false
  }
}

const editCategory = (category: AdminCategory): void => {
  fillForm(category)
}

const removeCategory = async (category: AdminCategory): Promise<void> => {
  if (!confirm(`Delete category "${category.name}"?`)) {
    return
  }

  errorMessage.value = ''
  successMessage.value = ''

  try {
    await deleteAdminCategory(category.id)
    successMessage.value = 'Category deleted successfully.'
    await loadCategories()
  } catch (error) {
    errorMessage.value = 'Unable to delete category.'
  }
}

watchEffect(() => {
  if (!editingCategoryId.value) {
    return
  }

  const current = flattenCategories(categories.value).find((item) => item.id === editingCategoryId.value)

  if (!current) {
    editingCategoryId.value = null
  }
})

onMounted(() => {
  loadCategories()
})
</script>

<template>
  <section class="space-y-6">
    <div class="rounded-3xl bg-white p-8 shadow-sm border border-slate-200">
      <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <p class="text-sm uppercase tracking-[0.24em] text-slate-500">Category management</p>
          <h1 class="mt-3 text-3xl font-semibold text-slate-900">Categories</h1>
          <p class="mt-2 text-slate-600">Create, edit, and delete product categories for your storefront.</p>
        </div>
      </div>

      <div class="mt-6 grid gap-6 xl:grid-cols-[1fr_360px]">
        <div class="rounded-3xl border border-slate-200 bg-slate-50 p-6">
          <div class="mb-4 flex flex-col gap-4">
            <div>
              <p class="text-sm font-semibold text-slate-900">Category list</p>
              <p class="mt-1 text-sm text-slate-500">Manage existing categories and their hierarchy.</p>
            </div>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
              <label class="w-full">
                <span class="sr-only">Search categories</span>
                <input
                  v-model="searchQuery"
                  type="search"
                  placeholder="Search categories..."
                  class="w-full rounded-3xl border border-slate-200 bg-white px-4 py-3 text-slate-900 outline-none focus:border-slate-400"
                />
              </label>
              <button
                v-if="searchQuery"
                type="button"
                @click="searchQuery = ''"
                class="rounded-full border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
              >
                Clear
              </button>
            </div>
          </div>

          <div v-if="isLoading" class="rounded-3xl border border-dashed border-slate-300 bg-white p-8 text-sm text-slate-500">
            Loading categories...
          </div>

          <div v-else-if="!categories.length" class="rounded-3xl border border-dashed border-slate-300 bg-white p-8 text-sm text-slate-500">
            No categories available yet.
          </div>

          <div v-else-if="!filteredCategories.length" class="rounded-3xl border border-dashed border-slate-300 bg-white p-8 text-sm text-slate-500">
            No categories match your search.
          </div>
          <div v-else class="space-y-4">
            <CategoryTreeItem
              v-for="category in filteredCategories"
              :key="category.id"
              :category="category"
              :level="0"
              :forceOpen="Boolean(searchQuery)"
              @edit-category="editCategory"
              @delete-category="removeCategory"
            />
          </div>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-6">
          <div class="mb-4">
            <p class="text-sm font-semibold text-slate-900">{{ editingCategoryId ? 'Edit category' : 'Add new category' }}</p>
            <p class="mt-1 text-sm text-slate-500">Use this form to create or update a category.</p>
          </div>

          <div v-if="errorMessage" class="rounded-2xl bg-rose-50 border border-rose-200 p-4 text-sm text-rose-700">{{ errorMessage }}</div>
          <div v-if="successMessage" class="rounded-2xl bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-700">{{ successMessage }}</div>

          <form @submit.prevent="saveCategory" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-slate-700">Name</label>
              <input
                v-model="form.name"
                type="text"
                class="mt-2 w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-slate-900 outline-none focus:border-slate-400"
                required
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-slate-700">Slug</label>
              <input
                v-model="form.slug"
                type="text"
                class="mt-2 w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-slate-900 outline-none focus:border-slate-400"
                required
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-slate-700">Parent category</label>
              <select
                v-model="form.parent_category_id"
                class="mt-2 w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-slate-900 outline-none focus:border-slate-400"
              >
                <option :value="null">None</option>
                <option
                  v-for="option in parentOptions"
                  :key="option.id"
                  :value="option.id"
                >
                  {{ option.name }}
                </option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-slate-700">Description</label>
              <textarea
                v-model="form.description"
                rows="3"
                class="mt-2 w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-slate-900 outline-none focus:border-slate-400"
              ></textarea>
            </div>

            <div>
              <label class="block text-sm font-medium text-slate-700">Category Image</label>

              <input
                ref="imageFileInput"
                type="file"
                accept="image/jpg,image/jpeg,image/png,image/gif,image/webp"
                class="hidden"
                @change="onImageSelect"
              />

              <div
                class="relative mt-2 flex items-center justify-center overflow-hidden rounded-2xl border-2 border-dashed border-slate-200 bg-slate-50"
                :class="displayImage ? 'border-solid border-slate-300 bg-white' : ''"
                style="min-height: 200px;"
              >
                <img
                  v-if="displayImage"
                  :src="displayImage"
                  alt="Category image preview"
                  class="absolute inset-0 h-full w-full object-cover"
                />
                <p v-else class="text-xs text-slate-400">No image selected</p>

                <button
                  v-if="displayImage"
                  type="button"
                  @click="removeImage"
                  class="absolute right-2 top-2 flex h-6 w-6 items-center justify-center rounded-full bg-rose-100 text-rose-600 hover:bg-rose-200"
                  title="Remove image"
                >✕</button>
              </div>

              <button
                type="button"
                @click="imageFileInput?.click()"
                class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
              >
                {{ displayImage ? 'Replace Image' : 'Upload Image' }}
              </button>
              <p class="mt-1 px-1 text-xs text-slate-400">JPG, PNG, GIF or WebP · Max 3 MB</p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
              <div>
                <label class="block text-sm font-medium text-slate-700">Sort order</label>
                <input
                  v-model.number="form.sort_order"
                  type="number"
                  min="0"
                  class="mt-2 w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-slate-900 outline-none focus:border-slate-400"
                />
              </div>
              <div>
                <label class="inline-flex items-center gap-3 text-sm font-medium text-slate-700">
                  <input
                    type="checkbox"
                    v-model="form.is_active"
                    class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-500"
                  />
                  Active
                </label>
              </div>
            </div>

            <div class="flex flex-wrap gap-3 pt-2">
              <button
                type="submit"
                class="rounded-full bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800"
                :disabled="isSaving"
              >
                {{ editingCategoryId ? 'Save changes' : 'Create category' }}
              </button>
              <button
                type="button"
                @click="resetForm"
                class="rounded-full border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
              >
                Cancel
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </section>
</template>
