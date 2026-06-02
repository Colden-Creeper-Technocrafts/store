<script setup lang="ts">
import { ref, computed } from 'vue'

interface SelectOption {
  id: number | string
  name: string
}

const props = defineProps<{
  modelValue: number | null
  options: SelectOption[]
  placeholder?: string
}>()

const emit = defineEmits<{
  'update:modelValue': [value: number | null]
}>()

const isOpen = ref(false)
const searchQuery = ref('')

const filteredOptions = computed(() => {
  if (!searchQuery.value) return props.options
  const query = searchQuery.value.toLowerCase()
  return props.options.filter(opt => opt.name.toLowerCase().includes(query))
})

const selectedOption = computed(() => {
  return props.options.find(opt => opt.id === props.modelValue)
})

const selectOption = (optionId: number | string | null) => {
  emit('update:modelValue', optionId as number | null)
  isOpen.value = false
  searchQuery.value = ''
}

const openDropdown = () => {
  isOpen.value = true
}

const closeDropdown = () => {
  isOpen.value = false
  searchQuery.value = ''
}
</script>

<template>
  <div class="relative w-full">
    <div
      class="rounded-3xl border bg-white px-4 py-2 cursor-pointer flex items-center justify-between"
      @click="openDropdown"
    >
      <span :class="selectedOption ? 'text-slate-900' : 'text-slate-500'">
        {{ selectedOption?.name || placeholder || 'Select...' }}
      </span>
      <span class="text-slate-400">▼</span>
    </div>

    <div
      v-if="isOpen"
      class="absolute top-full left-0 right-0 mt-1 bg-white border rounded-3xl shadow-lg z-50"
      @mouseleave="closeDropdown"
    >
      <input
        v-model="searchQuery"
        type="text"
        placeholder="Search categories..."
        class="w-full rounded-t-3xl border-b px-4 py-2 outline-none focus:border-slate-400"
        @keydown.escape="closeDropdown"
      />

      <div class="max-h-48 overflow-y-auto">
        <div
          class="px-4 py-2 cursor-pointer hover:bg-slate-100"
          @click="selectOption(null)"
        >
          All categories
        </div>

        <div
          v-for="option in filteredOptions"
          :key="option.id"
          :class="[
            'px-4 py-2 cursor-pointer hover:bg-slate-100',
            option.id === modelValue ? 'bg-slate-900 text-white hover:bg-slate-800' : ''
          ]"
          @click="selectOption(option.id)"
        >
          {{ option.name }}
        </div>

        <div v-if="filteredOptions.length === 0" class="px-4 py-2 text-sm text-slate-500">
          No categories found
        </div>
      </div>
    </div>

    <div v-if="isOpen" class="fixed inset-0 z-40" @click="closeDropdown"></div>
  </div>
</template>
