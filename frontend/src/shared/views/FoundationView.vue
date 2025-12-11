<template>
  <v-container fluid>
    <!-- Page Header -->
    <div class="d-flex align-center mb-6">
      <v-icon size="48" color="success" class="mr-4">mdi-check-decagram</v-icon>
      <div>
        <h1 class="text-h4 font-weight-bold">cbApp V1 Foundation</h1>
        <p class="text-body-1 text-medium-emphasis mb-0">
          Basis-Architektur und Authentifizierungs-System
        </p>
      </div>
    </div>

    <!-- Foundation Status Cards -->
    <v-row>
      <!-- Authentication System -->
      <v-col cols="12" md="6" lg="4">
        <v-card class="h-100">
          <v-card-title class="d-flex align-center">
            <v-icon color="success" class="mr-2">mdi-shield-check</v-icon>
            Auth-System
          </v-card-title>
          <v-card-text>
            <v-list lines="one" density="compact">
              <v-list-item>
                <v-list-item-title>Laravel Sanctum</v-list-item-title>
                <template #append>
                  <v-chip size="small" color="success" variant="flat">✓</v-chip>
                </template>
              </v-list-item>
              <v-list-item>
                <v-list-item-title>UUID-basierte IDs</v-list-item-title>
                <template #append>
                  <v-chip size="small" color="success" variant="flat">✓</v-chip>
                </template>
              </v-list-item>
              <v-list-item>
                <v-list-item-title>Account/Profile Trennung</v-list-item-title>
                <template #append>
                  <v-chip size="small" color="success" variant="flat">✓</v-chip>
                </template>
              </v-list-item>
              <v-list-item>
                <v-list-item-title>Quick Login (Dev)</v-list-item-title>
                <template #append>
                  <v-chip size="small" color="success" variant="flat">✓</v-chip>
                </template>
              </v-list-item>
            </v-list>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- Tenant Management -->
      <v-col cols="12" md="6" lg="4">
        <v-card class="h-100">
          <v-card-title class="d-flex align-center">
            <v-icon color="primary" class="mr-2">mdi-domain</v-icon>
            Tenant-System
          </v-card-title>
          <v-card-text>
            <v-list lines="one" density="compact">
              <v-list-item>
                <v-list-item-title>Multi-Tenancy</v-list-item-title>
                <template #append>
                  <v-chip size="small" color="success" variant="flat">✓</v-chip>
                </template>
              </v-list-item>
              <v-list-item>
                <v-list-item-title>SystemRole Enum</v-list-item-title>
                <template #append>
                  <v-chip size="small" color="success" variant="flat">✓</v-chip>
                </template>
              </v-list-item>
              <v-list-item>
                <v-list-item-title>Admin-Management</v-list-item-title>
                <template #append>
                  <v-chip size="small" color="success" variant="flat">✓</v-chip>
                </template>
              </v-list-item>
              <v-list-item>
                <v-list-item-title>User Assignment</v-list-item-title>
                <template #append>
                  <v-chip size="small" color="success" variant="flat">✓</v-chip>
                </template>
              </v-list-item>
            </v-list>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- Frontend Architecture -->
      <v-col cols="12" md="6" lg="4">
        <v-card class="h-100">
          <v-card-title class="d-flex align-center">
            <v-icon color="info" class="mr-2">mdi-vuejs</v-icon>
            Frontend
          </v-card-title>
          <v-card-text>
            <v-list lines="one" density="compact">
              <v-list-item>
                <v-list-item-title>Vue 3 + TypeScript</v-list-item-title>
                <template #append>
                  <v-chip size="small" color="success" variant="flat">✓</v-chip>
                </template>
              </v-list-item>
              <v-list-item>
                <v-list-item-title>Vuetify 3</v-list-item-title>
                <template #append>
                  <v-chip size="small" color="success" variant="flat">✓</v-chip>
                </template>
              </v-list-item>
              <v-list-item>
                <v-list-item-title>Pinia Store</v-list-item-title>
                <template #append>
                  <v-chip size="small" color="success" variant="flat">✓</v-chip>
                </template>
              </v-list-item>
              <v-list-item>
                <v-list-item-title>Domain-Architektur</v-list-item-title>
                <template #append>
                  <v-chip size="small" color="success" variant="flat">✓</v-chip>
                </template>
              </v-list-item>
            </v-list>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- Database & Backend -->
      <v-col cols="12" md="6" lg="4">
        <v-card class="h-100">
          <v-card-title class="d-flex align-center">
            <v-icon color="warning" class="mr-2">mdi-database</v-icon>
            Backend
          </v-card-title>
          <v-card-text>
            <v-list lines="one" density="compact">
              <v-list-item>
                <v-list-item-title>Laravel 10</v-list-item-title>
                <template #append>
                  <v-chip size="small" color="success" variant="flat">✓</v-chip>
                </template>
              </v-list-item>
              <v-list-item>
                <v-list-item-title>PostgreSQL + UUIDs</v-list-item-title>
                <template #append>
                  <v-chip size="small" color="success" variant="flat">✓</v-chip>
                </template>
              </v-list-item>
              <v-list-item>
                <v-list-item-title>REST APIs</v-list-item-title>
                <template #append>
                  <v-chip size="small" color="success" variant="flat">✓</v-chip>
                </template>
              </v-list-item>
              <v-list-item>
                <v-list-item-title>RBAC System</v-list-item-title>
                <template #append>
                  <v-chip size="small" color="success" variant="flat">✓</v-chip>
                </template>
              </v-list-item>
            </v-list>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- RSD/ADT System -->
      <v-col cols="12" md="6" lg="4">
        <v-card class="h-100">
          <v-card-title class="d-flex align-center">
            <v-icon color="purple" class="mr-2">mdi-table-edit</v-icon>
            UI Patterns
          </v-card-title>
          <v-card-text>
            <v-list lines="one" density="compact">
              <v-list-item>
                <v-list-item-title>Right Side Drawer (RSD)</v-list-item-title>
                <template #append>
                  <v-chip size="small" color="success" variant="flat">✓</v-chip>
                </template>
              </v-list-item>
              <v-list-item>
                <v-list-item-title>Advanced Data Table</v-list-item-title>
                <template #append>
                  <v-chip size="small" color="warning" variant="flat">Partial</v-chip>
                </template>
              </v-list-item>
              <v-list-item>
                <v-list-item-title>CRUD Components</v-list-item-title>
                <template #append>
                  <v-chip size="small" color="success" variant="flat">✓</v-chip>
                </template>
              </v-list-item>
              <v-list-item>
                <v-list-item-title>Toast Notifications</v-list-item-title>
                <template #append>
                  <v-chip size="small" color="success" variant="flat">✓</v-chip>
                </template>
              </v-list-item>
            </v-list>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- Development Tools -->
      <v-col cols="12" md="6" lg="4">
        <v-card class="h-100">
          <v-card-title class="d-flex align-center">
            <v-icon color="secondary" class="mr-2">mdi-wrench</v-icon>
            Dev Tools
          </v-card-title>
          <v-card-text>
            <v-list lines="one" density="compact">
              <v-list-item>
                <v-list-item-title>pnpm Package Manager</v-list-item-title>
                <template #append>
                  <v-chip size="small" color="success" variant="flat">✓</v-chip>
                </template>
              </v-list-item>
              <v-list-item>
                <v-list-item-title>Vite Dev Server</v-list-item-title>
                <template #append>
                  <v-chip size="small" color="success" variant="flat">✓</v-chip>
                </template>
              </v-list-item>
              <v-list-item>
                <v-list-item-title>TypeScript Support</v-list-item-title>
                <template #append>
                  <v-chip size="small" color="success" variant="flat">✓</v-chip>
                </template>
              </v-list-item>
              <v-list-item>
                <v-list-item-title>Hot Module Reload</v-list-item-title>
                <template #append>
                  <v-chip size="small" color="success" variant="flat">✓</v-chip>
                </template>
              </v-list-item>
            </v-list>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Current User Info -->
    <v-row class="mt-6">
      <v-col cols="12">
        <v-card>
          <v-card-title class="d-flex align-center">
            <v-icon color="info" class="mr-2">mdi-account-circle</v-icon>
            Aktuelle Session
          </v-card-title>
          <v-card-text>
            <v-row>
              <v-col cols="12" md="3">
                <div class="text-subtitle-2">Account</div>
                <div class="text-body-1">{{ authStore.account?.username || 'N/A' }}</div>
              </v-col>
              <v-col cols="12" md="3">
                <div class="text-subtitle-2">System Role</div>
                <v-chip 
                  size="small" 
                  :color="getSystemRoleColor(authStore.account?.system_role)"
                  variant="flat"
                >
                  {{ authStore.account?.system_role_label || 'N/A' }}
                </v-chip>
              </v-col>
              <v-col cols="12" md="3">
                <div class="text-subtitle-2">Current Tenant</div>
                <div class="text-body-1">{{ authStore.currentTenant?.name || 'N/A' }}</div>
              </v-col>
              <v-col cols="12" md="3">
                <div class="text-subtitle-2">Environment</div>
                <v-chip 
                  size="small" 
                  :color="environment === 'development' ? 'warning' : 'success'"
                  variant="flat"
                >
                  {{ environment }}
                </v-chip>
              </v-col>
            </v-row>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Admin Quick Access -->
    <v-row v-if="isAdmin" class="mt-6">
      <v-col cols="12">
        <v-card>
          <v-card-title class="d-flex align-center">
            <v-icon color="error" class="mr-2">mdi-shield-crown</v-icon>
            Admin Quick Access
          </v-card-title>
          <v-card-text>
            <div class="d-flex gap-4 flex-wrap">
              <v-btn 
                color="primary" 
                prepend-icon="mdi-domain"
                to="/admin/tenants"
                variant="outlined"
              >
                Tenants verwalten
              </v-btn>
              <v-btn 
                color="secondary" 
                prepend-icon="mdi-account-group"
                to="/admin/accounts"
                variant="outlined"
              >
                Accounts verwalten
              </v-btn>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Next Steps -->
    <v-row class="mt-6">
      <v-col cols="12">
        <v-card>
          <v-card-title class="d-flex align-center">
            <v-icon color="success" class="mr-2">mdi-rocket-launch</v-icon>
            Nächste Schritte
          </v-card-title>
          <v-card-text>
            <v-timeline side="end" line-inset="8">
              <v-timeline-item
                dot-color="success"
                size="small"
              >
                <div>
                  <div class="text-h6">V1.0 Foundation ✓</div>
                  <p class="text-body-2 mb-0">Auth-System, Tenants, Basic UI - Abgeschlossen</p>
                </div>
              </v-timeline-item>

              <v-timeline-item
                dot-color="primary"
                size="small"
              >
                <div>
                  <div class="text-h6">V1.1 Workflow System</div>
                  <p class="text-body-2 mb-0">Todos, Notizen, Workflows - In Planung</p>
                </div>
              </v-timeline-item>

              <v-timeline-item
                dot-color="info"
                size="small"
              >
                <div>
                  <div class="text-h6">V1.2 CRM Module</div>
                  <p class="text-body-2 mb-0">Companies, Contacts, Activities - Roadmap</p>
                </div>
              </v-timeline-item>

              <v-timeline-item
                dot-color="warning"
                size="small"
              >
                <div>
                  <div class="text-h6">V1.3 Finance System</div>
                  <p class="text-body-2 mb-0">Buchhaltung, Rechnungen, Reports - Future</p>
                </div>
              </v-timeline-item>
            </v-timeline>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useAuthStore } from '../../infrastructure/stores/authStore'

const authStore = useAuthStore()
const environment = computed(() => import.meta.env.MODE)

const isAdmin = computed(() => {
  return authStore.account?.system_role === 'admin' || 
         authStore.account?.system_role === 'tenant_admin'
})

const getSystemRoleColor = (role: string | null | undefined) => {
  if (!role) return 'grey'
  const colorMap: Record<string, string> = {
    'admin': 'error',
    'tenant_admin': 'warning', 
    'member': 'info'
  }
  return colorMap[role] || 'grey'
}
</script>

<style scoped>
.h-100 {
  height: 100%;
}

.v-card {
  height: 100%;
  display: flex;
  flex-direction: column;
}

.v-card-text {
  flex: 1;
}
</style>