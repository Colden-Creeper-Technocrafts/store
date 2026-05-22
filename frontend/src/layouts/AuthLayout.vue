<script setup lang="ts">
withDefaults(defineProps<{
  title?: string
  subtitle?: string
}>(), {
  title: 'E-Commerce',
  subtitle: 'Welcome Back'
})
</script>

<template>
<div class="min-h-screen bg-gray-100 flex items-center justify-center px-4">
  <div class="w-full max-w-md bg-white shadow-lg rounded-2xl p-8">
    <div class="text-center mb-8">
      <h1 class="text-3xl font-bold text-gray-800">
        {{ title }}
      </h1>
      <p class="text-gray-500 mt-2">
        {{ subtitle }}
      </p>
    </div>
    <slot />
  </div>
</div>
</template>
