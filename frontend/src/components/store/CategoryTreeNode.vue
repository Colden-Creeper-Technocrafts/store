<script setup lang="ts">
defineOptions({ name: 'CategoryTreeNode' })
import { ref } from 'vue'

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

const emit = defineEmits<{
  (event: 'selection-change'): void
}>()

const selected = ref(false)
const childNodes = ref<any[]>([])
const root = ref<HTMLElement | null>(null)

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
  emit('selection-change')
  // DOM fallback: ensure any native checkbox inputs under this node reflect the state
  if (root.value) {
    const inputs = Array.from(root.value.querySelectorAll('input[type="checkbox"]')) as HTMLInputElement[]
    inputs.forEach((el) => {
      if (el.checked !== value) {
        el.checked = value
        el.dispatchEvent(new Event('change', { bubbles: true }))
      }
    })
  }
}

const getSelectedIds = (): number[] => {
  const ids: number[] = []

  if (selected.value) {
    ids.push(props.node.id)
  }

  childNodes.value.forEach((childNode) => {
    if (childNode?.getSelectedIds) {
      ids.push(...childNode.getSelectedIds())
    }
  })

  return ids
}

const selectIfMatches = (ids: number[]) => {
  if (ids.includes(props.node.id)) {
    selected.value = true
    emit('selection-change')
  }
  childNodes.value.forEach((childNode) => {
    if (childNode?.selectIfMatches) childNode.selectIfMatches(ids)
  })
}

defineExpose({ setSelected, getSelectedIds, selectIfMatches })

const onCheckboxChange = (): void => {
  setSelected(selected.value)
}
</script>

<template>
  <li ref="root" class="space-y-2">
    <label class="inline-flex items-center gap-2 font-medium">
      <input
        type="checkbox"
        v-model="selected"
        @change="onCheckboxChange"
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
        @selection-change="emit('selection-change')"
        ref="childNodes"
      />
    </ul>
  </li>
</template>
