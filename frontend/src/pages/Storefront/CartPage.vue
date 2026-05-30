<script setup lang="ts">
import { computed } from 'vue'
import LadiesCartPage from '../Ladies/CartPage.vue'
import GroceryCartPage from '../Grocery/CartPage.vue'
import { useStorefront } from '../../services/storefront'

const { storeLayout } = useStorefront()
const activePage = computed(() => (storeLayout.value === 'grocery' ? GroceryCartPage : LadiesCartPage))
</script>

<template>
  <component :is="activePage" />
</template>
