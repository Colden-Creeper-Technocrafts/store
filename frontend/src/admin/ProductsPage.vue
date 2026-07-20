<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import type { AdminProduct, AdminProductImage, AdminProductVariant } from '../services/adminProducts'
import { loadAdminProducts as loadProductsService, createAdminProduct, updateAdminProduct, deleteAdminProduct, loadAdminProductVariants, createAdminProductVariant, updateAdminProductVariant, deleteAdminProductVariant, loadAdminProductImages, createAdminProductImage, updateAdminProductImage, deleteAdminProductImage, adjustProductStock, checkSlugPrefix } from '../services/adminProducts'
import { loadAdminCategories } from '../services/adminCategories'
import SearchableSelect from '../components/SearchableSelect.vue'

const products = ref<AdminProduct[]>([])
const categories = ref<{ id: number; name: string }[]>([])
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
const filterStockStatus = ref<string | null>(null)
const adjustingProductId = ref<number | null>(null)
const adjustQty = ref(0)
const isAdjusting = ref(false)
const formRef = ref<HTMLElement | null>(null)
const variants = ref<AdminProductVariant[]>([])
const variantLoading = ref(false)
const editingVariantId = ref<number | null>(null)
const showVariantForm = ref(false)
const variantImages = ref<Record<number, AdminProductImage[]>>({})
const variantImageFiles = ref<Record<number, File | null>>({})
const variantImageInputs = ref<Record<number, HTMLInputElement | null>>({})
const expandedVariantId = ref<number | null>(null)

const form = reactive({
  name: '',
  sku: '',
  price: 0,
  quantity: 0,
  category_id: null as number | null,
  status: true,
  short_description: '',
  description: ''
})

const skuChecking = ref(false)
const takenSkus = ref<string[]>([])
const skuUserEdited = ref(false)

function toSlug(name: string): string {
  return name.toLowerCase().trim().replace(/[^a-z0-9\s-]/g, '').replace(/\s+/g, '-').replace(/-+/g, '-').replace(/^-|-$/g, '')
}

async function runSkuCheck() {
  takenSkus.value = []
  if (!form.sku) return
  skuChecking.value = true
  try {
    const result = await checkSlugPrefix(form.sku, editingId.value)
    takenSkus.value = result.taken
  } catch {
    takenSkus.value = []
  } finally {
    skuChecking.value = false
  }
}

function onNameChange() {
  if (!editingId.value && !skuUserEdited.value) {
    form.sku = toSlug(form.name)
    runSkuCheck()
  }
}

function onSkuChange() {
  skuUserEdited.value = true
  runSkuCheck()
}

const variantForm = reactive({
  sku: '',
  price: 0,
  sale_price: null as number | null,
  quantity: 0,
  weight: null as number | null,
  status: true,
  is_default: false,
  options: '' as string,
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
      stock_status: filterStockStatus.value || undefined,
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
  form.short_description = ''
  form.description = ''
  takenSkus.value = []
  skuChecking.value = false
  skuUserEdited.value = false
  successMessage.value = ''
  errorMessage.value = ''
  showForm.value = false
  resetVariants()
  resetVariantImages()
}

const resetVariantForm = () => {
  editingVariantId.value = null
  showVariantForm.value = false
  variantForm.sku = ''
  variantForm.price = 0
  variantForm.sale_price = null
  variantForm.quantity = 0
  variantForm.weight = null
  variantForm.status = true
  variantForm.is_default = false
  variantForm.options = ''
}

const resetVariants = () => {
  variants.value = []
  variantLoading.value = false
  resetVariantForm()
}

const resetVariantImages = () => {
  variantImages.value = {}
  variantImageFiles.value = {}
  expandedVariantId.value = null
}

const syncDefaultVariantToForm = () => {
  const defaultVariant = variants.value.find((variant) => variant.is_default)

  if (defaultVariant) {
    form.sku = defaultVariant.sku ?? ''
    form.price = defaultVariant.price ?? 0
    form.quantity = defaultVariant.quantity ?? 0
    form.status = defaultVariant.status ?? true
  }
}

const loadVariants = async (productId: number) => {
  variantLoading.value = true
  try {
    variants.value = await loadAdminProductVariants(productId)
    syncDefaultVariantToForm()
  } catch (e) {
    variants.value = []
  } finally {
    variantLoading.value = false
  }
}

const editProduct = async (p: AdminProduct) => {
  editingId.value = p.id
  form.name = p.name
  form.sku = p.sku ?? ''
  skuUserEdited.value = true
  form.price = p.price ?? 0
  form.quantity = p.quantity ?? 0
  form.category_id = p.category_id ?? null
  form.short_description = p.short_description ?? ''
  form.description = p.description ?? ''
  form.status = p.status ?? true
  showForm.value = true
  resetVariants()
  resetVariantImages()
  await loadVariants(p.id)
  setTimeout(() => {
    formRef.value?.scrollIntoView({ behavior: 'smooth', block: 'start' })
  }, 0)
}

const openVariantForm = () => {
  if (!editingId.value) {
    return
  }
  resetVariantForm()
  showVariantForm.value = true
}

const editVariant = (variant: AdminProductVariant) => {
  editingVariantId.value = variant.id
  variantForm.sku = variant.sku ?? ''
  variantForm.price = variant.price ?? 0
  variantForm.sale_price = variant.sale_price ?? null
  variantForm.quantity = variant.quantity ?? 0
  variantForm.weight = variant.weight ?? null
  variantForm.status = variant.status ?? true
  variantForm.is_default = variant.is_default ?? false
  variantForm.options = typeof variant.options === 'string' ? variant.options : JSON.stringify(variant.options ?? {})
  showVariantForm.value = true
}

const saveVariant = async () => {
  if (!editingId.value) {
    return
  }

  isSaving.value = true
  errorMessage.value = ''
  successMessage.value = ''

  try {
    let options = null

    if (variantForm.options) {
      try {
        options = JSON.parse(variantForm.options)
      } catch {
        errorMessage.value = 'Variant options must be valid JSON.'
        isSaving.value = false
        return
      }
    }

    const payload = {
      sku: variantForm.sku,
      price: variantForm.price,
      sale_price: variantForm.sale_price,
      quantity: variantForm.quantity,
      weight: variantForm.weight,
      status: variantForm.status,
      is_default: variantForm.is_default,
      options,
    }

    if (editingVariantId.value) {
      await updateAdminProductVariant(editingId.value, editingVariantId.value, payload)
      successMessage.value = 'Variant updated.'
    } else {
      await createAdminProductVariant(editingId.value, payload)
      successMessage.value = 'Variant created.'
    }

    await loadVariants(editingId.value)
    resetVariantForm()
  } catch (e) {
    errorMessage.value = 'Unable to save variant.'
  } finally {
    isSaving.value = false
  }
}

const removeVariant = async (variant: AdminProductVariant) => {
  if (!editingId.value || !confirm(`Delete variant "${variant.sku ?? variant.id}"?`)) return

  try {
    await deleteAdminProductVariant(editingId.value, variant.id)
    successMessage.value = 'Variant deleted.'
    await loadVariants(editingId.value)
  } catch (e) {
    errorMessage.value = 'Unable to delete variant.'
  }
}

const loadVariantImages = async (variantId: number) => {
  if (!editingId.value) return
  try {
    const all = await loadAdminProductImages(editingId.value)
    variantImages.value[variantId] = all.filter((img) => img.product_variant_id === variantId)
  } catch {
    variantImages.value[variantId] = []
  }
}

const toggleVariantImages = async (variantId: number) => {
  if (expandedVariantId.value === variantId) {
    expandedVariantId.value = null
    return
  }
  expandedVariantId.value = variantId
  if (!variantImages.value[variantId]) {
    await loadVariantImages(variantId)
  }
}

const onVariantImageSelected = (variantId: number, event: Event) => {
  const target = event.target as HTMLInputElement
  variantImageFiles.value[variantId] = target.files?.[0] ?? null
}

const uploadVariantImage = async (variantId: number) => {
  if (!editingId.value || !variantImageFiles.value[variantId]) return
  isSaving.value = true
  errorMessage.value = ''
  try {
    const existing = variantImages.value[variantId] ?? []
    await createAdminProductImage(editingId.value, {
      image: variantImageFiles.value[variantId]!,
      variant_id: variantId,
      sort_order: existing.length,
      is_primary: existing.length === 0,
    })
    variantImageFiles.value[variantId] = null
    const input = variantImageInputs.value[variantId]
    if (input) input.value = ''
    await loadVariantImages(variantId)
    successMessage.value = 'Image uploaded.'
  } catch (e: any) {
    const resp = e?.response?.data
    const firstError = resp?.errors ? (Object.values(resp.errors) as string[][]).find((m) => m.length) : null
    errorMessage.value = firstError?.[0] ?? 'Unable to upload image.'
  } finally {
    isSaving.value = false
  }
}

const setPrimaryVariantImage = async (image: AdminProductImage) => {
  if (!editingId.value) return
  try {
    await updateAdminProductImage(editingId.value, image.id, { is_primary: true, sort_order: image.sort_order })
    await loadVariantImages(image.product_variant_id)
    successMessage.value = 'Primary image set.'
  } catch {
    errorMessage.value = 'Unable to update primary image.'
  }
}

const deleteVariantImage = async (image: AdminProductImage) => {
  if (!editingId.value || !confirm('Delete this image?')) return
  try {
    await deleteAdminProductImage(editingId.value, image.id)
    await loadVariantImages(image.product_variant_id)
    successMessage.value = 'Image deleted.'
  } catch {
    errorMessage.value = 'Unable to delete image.'
  }
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
      short_description: form.short_description || null,
      description: form.description || null,
    }

    if (editingId.value) {
      await updateAdminProduct(editingId.value, payload)
      successMessage.value = 'Product updated.'
      await loadVariants(editingId.value)
    } else {
      await createAdminProduct(payload)
      successMessage.value = 'Product created.'
    }

    await loadProducts()
    resetForm()
  } catch (e: any) {
    const response = e?.response?.data
    if (response?.errors?.slug?.length) {
      errorMessage.value = response.errors.slug[0]
    } else if (response?.errors && typeof response.errors === 'object') {
      const firstError = Object.values(response.errors).find((messages) => Array.isArray(messages) && messages.length)
      errorMessage.value = Array.isArray(firstError) ? firstError[0] : 'Unable to save product.'
    } else if (response?.message) {
      errorMessage.value = response.message
    } else {
      errorMessage.value = 'Unable to save product.'
    }
    console.error(e)
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

const stockBadge = (qty: number | null | undefined) => {
  const q = qty ?? 0
  if (q <= 0)  return { label: 'Out of stock', cls: 'bg-rose-100 text-rose-800' }
  if (q <= 5)  return { label: 'Low stock',    cls: 'bg-yellow-100 text-yellow-800' }
  return              { label: 'In stock',      cls: 'bg-emerald-100 text-emerald-800' }
}

const openAdjust = (p: AdminProduct) => {
  adjustingProductId.value = p.id
  adjustQty.value = p.quantity ?? 0
}

const cancelAdjust = () => {
  adjustingProductId.value = null
}

const saveAdjust = async (p: AdminProduct) => {
  isAdjusting.value = true
  errorMessage.value = ''
  try {
    const updated = await adjustProductStock(p.id, adjustQty.value)
    const idx = products.value.findIndex((x) => x.id === p.id)
    const existing = products.value[idx]
    if (idx !== -1 && existing) products.value[idx] = { ...existing, quantity: updated.quantity } as AdminProduct
    adjustingProductId.value = null
    successMessage.value = 'Stock updated.'
  } catch {
    errorMessage.value = 'Failed to update stock.'
  } finally {
    isAdjusting.value = false
  }
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
              <option :value="null">All statuses</option>
              <option value="1">Active</option>
              <option value="0">Inactive</option>
            </select>
            <select v-model="filterStockStatus" @change="doSearch" class="rounded-3xl border bg-white px-4 py-2">
              <option :value="null">All stock</option>
              <option value="in_stock">In stock</option>
              <option value="low_stock">Low stock (≤5)</option>
              <option value="out_of_stock">Out of stock</option>
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
                  <th class="py-2">Stock</th>
                  <th class="py-2">Status</th>
                  <th class="py-2">Category</th>
                  <th class="py-2">Actions</th>
                </tr>
              </thead>
              <tbody>
                <template v-for="p in products" :key="p.id">
                  <tr class="border-b hover:bg-slate-50">
                    <td class="py-3">{{ p.name }}</td>
                    <td class="py-3">{{ p.sku ?? '—' }}</td>
                    <td class="py-3">{{ p.price ? '₹' + p.price : '—' }}</td>
                    <td class="py-3">
                      <div class="flex items-center gap-2">
                        <span class="font-medium text-slate-800">{{ p.quantity ?? 0 }}</span>
                        <span :class="['rounded-full px-2 py-0.5 text-xs font-medium', stockBadge(p.quantity).cls]">
                          {{ stockBadge(p.quantity).label }}
                        </span>
                        <button
                          class="rounded-full border border-slate-200 px-2 py-0.5 text-xs text-slate-500 hover:bg-slate-50"
                          @click="openAdjust(p)"
                        >±</button>
                      </div>
                    </td>
                    <td class="py-3">
                      <span :class="['inline-flex rounded-full px-2 py-1 text-xs font-semibold', p.status ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800']">
                        {{ p.status ? 'Active' : 'Inactive' }}
                      </span>
                    </td>
                    <td class="py-3">{{ p.category_name ?? '—' }}</td>
                    <td class="py-3">
                      <button class="rounded-full border px-3 py-1 mr-2" @click="editProduct(p)">Edit</button>
                      <button class="rounded-full border border-rose-200 bg-rose-50 px-3 py-1 text-rose-700" @click="removeProduct(p)">Delete</button>
                    </td>
                  </tr>
                  <!-- Inline stock adjust row -->
                  <tr v-if="adjustingProductId === p.id" class="bg-yellow-50 border-b">
                    <td colspan="7" class="px-3 py-3">
                      <div class="flex items-center gap-3">
                        <span class="text-sm font-medium text-slate-700">Adjust stock for <strong>{{ p.name }}</strong>:</span>
                        <input
                          v-model.number="adjustQty"
                          type="number"
                          min="0"
                          class="w-24 rounded-xl border border-slate-300 px-3 py-1.5 text-sm focus:outline-none"
                        />
                        <button
                          :disabled="isAdjusting"
                          @click="saveAdjust(p)"
                          class="rounded-xl bg-slate-900 px-4 py-1.5 text-sm text-white disabled:opacity-50"
                        >Save</button>
                        <button @click="cancelAdjust" class="rounded-xl border px-4 py-1.5 text-sm text-slate-600">Cancel</button>
                      </div>
                    </td>
                  </tr>
                </template>
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
            <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">
              Pricing and inventory are managed as the product's default variant.
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
              <div>
                <label class="block text-sm font-medium text-slate-700">Name</label>
                <input v-model="form.name" type="text" required class="mt-2 w-full rounded-3xl border px-4 py-3" @change="onNameChange" />
                <div v-if="takenSkus.length" class="mt-2">
                  <p class="mb-1 text-xs text-slate-500">SKUs already taken with this prefix:</p>
                  <div class="flex flex-wrap gap-1">
                    <span
                      v-for="s in takenSkus"
                      :key="s"
                      :class="[
                        'inline-block rounded-full px-2.5 py-0.5 font-mono text-xs',
                        s === form.sku ? 'bg-rose-100 text-rose-700 font-semibold' : 'bg-slate-100 text-slate-600'
                      ]"
                    >{{ s }}</span>
                  </div>
                </div>
                <p v-else-if="form.sku" class="mt-1 text-xs text-emerald-600">SKU available.</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-slate-700">SKU</label>
                <input v-model="form.sku" type="text" class="mt-2 w-full rounded-3xl border px-4 py-3" @change="onSkuChange" />
              </div>
            </div>

            <div class="grid gap-4 lg:grid-cols-3">
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
            </div>

            <div>
              <label class="block text-sm font-medium text-slate-700">Short description <span class="font-normal text-slate-400">(shown on storefront cards)</span></label>
              <textarea v-model="form.short_description" rows="2" class="mt-2 w-full rounded-3xl border px-4 py-3"></textarea>
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

          <div v-if="editingId" class="mt-10 rounded-3xl border border-slate-200 bg-slate-50 p-6">
            <div class="mb-4 flex items-center justify-between">
              <p class="text-sm font-semibold text-slate-900">Variants</p>
              <button type="button" @click="openVariantForm" class="rounded-full border px-4 py-2 bg-white text-sm">Add variant</button>
            </div>

            <div v-if="variantLoading" class="text-sm text-slate-500">Loading variants...</div>
            <div v-else-if="!variants.length" class="text-sm text-slate-500">No variants yet. Use Add variant to create one.</div>
            <div v-else class="overflow-x-auto">
              <table class="w-full text-left">
                <thead>
                  <tr class="text-sm text-slate-600 border-b">
                    <th class="py-2">SKU</th>
                    <th class="py-2">Price</th>
                    <th class="py-2">Qty</th>
                    <th class="py-2">Default</th>
                    <th class="py-2">Status</th>
                    <th class="py-2">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <template v-for="variant in variants" :key="variant.id">
                    <tr class="border-b hover:bg-slate-50">
                      <td class="py-3">{{ variant.sku ?? '—' }}</td>
                      <td class="py-3">{{ variant.price ? '₹' + variant.price : '—' }}</td>
                      <td class="py-3">{{ variant.quantity ?? 0 }}</td>
                      <td class="py-3">{{ variant.is_default ? 'Yes' : 'No' }}</td>
                      <td class="py-3">{{ variant.status ? 'Active' : 'Inactive' }}</td>
                      <td class="py-3">
                        <div class="flex flex-wrap gap-2">
                          <button type="button" class="rounded-full border px-3 py-1 text-sm" @click="editVariant(variant)">Edit</button>
                          <button type="button" class="rounded-full border border-rose-200 bg-rose-50 px-3 py-1 text-sm text-rose-700" @click="removeVariant(variant)">Delete</button>
                          <button type="button" class="rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-sm text-sky-700" @click="toggleVariantImages(variant.id)">
                            Images{{ (variantImages[variant.id] ?? []).length ? ` (${(variantImages[variant.id] ?? []).length})` : '' }}
                          </button>
                        </div>
                      </td>
                    </tr>
                    <tr v-if="expandedVariantId === variant.id" class="bg-slate-50 border-b">
                      <td colspan="6" class="px-4 pb-4 pt-2">
                        <div class="space-y-3">
                          <div class="flex items-center gap-3">
                            <input
                              :ref="(el) => { variantImageInputs[variant.id] = el as HTMLInputElement | null }"
                              type="file"
                              accept="image/*"
                              class="text-sm text-slate-700"
                              @change="onVariantImageSelected(variant.id, $event)"
                            />
                            <button
                              type="button"
                              :disabled="isSaving || !variantImageFiles[variant.id]"
                              class="rounded-full bg-slate-900 px-4 py-1.5 text-sm text-white disabled:opacity-50"
                              @click="uploadVariantImage(variant.id)"
                            >Upload</button>
                          </div>
                          <div v-if="!variantImages[variant.id]?.length" class="text-sm text-slate-500">No images yet.</div>
                          <div v-else class="grid gap-3 sm:grid-cols-3 xl:grid-cols-5">
                            <div v-for="img in variantImages[variant.id]" :key="img.id" class="rounded-2xl border border-slate-200 bg-white p-2">
                              <img :src="img.image_url" alt="" class="h-28 w-full rounded-xl object-cover" />
                              <div class="mt-2 flex items-center justify-between gap-1">
                                <span :class="['rounded-full px-2 py-0.5 text-xs font-semibold', img.is_primary ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600']">
                                  {{ img.is_primary ? 'Primary' : 'Gallery' }}
                                </span>
                                <div class="flex gap-1">
                                  <button type="button" :disabled="img.is_primary" class="rounded-full border px-2 py-0.5 text-xs disabled:opacity-40" @click="setPrimaryVariantImage(img)">★</button>
                                  <button type="button" class="rounded-full border border-rose-200 bg-rose-50 px-2 py-0.5 text-xs text-rose-700" @click="deleteVariantImage(img)">✕</button>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                      </td>
                    </tr>
                  </template>
                </tbody>
              </table>
            </div>

            <div v-if="showVariantForm" class="mt-6 rounded-3xl border border-slate-200 bg-white p-6">
              <p class="text-sm font-semibold text-slate-900">{{ editingVariantId ? 'Edit variant' : 'Add variant' }}</p>
              <div class="mt-4 grid gap-4 lg:grid-cols-3">
                <div>
                  <label class="block text-sm font-medium text-slate-700">SKU</label>
                  <input v-model="variantForm.sku" type="text" class="mt-2 w-full rounded-3xl border px-4 py-3" />
                </div>
                <div>
                  <label class="block text-sm font-medium text-slate-700">Price</label>
                  <input v-model.number="variantForm.price" type="number" min="0" class="mt-2 w-full rounded-3xl border px-4 py-3" />
                </div>
                <div>
                  <label class="block text-sm font-medium text-slate-700">Sale price</label>
                  <input v-model.number="variantForm.sale_price" type="number" min="0" class="mt-2 w-full rounded-3xl border px-4 py-3" />
                </div>
                <div>
                  <label class="block text-sm font-medium text-slate-700">Quantity</label>
                  <input v-model.number="variantForm.quantity" type="number" min="0" class="mt-2 w-full rounded-3xl border px-4 py-3" />
                </div>
                <div>
                  <label class="block text-sm font-medium text-slate-700">Weight</label>
                  <input v-model.number="variantForm.weight" type="number" min="0" step="0.01" class="mt-2 w-full rounded-3xl border px-4 py-3" />
                </div>
                <div>
                  <label class="block text-sm font-medium text-slate-700">Default</label>
                  <input type="checkbox" v-model="variantForm.is_default" class="mt-3 h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-500" />
                </div>
              </div>
              <div>
                <label class="block text-sm font-medium text-slate-700">Options (JSON)</label>
                <textarea v-model="variantForm.options" rows="3" class="mt-2 w-full rounded-3xl border px-4 py-3" placeholder='{"color":"red","size":"M"}'></textarea>
              </div>
              <div class="grid gap-4 sm:grid-cols-[1fr_auto] sm:items-end">
                <div>
                  <label class="inline-flex items-center gap-3 text-sm font-medium text-slate-700">
                    <input
                      type="checkbox"
                      v-model="variantForm.status"
                      class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-500"
                    />
                    Active
                  </label>
                </div>
                <div class="flex gap-3">
                  <button type="button" @click="saveVariant" :disabled="isSaving" class="rounded-full bg-slate-900 px-5 py-3 text-white">{{ editingVariantId ? 'Save variant' : 'Create variant' }}</button>
                  <button type="button" @click="resetVariantForm" class="rounded-full border px-5 py-3">Cancel</button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- SKU check backdrop loader -->
  <div v-if="skuChecking" class="fixed inset-0 z-50 flex items-center justify-center bg-black/30">
    <div class="flex items-center gap-3 rounded-2xl bg-white px-6 py-4 shadow-xl">
      <svg class="h-5 w-5 animate-spin text-slate-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
      </svg>
      <span class="text-sm font-medium text-slate-700">Checking SKU…</span>
    </div>
  </div>
</template>
