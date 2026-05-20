import { createRouter, createWebHistory } from 'vue-router'

import StoreHomePage from '../store/HomePage.vue'
import AdminDashboardPage from '../admin/DashboardPage.vue'
import AdminLoginPage from '../admin/LoginPage.vue'
import AdminSettingsPage from '../admin/SettingsPage.vue'

const routes = [
  {
    path: '/',
    component: StoreHomePage,
    meta: { layout: 'store' }
  },
  {
    path: '/backstore/dashboard',
    component: AdminDashboardPage
  },
  {
    path: '/backstore/login',
    component: AdminLoginPage
  },
  {
    path: '/backstore/settings',
    component: AdminSettingsPage
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

export default router