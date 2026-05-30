<script setup lang="ts">
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

const containerClass =
  props.tone === 'grocery'
    ? 'border-emerald-200 bg-white text-emerald-950'
    : 'border-stone-200 bg-white text-stone-900'

const titleClass = props.tone === 'grocery' ? 'text-emerald-700' : 'text-stone-600'
const emptyClass = props.tone === 'grocery' ? 'text-emerald-700' : 'text-stone-600'
</script>

<template>
  <aside :class="['border p-6', containerClass]">
    <p :class="['text-xs font-semibold uppercase tracking-[0.16em]', titleClass]">{{ title }}</p>

    <div v-if="loading" class="mt-4 text-sm" :class="emptyClass">Loading categories...</div>
    <div v-else-if="!categories.length" class="mt-4 text-sm" :class="emptyClass">No categories available.</div>

    <ul v-else class="mt-4 space-y-3">
      <CategoryTreeNode
        v-for="category in categories"
        :key="category.id"
        :node="category"
        :tone="tone"
      />
    </ul>
  </aside>
</template>
