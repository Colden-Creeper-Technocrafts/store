<script lang="ts">
import { defineComponent, type PropType } from 'vue'
import type { AdminCategory } from '../services/adminCategories'

export default defineComponent({
  name: 'CategoryTreeItem',
  props: {
    category: {
      type: Object as PropType<AdminCategory>,
      required: true,
    },
    level: {
      type: Number,
      required: true,
    },
    forceOpen: {
      type: Boolean,
      default: false,
    },
  },
  emits: ['edit-category', 'delete-category'],
  data() {
    return {
      isOpen: false,
    }
  },
  watch: {
    forceOpen(value: boolean) {
      if (value) {
        this.isOpen = true
      }
    },
  },
  methods: {
    editCategory() {
      this.$emit('edit-category', this.category)
    },
    deleteCategory() {
      this.$emit('delete-category', this.category)
    },
    toggleOpen() {
      this.isOpen = !this.isOpen
    },
  },
})
</script>

<template>
  <div>
    <div class="rounded-3xl border border-slate-200 bg-white p-4">
      <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <p class="text-base font-semibold text-slate-900">
            <span v-if="level" class="text-slate-400">{{ '—'.repeat(level) }} </span>{{ category.name }}
          </p>
          <p class="text-sm text-slate-500">{{ category.children?.length ?? 0 }} subcategories</p>
        </div>
        <div class="flex flex-wrap gap-2 items-center">
          <button
            v-if="category.children?.length"
            type="button"
            @click="toggleOpen"
            class="rounded-full border border-slate-200 bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700 transition hover:bg-slate-200"
          >
            {{ isOpen ? 'Hide' : 'Show' }} {{ category.children?.length }}
          </button>
          <button
            type="button"
            @click="editCategory"
            class="rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
          >
            Edit
          </button>
          <button
            type="button"
            @click="deleteCategory"
            class="rounded-full border border-rose-200 bg-rose-50 px-4 py-2 text-sm font-semibold text-rose-700 transition hover:bg-rose-100"
          >
            Delete
          </button>
        </div>
      </div>

      <div class="mt-4 rounded-3xl bg-slate-50 p-4">
        <p class="text-sm text-slate-500">Slug: {{ category.slug }}</p>
        <p class="mt-2 text-sm text-slate-500">
          Parent: {{ category.parent_category_id ? category.parent_category_id : 'None' }}
        </p>
      </div>
    </div>

    <div v-if="isOpen && category.children?.length" class="mt-4 space-y-4 border-l border-slate-200 pl-4">
      <CategoryTreeItem
        v-for="child in category.children"
        :key="child.id"
        :category="child"
        :level="level + 1"
        @edit-category="$emit('edit-category', $event)"
        @delete-category="$emit('delete-category', $event)"
      />
    </div>
  </div>
</template>
