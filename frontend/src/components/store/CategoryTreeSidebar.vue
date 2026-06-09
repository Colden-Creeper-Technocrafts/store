<script setup lang="ts">
import { ref, nextTick } from 'vue'
import CategoryTreeNode from './CategoryTreeNode.vue'

type CategoryNode = {
  id: number
  name: string
  slug: string
  description?: string | null
  children: CategoryNode[]
}

const props = defineProps<{
  title: string
  categories: CategoryNode[]
  loading?: boolean
  tone: 'ladies' | 'grocery'
}>()

const emit = defineEmits<{
  (event: 'selected-updated', categoryIds: number[]): void
}>()

const categoryNodeRefs = ref<any[]>([])
const collapsed = ref(true)   // start collapsed on mobile; desktop always visible via CSS

const getSelectedIds = (): number[] => {
  return categoryNodeRefs.value.flatMap((node) => {
    if (node && typeof node.getSelectedIds === 'function') {
      return node.getSelectedIds()
    }
    return []
  })
}

const propagateSelectionChange = async (): Promise<void> => {
  await nextTick()
  emit('selected-updated', getSelectedIds())
}

const containerClass =
  props.tone === 'grocery'
    ? 'border-emerald-200 bg-white text-emerald-950'
    : 'border-stone-200 bg-white text-stone-900'

const titleClass = props.tone === 'grocery' ? 'text-emerald-700' : 'text-stone-600'
const emptyClass = props.tone === 'grocery' ? 'text-emerald-700' : 'text-stone-600'
const toggleAccent = props.tone === 'grocery' ? 'text-emerald-600' : 'text-amber-700'
</script>

<template>
  <aside :class="['border p-6', containerClass]">
    <!-- Header: always shown. On mobile, acts as a toggle. On lg+, just a label. -->
    <button
      type="button"
      class="flex w-full items-center justify-between lg:cursor-default lg:pointer-events-none"
      @click="collapsed = !collapsed"
      :aria-expanded="!collapsed"
    >
      <p :class="['text-xs font-semibold uppercase tracking-[0.16em]', titleClass]">{{ title }}</p>
      <span :class="['text-xs font-semibold lg:hidden', toggleAccent]">
        {{ collapsed ? '+ Filter' : '− Hide' }}
      </span>
    </button>

    <!-- Body: hidden on mobile when collapsed; always visible on lg -->
    <div :class="['mt-4', collapsed ? 'hidden lg:block' : 'block']">
      <div v-if="loading" class="text-sm" :class="emptyClass">Loading categories...</div>
      <div v-else-if="!categories.length" class="text-sm" :class="emptyClass">No categories available.</div>
      <ul v-else class="space-y-3">
        <CategoryTreeNode
          v-for="category in categories"
          :key="category.id"
          :node="category"
          :tone="tone"
          @selection-change="propagateSelectionChange"
          ref="categoryNodeRefs"
        />
      </ul>
    </div>
  </aside>
</template>
