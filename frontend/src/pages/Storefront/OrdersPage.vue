<script setup lang="ts">
import { computed } from 'vue'
import LadiesOrdersPage from '../Ladies/OrdersPage.vue'
import GroceryOrdersPage from '../Grocery/OrdersPage.vue'
import { useStorefront } from '../../services/storefront'

const { storeLayout } = useStorefront()
const activePage = computed(() => (storeLayout.value === 'grocery' ? GroceryOrdersPage : LadiesOrdersPage))
</script>

<template>
  <component :is="activePage" />
</template>
