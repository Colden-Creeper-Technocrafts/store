<script setup lang="ts">
</script>

<template>
  <section class="rounded-3xl bg-white p-8 shadow-sm border border-slate-200">
    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
      <div>
        <p class="text-sm uppercase tracking-[0.24em] text-slate-500">Store analytics</p>
        <h1 class="mt-3 text-4xl font-semibold text-slate-900">E-Commerce Dashboard</h1>
        <p class="mt-3 max-w-xl text-slate-500">Track revenue, customer growth, order volume, and conversion performance in one place.</p>
      </div>
      <div class="flex flex-wrap gap-3">
        <button class="rounded-full bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-700">Export report</button>
        <button class="rounded-full border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Last 30 days</button>
      </div>
    </div>

    <div class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <div class="rounded-3xl bg-slate-50 p-6">
        <p class="text-sm font-medium text-slate-500">Total Sales</p>
        <p class="mt-4 text-3xl font-semibold text-slate-900">$142.8K</p>
        <p class="mt-2 text-sm text-emerald-600">+18.4% from last period</p>
      </div>
      <div class="rounded-3xl bg-slate-50 p-6">
        <p class="text-sm font-medium text-slate-500">Orders</p>
        <p class="mt-4 text-3xl font-semibold text-slate-900">1,240</p>
        <p class="mt-2 text-sm text-slate-600">Stable order volume</p>
      </div>
      <div class="rounded-3xl bg-slate-50 p-6">
        <p class="text-sm font-medium text-slate-500">Customers</p>
        <p class="mt-4 text-3xl font-semibold text-slate-900">812</p>
        <p class="mt-2 text-sm text-slate-600">New customers this month</p>
      </div>
      <div class="rounded-3xl bg-slate-50 p-6">
        <p class="text-sm font-medium text-slate-500">Conversion</p>
        <p class="mt-4 text-3xl font-semibold text-slate-900">4.8%</p>
        <p class="mt-2 text-sm text-slate-600">Up from 4.1% last week</p>
      </div>
    </div>
  </section>

  <section class="grid gap-4 xl:grid-cols-[2fr_1fr]">
    <div class="rounded-3xl bg-white p-8 shadow-sm border border-slate-200">
      <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <p class="text-sm uppercase tracking-[0.24em] text-slate-500">Revenue trend</p>
          <h2 class="mt-3 text-2xl font-semibold text-slate-900">Sales this month</h2>
        </div>
        <div class="flex flex-wrap gap-3">
          <button class="rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">Day</button>
          <button class="rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">Week</button>
          <button class="rounded-full bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-700">Month</button>
        </div>
      </div>

      <div class="mt-8 grid gap-3">
        <div class="h-2 w-full rounded-full bg-slate-100">
          <div class="h-full w-[76%] rounded-full bg-emerald-500"></div>
        </div>
        <div class="grid gap-2 sm:grid-cols-3">
          <div class="rounded-3xl bg-slate-50 p-5">
            <p class="text-sm text-slate-500">Projected revenue</p>
            <p class="mt-3 text-2xl font-semibold text-slate-900">$155.4K</p>
          </div>
          <div class="rounded-3xl bg-slate-50 p-5">
            <p class="text-sm text-slate-500">New orders</p>
            <p class="mt-3 text-2xl font-semibold text-slate-900">342</p>
          </div>
          <div class="rounded-3xl bg-slate-50 p-5">
            <p class="text-sm text-slate-500">AOV</p>
            <p class="mt-3 text-2xl font-semibold text-slate-900">$72.35</p>
          </div>
        </div>
      </div>
    </div>

    <div class="rounded-3xl bg-white p-6 shadow-sm border border-slate-200">
      <p class="text-sm font-medium text-slate-500">Sales channel mix</p>
      <div class="mt-6 space-y-4">
        <div class="rounded-3xl bg-slate-50 p-4">
          <p class="text-sm text-slate-600">Online store</p>
          <div class="mt-3 h-3 rounded-full bg-slate-200"><div class="h-full w-[62%] rounded-full bg-slate-900"></div></div>
          <p class="mt-2 text-sm text-slate-500">62%</p>
        </div>
        <div class="rounded-3xl bg-slate-50 p-4">
          <p class="text-sm text-slate-600">Social ads</p>
          <div class="mt-3 h-3 rounded-full bg-slate-200"><div class="h-full w-[24%] rounded-full bg-sky-500"></div></div>
          <p class="mt-2 text-sm text-slate-500">24%</p>
        </div>
        <div class="rounded-3xl bg-slate-50 p-4">
          <p class="text-sm text-slate-600">Referral</p>
          <div class="mt-3 h-3 rounded-full bg-slate-200"><div class="h-full w-[14%] rounded-full bg-emerald-500"></div></div>
          <p class="mt-2 text-sm text-slate-500">14%</p>
        </div>
      </div>
    </div>
  </section>
</template>

