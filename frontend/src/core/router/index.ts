import { createRouter, createWebHistory } from 'vue-router'
import { authGuard, adminGuard } from './guards'

// Lazy load components
const LoginView = () => import('../../domains/identity/views/LoginView.vue')
const DashboardView = () => import('../../shared/layouts/DashboardLayout.vue')
const AdminLayout = () => import('../../domains/admin/layouts/AdminLayout.vue')

const routes = [
  {
    path: '/',
    redirect: '/dashboard'
  },
  {
    path: '/login',
    name: 'login',
    component: LoginView,
    meta: { guestOnly: true }
  },
  {
    path: '/dashboard',
    name: 'dashboard',
    component: DashboardView,
    meta: { requiresAuth: true }
  },
  {
    path: '/admin',
    component: AdminLayout,
    meta: { requiresAuth: true, requiresAdmin: true },
    children: [
      {
        path: '',
        redirect: '/admin/tenants'
      },
      {
        path: 'tenants',
        name: 'admin-tenants',
        component: () => import('../../domains/admin/views/TenantsView.vue')
      },
      {
        path: 'accounts',
        name: 'admin-accounts',
        component: () => import('../../domains/admin/views/AccountsView.vue')
      }
    ]
  }
]

export const router = createRouter({
  history: createWebHistory(),
  routes
})

// Apply guards
router.beforeEach(authGuard)
router.beforeEach(adminGuard)

export default router