import { createRouter, createWebHistory } from 'vue-router'

import StoreHomePage from '../store/HomePage.vue'
import AdminDashboardPage from '../admin/DashboardPage.vue'
import AdminLoginPage from '../admin/LoginPage.vue'
import AdminSettingsPage from '../admin/SettingsPage.vue'
import CustomerAuthPage from '../customer/AuthPage.vue'
import CustomerProfilePage from '../customer/ProfilePage.vue'

const routes = [
  {
    path: '/',
    component: StoreHomePage,
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
    path: '/backstore/dashboard',
    component: AdminDashboardPage,
    meta: { requiresAdmin: true }
  },
  {
    path: '/backstore/login',
    component: AdminLoginPage
  },
  {
    path: '/backstore/settings',
    component: AdminSettingsPage,
    meta: { requiresAdmin: true }
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

  if (to.meta.requiresAdmin) {
    if (!isLoggedIn) {
      return '/backstore/login'
    }

    if (!isAdmin) {
      return '/'
    }
  }

  if (to.path === '/backstore/login' && isLoggedIn) {
    return isAdmin ? '/backstore/dashboard' : '/'
  }

  if ((to.path === '/login' || to.path === '/register') && isLoggedIn) {
    return isAdmin ? '/backstore/dashboard' : '/'
  }

  if (to.path === '/profile') {
    if (!isLoggedIn) {
      return '/login'
    }

    if (!isCustomer) {
      return isAdmin ? '/backstore/dashboard' : '/'
    }
  }
})

export default router
