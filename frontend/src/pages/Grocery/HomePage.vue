<script setup lang="ts">
const categories = [
  {
    id: 1,
    name: 'Fresh Vegetables',
    itemCount: '84 items',
    note: 'Farm direct daily harvest',
    image: 'https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=900&q=80'
  },
  {
    id: 2,
    name: 'Fruits',
    itemCount: '63 items',
    note: 'Seasonal premium quality',
    image: 'https://images.unsplash.com/photo-1619566636858-adf3ef46400b?auto=format&fit=crop&w=900&q=80'
  },
  {
    id: 3,
    name: 'Dairy & Bread',
    itemCount: '42 items',
    note: 'Milk, curd, cheese, butter',
    image: 'https://images.unsplash.com/photo-1550583724-b2692b85b150?auto=format&fit=crop&w=900&q=80'
  },
  {
    id: 4,
    name: 'Bakery',
    itemCount: '29 items',
    note: 'Bread, buns, pastries',
    image: 'https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&fit=crop&w=900&q=80'
  },
  {
    id: 5,
    name: 'Snacks',
    itemCount: '117 items',
    note: 'Healthy and family packs',
    image: 'https://images.unsplash.com/photo-1585238342024-78d387f4a707?auto=format&fit=crop&w=900&q=80'
  },
  {
    id: 6,
    name: 'Home Essentials',
    itemCount: '91 items',
    note: 'Cleaning and care products',
    image: 'https://images.unsplash.com/photo-1583947215259-38e31be8751f?auto=format&fit=crop&w=900&q=80'
  }
]

const freshPicks = [
  {
    id: 1,
    name: 'Organic Spinach Bundle',
    unit: '250g',
    price: '$2.49',
    tag: 'Fresh Today',
    image: 'https://images.unsplash.com/photo-1576045057995-568f588f82fb?auto=format&fit=crop&w=900&q=80'
  },
  {
    id: 2,
    name: 'Alphonso Mangoes',
    unit: '1kg',
    price: '$8.90',
    tag: 'Season Special',
    image: 'https://images.unsplash.com/photo-1553279768-865429fa0078?auto=format&fit=crop&w=900&q=80'
  },
  {
    id: 3,
    name: 'Vanilla Yogurt Cup',
    unit: '500g',
    price: '$4.20',
    tag: 'Best Value',
    image: 'https://images.unsplash.com/photo-1490645935967-10de6ba17061?auto=format&fit=crop&w=900&q=80'
  },
  {
    id: 4,
    name: 'Whole Wheat Bread',
    unit: '400g',
    price: '$2.10',
    tag: 'Daily Essential',
    image: 'https://images.unsplash.com/photo-1608198093002-ad4e005484ec?auto=format&fit=crop&w=900&q=80'
  },
  {
    id: 5,
    name: 'Greek Yogurt',
    unit: '500g',
    price: '$5.40',
    tag: 'Protein Pick',
    image: 'https://images.unsplash.com/photo-1488477181946-6428a0291777?auto=format&fit=crop&w=900&q=80'
  },
  {
    id: 6,
    name: 'Mixed Dry Fruits',
    unit: '300g',
    price: '$9.80',
    tag: 'Top Seller',
    image: 'https://images.unsplash.com/photo-1599719590328-2eb3f11b6e36?auto=format&fit=crop&w=900&q=80'
  }
]

const servicePoints = [
  { id: 1, title: '2-Hour Delivery', description: 'Express delivery slots in selected areas.' },
  { id: 2, title: 'Daily Price Drops', description: 'Fresh deals updated every morning.' },
  { id: 3, title: 'No-Risk Returns', description: 'Quality issue? Easy replacement at doorstep.' }
]
</script>

<template>
  <div class="space-y-8 text-emerald-950">
    <section class="hero-grid grid gap-6 border border-emerald-300 bg-emerald-900 px-6 py-10 text-white lg:grid-cols-[1.2fr_0.8fr] lg:px-10">
      <div class="space-y-5">
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-200">Fresh Grocery Delivered</p>
        <h1 class="text-4xl leading-tight sm:text-5xl">Healthy Groceries For Everyday Living</h1>
        <p class="max-w-2xl text-emerald-50/90">
          Shop vegetables, fruits, dairy, snacks, and home essentials in one place. Fast delivery, trusted quality, and fair pricing every day.
        </p>
        <div class="flex flex-col gap-3 sm:flex-row">
          <button class="border border-emerald-100 bg-emerald-100 px-6 py-3 text-sm font-semibold text-emerald-900 transition hover:bg-white">
            Start Shopping
          </button>
          <button class="border border-emerald-100/80 px-6 py-3 text-sm font-semibold text-white transition hover:bg-emerald-800">
            View Today's Offers
          </button>
        </div>
      </div>

      <div class="grid grid-cols-2 gap-3 self-start">
        <div
          class="col-span-2 min-h-[150px] border border-emerald-200/50 bg-cover bg-center"
          style="background-image: linear-gradient(rgba(6, 78, 59, 0.35), rgba(6, 78, 59, 0.35)), url('https://images.unsplash.com/photo-1543168256-418811576931?auto=format&fit=crop&w=1200&q=80');"
        ></div>
        <div class="border border-emerald-200/50 bg-emerald-800/80 p-4">
          <p class="text-xs uppercase tracking-[0.14em] text-emerald-200">Delivery</p>
          <p class="mt-2 text-2xl">2 hrs</p>
        </div>
        <div class="border border-emerald-200/50 bg-emerald-800/80 p-4">
          <p class="text-xs uppercase tracking-[0.14em] text-emerald-200">Categories</p>
          <p class="mt-2 text-2xl">60+</p>
        </div>
        <div class="border border-emerald-200/50 bg-emerald-800/80 p-4">
          <p class="text-xs uppercase tracking-[0.14em] text-emerald-200">Products</p>
          <p class="mt-2 text-2xl">2,300+</p>
        </div>
        <div class="border border-emerald-200/50 bg-emerald-800/80 p-4">
          <p class="text-xs uppercase tracking-[0.14em] text-emerald-200">Avg Savings</p>
          <p class="mt-2 text-2xl">18%</p>
        </div>
      </div>
    </section>

    <section class="border border-emerald-200 bg-white p-6 sm:p-8">
      <div class="mb-6 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Shop By Category</p>
          <h2 class="mt-2 text-3xl text-emerald-950">Everything You Need, Daily</h2>
        </div>
        <button class="self-start border border-emerald-300 bg-white px-5 py-2 text-sm font-semibold text-emerald-900 transition hover:border-emerald-500 hover:bg-emerald-50">
          Browse All
        </button>
      </div>

      <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
        <article
          v-for="category in categories"
          :key="category.id"
          class="relative overflow-hidden border border-emerald-200 p-5 transition hover:border-emerald-400"
        >
          <div class="absolute inset-0 bg-cover bg-center" :style="{ backgroundImage: `url(${category.image})` }"></div>
          <div class="category-overlay absolute inset-0"></div>
          <div class="relative z-10">
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-emerald-100">{{ category.itemCount }}</p>
            <h3 class="mt-2 text-xl text-white">{{ category.name }}</h3>
            <p class="mt-2 text-sm text-emerald-50">{{ category.note }}</p>
          </div>
        </article>
      </div>
    </section>

    <section class="grid gap-4 lg:grid-cols-[1.1fr_0.9fr]">
      <div class="border border-emerald-200 bg-white p-6 sm:p-8">
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Fresh Picks</p>
        <h2 class="mt-2 text-3xl text-emerald-950">Trending This Week</h2>

        <div class="mt-5 grid gap-3 sm:grid-cols-2">
          <article
            v-for="product in freshPicks"
            :key="product.id"
            class="border border-emerald-200 bg-emerald-50/50 p-4 transition hover:border-emerald-400 hover:bg-emerald-50"
          >
            <div
              class="mb-3 h-28 bg-cover bg-center"
              :style="{ backgroundImage: `linear-gradient(rgba(4, 120, 87, 0.15), rgba(4, 120, 87, 0.15)), url(${product.image})` }"
            ></div>
            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-emerald-700">{{ product.tag }}</p>
            <h3 class="mt-2 text-lg text-emerald-950">{{ product.name }}</h3>
            <p class="mt-1 text-sm text-emerald-800">{{ product.unit }}</p>
            <div class="mt-4 flex items-center justify-between">
              <p class="text-2xl text-emerald-950">{{ product.price }}</p>
              <button class="border border-emerald-300 bg-white px-3 py-2 text-xs font-semibold uppercase tracking-[0.1em] text-emerald-900 transition hover:border-emerald-500 hover:bg-emerald-100">
                Add
              </button>
            </div>
          </article>
        </div>
      </div>

      <div class="border border-emerald-200 bg-emerald-50 p-6 sm:p-8">
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Saver Zone</p>
        <h2 class="mt-2 text-3xl text-emerald-950">Today's Grocery Deals</h2>
        <div class="mt-6 space-y-3">
          <div class="border border-emerald-200 bg-white p-4">
            <p class="text-sm font-semibold text-emerald-900">Buy 1 Get 1</p>
            <p class="mt-1 text-sm text-emerald-800">Selected juices and breakfast cereals</p>
          </div>
          <div class="border border-emerald-200 bg-white p-4">
            <p class="text-sm font-semibold text-emerald-900">Flat 20% Off</p>
            <p class="mt-1 text-sm text-emerald-800">Fresh vegetables combo packs</p>
          </div>
          <div class="border border-emerald-200 bg-white p-4">
            <p class="text-sm font-semibold text-emerald-900">Weekend Mega Cart</p>
            <p class="mt-1 text-sm text-emerald-800">Save extra $10 on orders over $85</p>
          </div>
        </div>
      </div>
    </section>

    <section class="grid gap-3 sm:grid-cols-3">
      <article
        v-for="point in servicePoints"
        :key="point.id"
        class="border border-emerald-200 bg-white p-6"
      >
        <h3 class="text-lg text-emerald-950">{{ point.title }}</h3>
        <p class="mt-2 text-sm text-emerald-800">{{ point.description }}</p>
      </article>
    </section>
  </div>
</template>

<style scoped>
.hero-grid {
  background:
    linear-gradient(120deg, rgba(6, 95, 70, 0.8), rgba(4, 120, 87, 0.88)),
    radial-gradient(circle at 10% 15%, rgba(167, 243, 208, 0.35), transparent 36%);
}

.category-overlay {
  background: linear-gradient(160deg, rgba(6, 78, 59, 0.35), rgba(6, 78, 59, 0.82));
}
</style>
