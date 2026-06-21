<script setup lang="ts">
import { computed } from 'vue'
import LadiesAddressesPage from '../Ladies/AddressesPage.vue'
import { useStorefront } from '../../services/storefront'

const { storeLayout } = useStorefront()
// Extend here if a Grocery-specific layout is needed in future
const activePage = computed(() => LadiesAddressesPage)
</script>

<template>
  <component :is="activePage" />
</template>
