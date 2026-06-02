<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import type { AdminProduct, loadAdminProducts } from '../services/adminProducts'
import { loadAdminProducts as loadProductsService, createAdminProduct, updateAdminProduct, deleteAdminProduct } from '../services/adminProducts'
import { loadAdminCategories } from '../services/adminCategories'
import SearchableSelect from '../components/SearchableSelect.vue'

const products = ref<AdminProduct[]>([])
const categories = ref([])
const isLoading = ref(false)
const isSaving = ref(false)
const errorMessage = ref('')
const successMessage = ref('')
const editingId = ref<number | null>(null)
const showForm = ref(false)
const page = ref(1)
const perPage = ref(10)
const total = ref(0)
const lastPage = ref(1)
const searchQuery = ref('')
const filterCategory = ref<number | null>(null)
const filterStatus = ref<string | null>(null)
const formRef = ref<HTMLElement | null>(null)

const form = reactive({
  name: '',
  sku: '',
  price: 0,
  quantity: 0,
  category_id: null as number | null,
  status: true,
  description: ''
})

const loadProducts = async () => {
  isLoading.value = true
  try {
    const { products: items, meta } = await loadProductsService({
      page: page.value,
      per_page: perPage.value,
      search: searchQuery.value || undefined,
      category_id: filterCategory.value || undefined,
      status: filterStatus.value !== null ? filterStatus.value : undefined,
    })

    products.value = items
    total.value = meta.total ?? 0
    lastPage.value = meta.last_page ?? 1
  } catch (e) {
    errorMessage.value = 'Unable to load products.'
  } finally {
    isLoading.value = false
  }
}

const flattenCategories = (items: any[], prefix = ''): any[] => {
  return items.flatMap((item) => [
    { id: item.id, name: prefix ? `${prefix} / ${item.name}` : item.name },
    ...flattenCategories(item.children ?? [], prefix ? `${prefix} / ${item.name}` : item.name),
  ])
}

const loadCategories = async () => {
  try {
    const data = await loadAdminCategories()
    categories.value = flattenCategories(data)
  } catch (e) {
    // ignore
  }
}

const resetForm = () => {
  editingId.value = null
  form.name = ''
  form.sku = ''
  form.price = 0
  form.quantity = 0
  form.category_id = null
  form.status = true
  form.description = ''
  successMessage.value = ''
  errorMessage.value = ''
  showForm.value = false
}

const editProduct = (p: AdminProduct) => {
  editingId.value = p.id
  form.name = p.name
  form.sku = p.sku ?? ''
  form.price = p.price ?? 0
  form.quantity = p.quantity ?? 0
  form.category_id = p.category_id ?? null
  form.description = p.description ?? ''
  form.status = p.status ?? true
  showForm.value = true
  setTimeout(() => {
    formRef.value?.scrollIntoView({ behavior: 'smooth', block: 'start' })
  }, 0)
}

const saveProduct = async () => {
  isSaving.value = true
  errorMessage.value = ''
  successMessage.value = ''
  try {
    const payload = {
      name: form.name,
      sku: form.sku,
      price: form.price,
      quantity: form.quantity,
      status: form.status,
      category_id: form.category_id,
      description: form.description,
    }

    if (editingId.value) {
      await updateAdminProduct(editingId.value, payload)
      successMessage.value = 'Product updated.'
    } else {
      await createAdminProduct(payload)
      successMessage.value = 'Product created.'
    }

    await loadProducts()
    resetForm()
  } catch (e) {
    errorMessage.value = 'Unable to save product.'
  } finally {
    isSaving.value = false
  }
}

const removeProduct = async (p: AdminProduct) => {
  if (!confirm(`Delete product "${p.name}"?`)) return
  try {
    await deleteAdminProduct(p.id)
    successMessage.value = 'Product deleted.'
    await loadProducts()
  } catch (e) {
    errorMessage.value = 'Unable to delete product.'
  }
}

const addNew = () => {
  resetForm()
  showForm.value = true
  setTimeout(() => {
    formRef.value?.scrollIntoView({ behavior: 'smooth', block: 'start' })
  }, 0)
}

const doSearch = () => {
  page.value = 1
  loadProducts()
}

const changePage = (p: number) => {
  if (p < 1 || p > lastPage.value) return
  page.value = p
  loadProducts()
}

onMounted(() => {
  loadProducts()
  loadCategories()
})
</script>

<template>
  <section class="space-y-6">
    <div class="rounded-3xl bg-white p-8 shadow-sm border border-slate-200">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm uppercase tracking-[0.24em] text-slate-500">Product master</p>
          <h1 class="mt-3 text-3xl font-semibold text-slate-900">Products</h1>
        </div>
      </div>

      <div class="mt-6">
        <div class="mb-4 flex items-center justify-between">
          <div></div>
          <div>
            <button @click="addNew" class="rounded-full bg-slate-900 px-4 py-2 text-white">Add product</button>
          </div>
        </div>
        <div class="mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
          <div class="flex items-center gap-3">
            <input v-model="searchQuery" @input="doSearch" placeholder="Search products..." type="search" class="rounded-3xl border bg-white px-4 py-2" />
            <div class="w-48">
              <SearchableSelect v-model="filterCategory" :options="categories" placeholder="All categories" @update:modelValue="doSearch" />
            </div>
            <select v-model="filterStatus" @change="doSearch" class="rounded-3xl border bg-white px-4 py-2">
              <option :value="null">All</option>
              <option value="1">Active</option>
              <option value="0">Inactive</option>
            </select>
          </div>
          <div class="flex items-center gap-3">
            <label class="text-sm text-slate-600">Per page</label>
            <select v-model.number="perPage" @change="() => { page = 1; loadProducts() }" class="rounded-3xl border bg-white px-3 py-2">
                <option :value="10">10</option>
                <option :value="20">20</option>
                <option :value="30">30</option>
                <option :value="50">50</option>
            </select>
          </div>
        </div>

        <div v-if="isLoading" class="p-8 text-sm text-slate-500">Loading products...</div>
        <div v-else>
          <div v-if="!products.length" class="p-8 text-sm text-slate-500">No products yet.</div>
          <div v-else>
            <table class="w-full text-left">
              <thead>
                <tr class="text-sm text-slate-600 border-b">
                  <th class="py-2">Name</th>
                  <th class="py-2">SKU</th>
                  <th class="py-2">Price</th>
                  <th class="py-2">Qty</th>
                  <th class="py-2">Category</th>
                  <th class="py-2">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="p in products" :key="p.id" class="border-b hover:bg-slate-50">
                  <td class="py-3">{{ p.name }}</td>
                  <td class="py-3">{{ p.sku ?? '—' }}</td>
                  <td class="py-3">{{ p.price ? '$' + p.price : '—' }}</td>
                  <td class="py-3">{{ p.quantity ?? 0 }}</td>
                  <td class="py-3">{{ p.category_name ?? '—' }}</td>
                  <td class="py-3">
                    <button class="rounded-full border px-3 py-1 mr-2" @click="editProduct(p)">Edit</button>
                    <button class="rounded-full border border-rose-200 bg-rose-50 px-3 py-1 text-rose-700" @click="removeProduct(p)">Delete</button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="mt-6 flex flex-wrap gap-2 items-center">
            <button @click="changePage(page - 1)" :disabled="page <= 1" class="rounded-full border px-3 py-1">Previous</button>
            <template v-for="p in Math.min(7, lastPage)">
              <button @click="changePage(p)" :class="['rounded-full px-3 py-1', { 'bg-slate-900 text-white': p === page, 'border': p !== page }]">{{ p }}</button>
            </template>
            <button :disabled="page >= lastPage" @click="changePage(page + 1)" class="rounded-full border px-3 py-1">Next</button>
          </div>
        </div>

        <div v-if="showForm" ref="formRef" class="mt-12 rounded-3xl border border-slate-200 bg-white p-6">
          <div class="mb-4">
            <p class="text-sm font-semibold text-slate-900">{{ editingId ? 'Edit product' : 'Add new product' }}</p>
          </div>

          <div v-if="errorMessage" class="rounded-2xl bg-rose-50 border border-rose-200 p-4 text-sm text-rose-700">{{ errorMessage }}</div>
          <div v-if="successMessage" class="rounded-2xl bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-700">{{ successMessage }}</div>

          <form @submit.prevent="saveProduct" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-slate-700">Name</label>
              <input v-model="form.name" type="text" required class="mt-2 w-full rounded-3xl border px-4 py-3" />
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700">SKU</label>
              <input v-model="form.sku" type="text" class="mt-2 w-full rounded-3xl border px-4 py-3" />
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700">Price</label>
              <input v-model.number="form.price" type="number" min="0" class="mt-2 w-full rounded-3xl border px-4 py-3" />
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700">Quantity</label>
              <input v-model.number="form.quantity" type="number" min="0" class="mt-2 w-full rounded-3xl border px-4 py-3" />
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700">Category</label>
              <SearchableSelect v-model="form.category_id" :options="categories" placeholder="None" class="mt-2" />
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700">Description</label>
              <textarea v-model="form.description" rows="3" class="mt-2 w-full rounded-3xl border px-4 py-3"></textarea>
            </div>
            <div class="grid gap-4 sm:grid-cols-[1fr_auto] sm:items-end">
              <div>
                <label class="inline-flex items-center gap-3 text-sm font-medium text-slate-700">
                  <input
                    type="checkbox"
                    v-model="form.status"
                    class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-500"
                  />
                  Active
                </label>
              </div>
              <div class="flex gap-3">
                <button type="submit" :disabled="isSaving" class="rounded-full bg-slate-900 px-5 py-3 text-white">{{ editingId ? 'Save changes' : 'Create product' }}</button>
                <button type="button" @click="resetForm" class="rounded-full border px-5 py-3">Cancel</button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </section>
</template>
