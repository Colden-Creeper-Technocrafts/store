<script setup lang="ts">
import { computed } from 'vue'
import LadiesHomePage from '../Ladies/HomePage.vue'
import GroceryHomePage from '../Grocery/HomePage.vue'
import { useStorefront } from '../../services/storefront'

const { storeLayout } = useStorefront()
const activePage = computed(() => (storeLayout.value === 'grocery' ? GroceryHomePage : LadiesHomePage))
</script>

<template>
  <component :is="activePage" />
</template>
