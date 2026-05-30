<script setup lang="ts">
import { computed } from 'vue'
import LadiesCheckoutPage from '../Ladies/CheckoutPage.vue'
import GroceryCheckoutPage from '../Grocery/CheckoutPage.vue'
import { useStorefront } from '../../services/storefront'

const { storeLayout } = useStorefront()
const activePage = computed(() => (storeLayout.value === 'grocery' ? GroceryCheckoutPage : LadiesCheckoutPage))
</script>

<template>
  <component :is="activePage" />
</template>
