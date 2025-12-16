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
      {
        path: 'foundation',
        name: 'foundation',
        component: () => import('../../shared/views/FoundationView.vue'),
      },
      // Identity domain routes
      // {
      //   path: 'profile',
      //   name: 'profile',
      //   component: () => import('../../domains/identity/views/ProfileView.vue'),
      // },
      // Tenant management routes (for tenant admins)
      {
        path: 'tenant',
        children: [
          {
            path: 'users',
            name: 'tenant-users',
            component: () => import('../../domains/tenant/views/UsersView.vue'),
          },
          {
            path: 'categories',
            name: 'tenant-categories',
            component: () => import('../../domains/tenant/views/CategoryManagementView.vue'),
          },
          {
            path: 'documents',
            name: 'tenant-documents',
            component: () => import('../../domains/tenant/views/DocumentManagementView.vue'),
          },
          {
            path: 'documents/:id',
            name: 'tenant-document-detail',
            component: () => import('../../domains/tenant/views/DocumentDetailView.vue'),
            props: true
          },
          {
            path: 'workflows',
            name: 'workflows',
            component: () => import('../../domains/workflow/views/WorkflowsView.vue'),
          },
          {
            path: 'workflows/builder/:id?',
            name: 'workflow-builder',
            component: () => import('../../domains/workflow/views/WorkflowBuilderView.vue'),
            props: true
          },
          // TODO: Add other tenant admin routes
          // {
          //   path: 'settings',
          //   name: 'tenant-settings',
          //   component: () => import('../../domains/tenant/views/SettingsView.vue'),
          // },
        ],
      },
      
    ],
  },
  // Admin routes - using same DashboardLayout to keep sidebar
  {
    path: '/admin',
    component: DashboardLayout,
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
        path: 'tenants/:id',
        name: 'admin-tenant-detail',
        component: () => import('../../domains/admin/views/TenantDetailView.vue'),
        props: true
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
