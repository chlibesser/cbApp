<template>
  <v-navigation-drawer
    v-model="layoutStore.sidebarOpen"
    :permanent="layoutStore.sidebarPermanent"
    :width="280"
    color="surface"
    elevation="2"
    border="0"
  >
    <!-- Loading State -->
    <div v-if="!authStore.isAuthenticated" class="pa-4 text-center">
      <v-progress-circular 
        indeterminate 
        size="24"
        color="primary"
      />
      <p class="text-body-2 mt-2 mb-0">Lädt...</p>
    </div>

    <!-- Navigation Menu -->
    <v-list v-else density="compact" nav class="py-2">
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
      <template v-if="isAdmin || isDevelopment">
        <v-divider class="my-4" />
        
        <v-list-subheader class="text-primary font-weight-medium">
          <v-icon size="small" class="mr-2">mdi-shield-crown</v-icon>
          Administration
          <v-chip v-if="!isAdmin" size="x-small" color="warning" class="ml-2">DEBUG</v-chip>
        </v-list-subheader>

        <v-list-item
          to="/admin/tenants"
          prepend-icon="mdi-domain"
          title="Tenants"
          value="admin-tenants"
        >
          <template #append>
            <v-chip size="x-small" color="primary" variant="flat">
              Neu
            </v-chip>
          </template>
        </v-list-item>

        <v-list-item
          to="/admin/accounts"
          prepend-icon="mdi-account-group"
          title="Accounts"
          value="admin-accounts"
        />

        <v-list-item
          to="/admin/roles"
          prepend-icon="mdi-account-key"
          title="Rollen"
          value="admin-roles"
          disabled
        >
          <template #append>
            <v-chip size="x-small" color="grey" variant="outlined">
              Bald
            </v-chip>
          </template>
        </v-list-item>

        <v-list-item
          to="/admin/permissions"
          prepend-icon="mdi-shield-lock"
          title="Berechtigungen"
          value="admin-permissions"
          disabled
        >
          <template #append>
            <v-chip size="x-small" color="grey" variant="outlined">
              Bald
            </v-chip>
          </template>
        </v-list-item>

        <v-list-item
          to="/admin/system"
          prepend-icon="mdi-cog"
          title="System"
          value="admin-system"
          disabled
        >
          <template #append>
            <v-chip size="x-small" color="grey" variant="outlined">
              Bald
            </v-chip>
          </template>
        </v-list-item>
      </template>
    </v-list>

    <!-- Footer (Development Info) -->
    <template v-if="isDevelopment" #append>
      <div class="pa-3 border-t">
        <div class="text-caption text-medium-emphasis">
          <div>Mode: {{ environment }}</div>
          <div>User: {{ authStore.account?.username || 'N/A' }}</div>
          <div>Role: {{ authStore.account?.system_role || 'N/A' }}</div>
          <div>isAdmin: {{ isAdmin ? 'YES' : 'NO' }}</div>
          <div>Tenant: {{ currentTenant?.name || 'N/A' }}</div>
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
  // Wenn keine Permission erforderlich, immer anzeigen
  if (!permission) return true
  
  // Prüfe erstmal ob User überhaupt eingeloggt ist
  if (!authStore.isAuthenticated) {
    return false
  }
  
  // Für V1: Einfache Fallback-Logik, da Permission-System noch nicht vollständig implementiert
  return true
}

// Role checking
const isAdmin = computed(() => {
  if (!authStore.isAuthenticated) return false
  
  // Debug logging
  console.log('isAdmin check:', {
    isAuthenticated: authStore.isAuthenticated,
    account: authStore.account,
    systemRole: authStore.account?.system_role
  })
  
  // Check SystemRole - Admin und TenantAdmin haben Admin-Zugriff
  if (authStore.account?.system_role === 'admin' || 
      authStore.account?.system_role === 'tenant_admin') {
    return true
  }
  
  // Fallback: Permission-basierte Prüfung
  return authStore.hasPermission('admin.view')
})

// Navigation structure - nur verfügbare Features für V1
const navigationSections = computed((): NavigationSection[] => {
  // Wenn nicht eingeloggt, leere Sections zurückgeben
  if (!authStore.isAuthenticated) {
    return []
  }

  return [
    {
      title: 'Foundation',
      items: [
        {
          name: 'foundation-status',
          title: 'Foundation Status',
          to: '/foundation',
          icon: 'mdi-check-decagram',
          badge: {
            text: 'V1.0',
            color: 'success'
          }
        }
      ]
    }
  ]
})
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