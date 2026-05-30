<script setup lang="ts">
defineOptions({ name: 'CategoryTreeNode' })
import { ref, watch } from 'vue'

type CategoryNode = {
  id: number
  name: string
  slug: string
  description?: string | null
  children: CategoryNode[]
}

const props = defineProps<{
  node: CategoryNode
  tone: 'ladies' | 'grocery'
}>()

const selected = ref(false)
const childNodes = ref<any[]>([])

const textClass = props.tone === 'grocery' ? 'text-emerald-900' : 'text-stone-900'
const childClass = props.tone === 'grocery' ? 'border-emerald-200 text-emerald-800' : 'border-stone-200 text-stone-700'
const checkboxClass = props.tone === 'grocery'
  ? 'text-emerald-600 border-emerald-300 focus:ring-emerald-500'
  : 'text-stone-600 border-stone-300 focus:ring-stone-500'

const setSelected = (value: boolean) => {
  selected.value = value
  childNodes.value.forEach((childNode) => {
    if (childNode?.setSelected) {
      childNode.setSelected(value)
    }
  })
}

defineExpose({ setSelected })

watch(selected, (value) => {
  if (childNodes.value.length) {
    childNodes.value.forEach((childNode) => {
      if (childNode?.setSelected) {
        childNode.setSelected(value)
      }
    })
  }
})
</script>

<template>
  <li class="space-y-2">
    <label class="inline-flex items-center gap-2 font-medium">
      <input
        type="checkbox"
        v-model="selected"
        :class="['h-4 w-4 rounded border', checkboxClass]"
        :aria-label="`Select ${node.name}`"
      />
      <span :class="textClass">{{ node.name }}</span>
    </label>

    <ul v-if="node.children.length" class="space-y-2 border-l pl-4" :class="childClass">
      <CategoryTreeNode
        v-for="child in node.children"
        :key="child.id"
        :node="child"
        :tone="tone"
        ref="childNodes"
      />
    </ul>
  </li>
</template>
