<script setup lang="ts">
const collections = [
  {
    id: 1,
    title: 'Clutches',
    subtitle: 'Party, wedding, and everyday styles',
    count: '112 pieces',
    image: 'https://images.unsplash.com/photo-1584917865442-de89df76afd3?auto=format&fit=crop&w=900&q=80'
  },
  {
    id: 2,
    title: 'Scrunchies & Hair Pins',
    subtitle: 'Soft fabrics and premium pin sets',
    count: '178 pieces',
    image: 'https://images.unsplash.com/photo-1522335789203-aabd1fc54bc9?auto=format&fit=crop&w=900&q=80'
  },
  {
    id: 3,
    title: 'Cosmetics',
    subtitle: 'Daily glam essentials and gift sets',
    count: '206 pieces',
    image: 'https://images.unsplash.com/photo-1596462502278-27bfdc403348?auto=format&fit=crop&w=900&q=80'
  },
  {
    id: 4,
    title: 'Children Accessories',
    subtitle: 'Cute, colorful, and lightweight picks',
    count: '94 pieces',
    image: 'https://images.unsplash.com/photo-1519340241574-2cec6aef0c01?auto=format&fit=crop&w=900&q=80'
  },
  {
    id: 5,
    title: 'Bangles',
    subtitle: 'Traditional, modern, and combo sets',
    count: '163 pieces',
    image: 'https://images.unsplash.com/photo-1617038260897-41a1f14a8ca0?auto=format&fit=crop&w=900&q=80'
  },
  {
    id: 6,
    title: 'Gift Articles',
    subtitle: 'Festive gifting boxes and keepsakes',
    count: '87 pieces',
    image: 'https://images.unsplash.com/photo-1512909006721-3d6018887383?auto=format&fit=crop&w=900&q=80'
  }
]

const featuredPieces = [
  {
    id: 1,
    name: 'Velvet Evening Clutch',
    metal: 'Pearl Chain Strap',
    price: '$74',
    tag: 'Bestseller',
    image: 'https://images.unsplash.com/photo-1566150905458-1bf1fc113f0d?auto=format&fit=crop&w=1200&q=80'
  },
  {
    id: 2,
    name: 'Floral Scrunchie Pack',
    metal: 'Satin Set Of 6',
    price: '$18',
    tag: 'New Arrival',
    image: 'https://images.unsplash.com/photo-1617038260897-41a1f14a8ca0?auto=format&fit=crop&w=1200&q=80'
  },
  {
    id: 3,
    name: 'Rose Gold Hair Pin Set',
    metal: 'Set Of 8',
    price: '$26',
    tag: 'Limited',
    image: 'https://images.unsplash.com/photo-1522335789203-aabd1fc54bc9?auto=format&fit=crop&w=1200&q=80'
  },
  {
    id: 4,
    name: 'Kids Sparkle Bangle Duo',
    metal: 'Lightweight Alloy',
    price: '$22',
    tag: 'Gift Edit',
    image: 'https://images.unsplash.com/photo-1514996937319-344454492b37?auto=format&fit=crop&w=1200&q=80'
  }
]

const promises = [
  { id: 1, title: 'Curated Quality', text: 'Handpicked designs with comfort, finish, and durability checks.' },
  { id: 2, title: 'Trendy + Traditional', text: 'From modern clutches to classic bangles, all in one boutique.' },
  { id: 3, title: 'Gift Ready', text: 'Ready-to-gift packaging available for all occasions and festivals.' }
]
</script>

<template>
  <div class="jewelry-theme space-y-12">
    <section class="hero-shell relative -mx-4 overflow-hidden px-6 py-14 sm:-mx-6 sm:px-10 lg:-mx-8 lg:px-14 lg:py-20">
      <div class="hero-orb hero-orb-one"></div>
      <div class="hero-orb hero-orb-two"></div>

      <div class="relative mx-auto grid max-w-6xl gap-10 lg:grid-cols-[1.2fr_0.8fr] lg:items-center">
        <div class="animate-rise">
          <p class="text-xs font-semibold uppercase tracking-[0.22em] text-amber-200">Ladies Accessories Boutique</p>
          <h1 class="mt-4 text-4xl leading-tight text-white sm:text-5xl lg:text-6xl">
            Style Essentials For
            <span class="text-amber-200">Every Occasion</span>
          </h1>
          <p class="mt-6 max-w-xl text-base text-stone-200 sm:text-lg">
            Discover curated clutches, scrunchies, hair pins, cosmetics, children accessories, bangles, and thoughtful gift articles.
          </p>
          <div class="mt-8 flex flex-col gap-3 sm:flex-row">
            <button class="bg-amber-200 px-7 py-3 text-sm font-semibold text-stone-900 transition hover:bg-amber-100">Shop New Collection</button>
            <button class="border border-stone-200/50 px-7 py-3 text-sm font-semibold text-white transition hover:bg-white/10">Book Private Appointment</button>
          </div>
        </div>

        <div class="animate-float border border-amber-100/30 bg-white/10 p-8 backdrop-blur-sm">
          <p class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-100">This Season</p>
          <h2 class="mt-3 text-3xl text-white">Festive Ladies Edit</h2>
          <p class="mt-3 text-sm leading-relaxed text-stone-200">
            Mix-and-match accessories combos for festive looks, gifting, and daily wear.
          </p>
          <div class="mt-7 grid grid-cols-2 gap-3 text-sm">
            <div class="bg-white/15 p-4 text-white">
              <p class="text-xs uppercase tracking-[0.12em] text-stone-200">Starting At</p>
              <p class="mt-2 text-xl">$12</p>
            </div>
            <div class="bg-white/15 p-4 text-white">
              <p class="text-xs uppercase tracking-[0.12em] text-stone-200">Delivery</p>
              <p class="mt-2 text-xl">1-4 Days</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section id="collections" class="px-2 sm:px-4">
      <div class="mb-7 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <p class="text-xs font-semibold uppercase tracking-[0.2em] text-stone-500">Collections</p>
          <h2 class="mt-2 text-3xl text-stone-900 sm:text-4xl">Explore Signature Categories</h2>
        </div>
        <button class="self-start rounded-full border border-stone-300 px-5 py-2 text-sm font-medium text-stone-700 transition hover:border-stone-400 hover:bg-stone-50">View All Pieces</button>
      </div>

      <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <article
          v-for="collection in collections"
          :key="collection.id"
          class="collection-card relative overflow-hidden rounded-[1.6rem] border border-stone-200 p-6"
        >
          <div
            class="absolute inset-0 bg-cover bg-center"
            :style="{ backgroundImage: `url(${collection.image})` }"
          ></div>
          <div class="collection-overlay absolute inset-0"></div>
          <div class="relative z-10">
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-amber-200">{{ collection.count }}</p>
            <h3 class="mt-3 text-2xl text-white">{{ collection.title }}</h3>
            <p class="mt-3 text-sm text-stone-100">{{ collection.subtitle }}</p>
            <button class="mt-6 text-sm font-semibold text-white underline decoration-amber-300 underline-offset-4">Explore Collection</button>
          </div>
        </article>
      </div>
    </section>

    <section id="featured" class="grid gap-5 px-2 sm:px-4 lg:grid-cols-[1.2fr_0.8fr]">
      <div class="rounded-[1.8rem] border border-stone-200 bg-white p-6 sm:p-8">
        <div class="mb-6">
          <p class="text-xs font-semibold uppercase tracking-[0.2em] text-stone-500">Bestsellers</p>
          <h2 class="mt-2 text-3xl text-stone-900">Most Loved Picks</h2>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
          <article
            v-for="piece in featuredPieces"
            :key="piece.id"
            class="rounded-3xl border border-stone-200 bg-stone-50/80 p-5 transition hover:-translate-y-1 hover:border-amber-300 hover:bg-white"
          >
            <div
              class="mb-4 h-36 rounded-2xl bg-cover bg-center"
              :style="{ backgroundImage: `linear-gradient(rgba(21, 17, 16, 0.18), rgba(21, 17, 16, 0.18)), url(${piece.image})` }"
            ></div>
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-rose-500">{{ piece.tag }}</p>
            <h3 class="mt-2 text-xl text-stone-900">{{ piece.name }}</h3>
            <p class="mt-1 text-sm text-stone-600">{{ piece.metal }}</p>
            <div class="mt-5 flex items-center justify-between">
              <p class="text-2xl text-stone-900">{{ piece.price }}</p>
              <button class="rounded-full border border-stone-300 px-4 py-2 text-xs font-semibold uppercase tracking-[0.1em] text-stone-700 transition hover:border-stone-500">
                Add
              </button>
            </div>
          </article>
        </div>
      </div>

      <div class="rounded-[1.8rem] border border-stone-200 bg-gradient-to-b from-rose-50 to-amber-50 p-6 sm:p-8">
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-rose-600">Why Choose Us</p>
        <h3 class="mt-3 text-3xl leading-tight text-stone-900">Your One-Stop Accessories Destination</h3>

        <div class="mt-7 space-y-4">
          <article
            v-for="promise in promises"
            :key="promise.id"
            class="rounded-2xl border border-rose-100 bg-white/80 p-4"
          >
            <h4 class="text-sm font-semibold uppercase tracking-[0.14em] text-stone-800">{{ promise.title }}</h4>
            <p class="mt-2 text-sm text-stone-600">{{ promise.text }}</p>
          </article>
        </div>
      </div>
    </section>

    <section id="concierge" class="relative overflow-hidden rounded-[1.8rem] border border-stone-200 bg-white px-6 py-10 sm:px-10">
      <div class="absolute -right-12 -top-12 h-32 w-32 rounded-full bg-amber-100/60 blur-2xl"></div>
      <div class="absolute -left-10 -bottom-10 h-32 w-32 rounded-full bg-rose-100/70 blur-2xl"></div>

      <div class="relative grid gap-7 lg:grid-cols-[1.1fr_0.9fr] lg:items-center">
        <div>
          <p class="text-xs font-semibold uppercase tracking-[0.2em] text-stone-500">Styling Assistance</p>
          <h2 class="mt-3 text-3xl text-stone-900 sm:text-4xl">Need Help Building A Complete Look?</h2>
          <p class="mt-4 max-w-xl text-stone-600">
            Get personalized recommendations for ladies accessories, kids accessories, and gift combinations for every budget.
          </p>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row lg:justify-end">
          <button class="rounded-full bg-stone-900 px-8 py-3 text-sm font-semibold text-white transition hover:bg-stone-700">Schedule Consultation</button>
          <button class="rounded-full border border-stone-300 px-8 py-3 text-sm font-semibold text-stone-700 transition hover:border-stone-400 hover:bg-stone-50">Contact Concierge</button>
        </div>
      </div>
    </section>
  </div>
</template>

<style scoped>
.jewelry-theme {
  font-family: "Garamond", "Times New Roman", serif;
}

.hero-shell {
  background: radial-gradient(circle at 15% 20%, #be9f56 0%, #2d2423 43%, #151110 100%);
}

.hero-orb {
  pointer-events: none;
  position: absolute;
  border-radius: 9999px;
  filter: blur(24px);
}

.hero-orb-one {
  right: -40px;
  top: -20px;
  height: 180px;
  width: 180px;
  background: rgba(252, 211, 138, 0.35);
}

.hero-orb-two {
  bottom: -45px;
  left: -30px;
  height: 160px;
  width: 160px;
  background: rgba(244, 114, 182, 0.2);
}

.collection-card {
  transition: transform 220ms ease, box-shadow 220ms ease;
}

.collection-overlay {
  background: linear-gradient(165deg, rgba(12, 10, 9, 0.18), rgba(12, 10, 9, 0.76));
}

.collection-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 18px 38px rgba(41, 37, 36, 0.1);
}

.animate-rise {
  animation: rise 520ms ease-out;
}

.animate-float {
  animation: drift 4.2s ease-in-out infinite;
}

@keyframes rise {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes drift {
  0%, 100% {
    transform: translateY(0);
  }
  50% {
    transform: translateY(-6px);
  }
}
</style>
