<template>
  <div class="min-h-screen bg-slate-50 text-slate-900">
    <header class="border-b border-slate-200 bg-white">
      <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6">
        <router-link to="/" class="text-xl font-semibold text-slate-900">Front Store</router-link>
      </div>
    </header>

    <main class="px-4 py-10 sm:px-6 lg:px-8">
      <div class="mx-auto max-w-7xl">
        <slot />
      </div>
    </main>
  </div>
</template>
