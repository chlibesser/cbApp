import { createRouter, createWebHistory } from 'vue-router'
import { authGuard, adminGuard } from './guards'

// Lazy load layouts
const AuthLayout = () => import('../../shared/layouts/AuthLayout.vue')
const DashboardLayout = () => import('../../shared/layouts/DashboardLayout.vue')
const AdminLayout = () => import('../../domains/admin/layouts/AdminLayout.vue')

// Lazy load views
const LoginView = () => import('../../domains/identity/views/LoginView.vue')
const RegisterView = () => import('../../domains/identity/views/RegisterView.vue')

const routes = [
  {
    path: '/',
    redirect: '/dashboard',
  },
  // Auth routes (login, register, etc.)
  {
    path: '/auth',
    component: AuthLayout,
    meta: { guestOnly: true },
    children: [
      {
        path: 'login',
        name: 'login',
        component: LoginView,
      },
      {
        path: 'register',
        name: 'register',
        component: RegisterView,
      },
      {
        path: '',
        redirect: '/auth/login',
      },
    ],
  },
  // Legacy login route redirect
  {
    path: '/login',
    redirect: '/auth/login',
  },
  // Main application routes
  {
    path: '/',
    component: DashboardLayout,
    meta: { requiresAuth: true },
    children: [
      {
        path: 'dashboard',
        name: 'dashboard',
        component: () => import('../../shared/views/DashboardView.vue'),
      },
      // Identity domain routes
      // {
      //   path: 'profile',
      //   name: 'profile',
      //   component: () => import('../../domains/identity/views/ProfileView.vue'),
      // },
      // TODO: Add other domain routes here as they're created
      // Workflow routes
      // {
      //   path: 'todos',
      //   name: 'todos',
      //   component: () => import('../../domains/workflow/views/TodosView.vue'),
      // },
    ],
  },
  // Admin routes with separate layout
  {
    path: '/admin',
    component: AdminLayout,
    meta: { requiresAuth: true, requiresAdmin: true },
    children: [
      {
        path: '',
        redirect: '/admin/tenants',
      },
      {
        path: 'tenants',
        name: 'admin-tenants',
        component: () => import('../../domains/admin/views/TenantsView.vue'),
      },
      {
        path: 'accounts',
        name: 'admin-accounts',
        component: () => import('../../domains/admin/views/AccountsView.vue'),
      },
    ],
  },
]

export const router = createRouter({
  history: createWebHistory(),
  routes,
})

// Apply guards
router.beforeEach(authGuard)
router.beforeEach(adminGuard)

export default router
