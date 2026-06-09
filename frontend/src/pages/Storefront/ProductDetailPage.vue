<script setup lang="ts">
import { computed } from 'vue'
import LadiesProductDetailPage from '../Ladies/ProductDetailPage.vue'
import GroceryProductDetailPage from '../Grocery/ProductDetailPage.vue'
import { useStorefront } from '../../services/storefront'

const { storeLayout } = useStorefront()
const activePage = computed(() => storeLayout.value === 'grocery' ? GroceryProductDetailPage : LadiesProductDetailPage)
</script>

<template>
  <component :is="activePage" />
</template>
