<template>
  <v-navigation-drawer
    v-model="layoutStore.sidebarOpen"
    :permanent="layoutStore.sidebarPermanent"
    :width="280"
    color="surface"
    elevation="2"
    border="0"
  >
    <!-- Navigation Menu -->
    <v-list density="compact" nav class="py-2">
      <!-- Dashboard -->
      <v-list-item
        to="/dashboard"
        prepend-icon="mdi-view-dashboard"
        title="Dashboard"
        value="dashboard"
      />

      <!-- Core Modules -->
      <template v-for="section in navigationSections" :key="section.title">
        <!-- Section Header -->
        <v-list-subheader 
          v-if="section.items.some(item => !item.permission || hasPermission(item.permission))"
          class="text-primary font-weight-medium mt-4"
        >
          {{ section.title }}
        </v-list-subheader>

        <!-- Section Items -->
        <template v-for="item in section.items" :key="item.name">
          <v-list-item
            v-if="!item.permission || hasPermission(item.permission)"
            :to="item.to"
            :prepend-icon="item.icon"
            :title="item.title"
            :value="item.name"
            :disabled="item.disabled"
          >
            <template v-if="item.badge" #append>
              <v-chip
                size="x-small"
                :color="item.badge.color"
                variant="flat"
              >
                {{ item.badge.text }}
              </v-chip>
            </template>
          </v-list-item>

          <!-- Nested Items -->
          <template v-if="item.children && item.children.length > 0">
            <v-list-group
              v-if="!item.permission || hasPermission(item.permission)"
              :prepend-icon="item.icon"
              :value="item.name"
            >
              <template #activator="{ props }">
                <v-list-item
                  v-bind="props"
                  :title="item.title"
                />
              </template>

              <template v-for="child in item.children" :key="child?.name || 'unknown'">
                <v-list-item
                  v-if="child && child.name && (!child.permission || hasPermission(child.permission))"
                  :to="child.to"
                  :title="child.title"
                  :value="child.name"
                  class="ms-4"
                />
              </template>
            </v-list-group>
          </template>
        </template>
      </template>

      <!-- Admin Section (bottom) -->
      <template v-if="isAdmin">
        <v-divider class="my-4" />
        
        <v-list-subheader class="text-primary font-weight-medium">
          Administration
        </v-list-subheader>

        <v-list-item
          to="/admin/accounts"
          prepend-icon="mdi-account-group"
          title="Accounts"
          value="admin-accounts"
        />

        <v-list-item
          to="/admin/tenants"
          prepend-icon="mdi-domain"
          title="Tenants"
          value="admin-tenants"
        />
      </template>
    </v-list>

    <!-- Footer (Development Info) -->
    <template v-if="isDevelopment" #append>
      <div class="pa-3 border-t">
        <div class="text-caption text-medium-emphasis">
          <div>Mode: {{ environment }}</div>
          <div>User: {{ authStore.profile?.display_name || 'N/A' }}</div>
          <div>Tenant: {{ currentTenant?.id || 'N/A' }}</div>
        </div>
      </div>
    </template>
  </v-navigation-drawer>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useAuthStore } from '../../infrastructure/stores/authStore'
import { useLayoutStore } from '../../infrastructure/stores/layoutStore'

interface NavigationItem {
  name: string
  title: string
  to: string
  icon: string
  permission?: string
  disabled?: boolean
  badge?: {
    text: string
    color: string
  }
  children?: NavigationItem[]
}

interface NavigationSection {
  title: string
  items: NavigationItem[]
}

const authStore = useAuthStore()
const layoutStore = useLayoutStore()

const currentTenant = computed(() => authStore.currentTenant)
const environment = computed(() => import.meta.env.MODE)
const isDevelopment = computed(() => environment.value === 'development')

// Permission checking
const hasPermission = (permission: string): boolean => {
  if (!permission) return true
  
  // Prüfe erstmal ob User überhaupt eingeloggt ist
  if (!authStore.isAuthenticated) {
    return false
  }
  
  // Fallback für Demo - zeige alle Items wenn keine Permissions gesetzt sind
  if (!authStore.currentTenant?.role?.permissions) {
    console.log('No permissions found, showing all menu items for demo')
    return true
  }
  
  return authStore.hasPermission(permission)
}

// Role checking
const isAdmin = computed(() => {
  if (!authStore.isAuthenticated) return false
  return authStore.hasPermission('admin.view')
})

// Navigation structure based on V0 analysis
const navigationSections = computed((): NavigationSection[] => [
  {
    title: 'Workflow',
    items: [
      {
        name: 'todos',
        title: 'Todos',
        to: '/todos',
        icon: 'mdi-check-circle-outline',
        permission: 'todos.view'
      },
      {
        name: 'notes',
        title: 'Notizen',
        to: '/notes',
        icon: 'mdi-note-text-outline',
        permission: 'notes.view'
      },
      {
        name: 'workflows',
        title: 'Workflows',
        to: '/workflows',
        icon: 'mdi-workflow',
        permission: 'workflow.view',
        badge: {
          text: 'NEW',
          color: 'primary'
        }
      }
    ]
  },
  {
    title: 'Business',
    items: [
      {
        name: 'projects',
        title: 'Projekte',
        to: '/projects',
        icon: 'mdi-folder-multiple-outline',
        permission: 'project.view'
      },
      {
        name: 'crm',
        title: 'CRM',
        to: '/crm/companies',
        icon: 'mdi-account-group',
        permission: 'crm.view',
        children: [
          {
            name: 'companies',
            title: 'Unternehmen',
            to: '/crm/companies',
            icon: 'mdi-domain',
            permission: 'crm.company.view'
          },
          {
            name: 'contacts',
            title: 'Kontakte',
            to: '/crm/contacts',
            icon: 'mdi-account-multiple',
            permission: 'crm.contact.view'
          },
          {
            name: 'activities',
            title: 'Aktivitäten',
            to: '/crm/activities',
            icon: 'mdi-calendar-clock',
            permission: 'crm.activity.view'
          }
        ]
      }
    ]
  },
  {
    title: 'Finanzen',
    items: [
      {
        name: 'finance',
        title: 'Buchhaltung',
        to: '/finance/invoices',
        icon: 'mdi-calculator',
        permission: 'finance.view',
        children: [
          {
            name: 'invoices',
            title: 'Rechnungen',
            to: '/finance/invoices',
            icon: 'mdi-file-document',
            permission: 'finance.invoice.view'
          },
          {
            name: 'chart-of-accounts',
            title: 'Kontenplan',
            to: '/finance/chart-of-accounts',
            icon: 'mdi-chart-tree',
            permission: 'finance.chart.view'
          }
        ]
      }
    ]
  },
  {
    title: 'Content',
    items: [
      {
        name: 'media',
        title: 'Medien',
        to: '/media',
        icon: 'mdi-image-multiple',
        permission: 'media.view'
      },
      {
        name: 'email',
        title: 'E-Mail',
        to: '/email/processed',
        icon: 'mdi-email',
        permission: 'email.view',
        children: [
          {
            name: 'processed',
            title: 'Verarbeitete E-Mails',
            to: '/email/processed',
            icon: 'mdi-email-check',
            permission: 'email.processed.view'
          },
          {
            name: 'accounts',
            title: 'E-Mail Konten',
            to: '/email/accounts',
            icon: 'mdi-email-outline',
            permission: 'email.account.view'
          }
        ]
      }
    ]
  },
  {
    title: 'Tenant',
    items: [
      {
        name: 'tenant-profiles',
        title: 'Profile',
        to: '/tenant/profiles',
        icon: 'mdi-account-circle',
        permission: 'tenant.profile.view'
      },
      {
        name: 'tenant-roles',
        title: 'Rollen',
        to: '/tenant/tenant-roles',
        icon: 'mdi-account-key',
        permission: 'tenant.role.view'
      },
      {
        name: 'tenant-settings',
        title: 'Einstellungen',
        to: '/tenant/settings',
        icon: 'mdi-cog',
        permission: 'tenant.settings.view'
      }
    ]
  }
])
</script>

<style scoped>

.v-navigation-drawer {
  border-right: 1px solid rgba(var(--v-border-color), var(--v-border-opacity)) !important;
}

.v-list-item {
  margin-bottom: 2px;
}

.v-list-item--active {
  background-color: rgba(var(--v-theme-primary), 0.12) !important;
}

.v-list-subheader {
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

</style>