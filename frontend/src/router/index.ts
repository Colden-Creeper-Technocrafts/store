import { createRouter, createWebHistory } from 'vue-router'

import StorefrontHomePage from '../pages/Storefront/HomePage.vue'
import StorefrontStorePage from '../pages/Storefront/StorePage.vue'
import StorefrontProductDetailPage from '../pages/Storefront/ProductDetailPage.vue'
import StorefrontCheckoutPage from '../pages/Storefront/CheckoutPage.vue'
import StorefrontCartPage from '../pages/Storefront/CartPage.vue'
import StorefrontOrdersPage from '../pages/Storefront/OrdersPage.vue'
import AdminDashboardPage from '../admin/DashboardPage.vue'
import AdminLoginPage from '../admin/LoginPage.vue'
import AdminSettingsPage from '../admin/SettingsPage.vue'
import AdminCategoriesPage from '../admin/CategoriesPage.vue'
import AdminProductsPage from '../admin/ProductsPage.vue'
import AdminCouponsPage from '../admin/CouponsPage.vue'
import AdminOrdersPage from '../admin/OrdersPage.vue'
import AdminShippingPage from '../admin/ShippingPage.vue'
import AdminCustomersPage from '../admin/CustomersPage.vue'
import AdminUsersPage from '../admin/UsersPage.vue'
import AdminReturnsPage from '../admin/ReturnsPage.vue'
import AdminFulfillmentPage from '../admin/FulfillmentPage.vue'
import AdminNotificationsPage from '../admin/NotificationsPage.vue'
import AdminCartsPage from '../admin/CartsPage.vue'
import AdminLogsPage from '../admin/LogsPage.vue'
import CustomerAuthPage from '../customer/AuthPage.vue'
import CustomerProfilePage from '../customer/ProfilePage.vue'
import StorefrontAddressesPage from '../pages/Storefront/AddressesPage.vue'

const routes = [
  {
    path: '/',
    component: StorefrontHomePage,
    meta: { layout: 'store' }
  },
  {
    path: '/store',
    component: StorefrontStorePage,
    meta: { layout: 'store' }
  },
  {
    path: '/product/:slug',
    component: StorefrontProductDetailPage,
    meta: { layout: 'store' }
  },
  {
    path: '/checkout',
    component: StorefrontCheckoutPage,
    meta: { layout: 'store' }
  },
  {
    path: '/cart',
    component: StorefrontCartPage,
    meta: { layout: 'store' }
  },
  {
    path: '/orders',
    component: StorefrontOrdersPage,
    meta: { layout: 'store' }
  },
  {
    path: '/login',
    component: CustomerAuthPage,
    meta: { layout: 'store' }
  },
  {
    path: '/register',
    component: CustomerAuthPage,
    meta: { layout: 'store' }
  },
  {
    path: '/profile',
    component: CustomerProfilePage,
    meta: { layout: 'store' }
  },
  {
    path: '/addresses',
    component: StorefrontAddressesPage,
    meta: { layout: 'store' }
  },
  {
    path: '/backstore',
    redirect: '/backstore/dashboard'
  },
  {
    path: '/backstore/dashboard',
    component: AdminDashboardPage,
    meta: { requiresAdmin: true }
  },
  {
    path: '/backstore/login',
    component: AdminLoginPage
  },
  {
    path: '/backstore/categories',
    component: AdminCategoriesPage,
    meta: { requiresAdmin: true }
  },
  {
    path: '/backstore/products',
    component: AdminProductsPage,
    meta: { requiresAdmin: true }
  },
  {
    path: '/backstore/settings',
    component: AdminSettingsPage,
    meta: { requiresAdmin: true }
  },
  {
    path: '/backstore/coupons',
    component: AdminCouponsPage,
    meta: { requiresAdmin: true }
  },
  {
    path: '/backstore/orders',
    component: AdminOrdersPage,
    meta: { requiresAdmin: true }
  },
  {
    path: '/backstore/shipping',
    component: AdminShippingPage,
    meta: { requiresAdmin: true }
  },
  {
    path: '/backstore/customers',
    component: AdminCustomersPage,
    meta: { requiresAdmin: true }
  },
  {
    path: '/backstore/users',
    component: AdminUsersPage,
    meta: { requiresAdmin: true }
  },
  {
    path: '/backstore/returns',
    component: AdminReturnsPage,
    meta: { requiresAdmin: true }
  },
  {
    path: '/backstore/fulfillment',
    component: AdminFulfillmentPage,
    meta: { requiresAdmin: true }
  },
  {
    path: '/backstore/notifications',
    component: AdminNotificationsPage,
    meta: { requiresAdmin: true }
  },
  {
    path: '/backstore/carts',
    component: AdminCartsPage,
    meta: { requiresAdmin: true }
  },
  {
    path: '/backstore/logs',
    component: AdminLogsPage,
    meta: { requiresAdmin: true }
  },
  {
    path: '/:pathMatch(.*)*',
    redirect: '/'
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

const getSession = () => {
  const token = localStorage.getItem('token')
  const rawUser = localStorage.getItem('user')
  let role = ''

  if (rawUser) {
    try {
      const user = JSON.parse(rawUser)
      role = (user?.role || '').toLowerCase()
    } catch {
      role = ''
    }
  }

  return {
    isLoggedIn: !!token,
    isAdmin: role === 'admin',
    isCustomer: role === 'customer'
  }
}

router.beforeEach((to) => {
  const { isLoggedIn, isAdmin, isCustomer } = getSession()

  console.log('[router] navigating to:', to.path, { isLoggedIn, isAdmin, isCustomer, requiresAdmin: !!to.meta.requiresAdmin })

  if (to.meta.requiresAdmin) {
    if (!isLoggedIn) {
      console.log('[router] requiresAdmin but not logged in → /backstore/login')
      return '/backstore/login'
    }

    if (!isAdmin) {
      console.log('[router] requiresAdmin but not admin (role mismatch) → /backstore/login')
      return '/backstore/login'
    }
  }

  if (to.path === '/backstore/login' && isLoggedIn && isAdmin) {
    console.log('[router] already admin, redirecting away from login → /backstore/dashboard')
    return '/backstore/dashboard'
  }

  if ((to.path === '/login' || to.path === '/register') && isLoggedIn) {
    const dest = isAdmin ? '/backstore/dashboard' : '/'
    console.log('[router] already logged in on auth page → ' + dest)
    return dest
  }

  if (to.path === '/profile') {
    if (!isLoggedIn) {
      console.log('[router] /profile requires login → /login')
      return '/login'
    }

    if (!isCustomer) {
      const dest = isAdmin ? '/backstore/dashboard' : '/'
      console.log('[router] /profile: not a customer → ' + dest)
      return dest
    }
  }

  if (to.path === '/orders' && !isLoggedIn && !to.query?.placed) {
    console.log('[router] /orders requires login → /login')
    return '/login'
  }

  if (to.path === '/addresses' && !isLoggedIn) {
    return '/login'
  }
})

export default router
