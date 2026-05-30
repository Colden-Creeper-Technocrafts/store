<script setup lang="ts">
import { computed } from 'vue'
import LadiesStorePage from '../Ladies/StorePage.vue'
import GroceryStorePage from '../Grocery/StorePage.vue'
import { useStorefront } from '../../services/storefront'

const { storeLayout } = useStorefront()
const activePage = computed(() => (storeLayout.value === 'grocery' ? GroceryStorePage : LadiesStorePage))
</script>

<template>
  <component :is="activePage" />
</template>
