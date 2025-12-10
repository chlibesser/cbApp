<template>
  <v-app-bar app>
    <!-- Sidebar Toggle Button -->
    <v-btn
      icon
      @click="layoutStore.toggleSidebar()"
      class="me-3"
    >
      <v-icon>mdi-menu</v-icon>
    </v-btn>

    <!-- Logo -->
    <router-link to="/dashboard" class="d-flex align-center text-decoration-none me-4">
      <img 
        src="/Logo_cbapp.svg" 
        alt="cbApp Logo" 
        class="header-logo me-2"
      />
      <span class="text-h6 font-weight-medium text-white">cbApp</span>
    </router-link>

    <v-spacer />

    <!-- Tenant Switcher -->
    <v-menu v-if="currentTenant" offset-y>
      <template v-slot:activator="{ props }">
        <v-btn variant="outlined" v-bind="props" class="me-4">
          <v-icon start :color="currentTenant.is_personal ? 'purple' : 'blue'">
            {{ currentTenant.is_personal ? 'mdi-account' : 'mdi-office-building' }}
          </v-icon>
          {{ currentTenant.name }}
          <v-icon end>mdi-chevron-down</v-icon>
        </v-btn>
      </template>

      <v-card min-width="300">
        <v-card-title class="text-subtitle-1">
          <v-icon class="me-2">mdi-swap-horizontal</v-icon>
          Switch Workspace
        </v-card-title>
        
        <v-card-text>
          <div class="mb-3">
            <div class="text-caption text-medium-emphasis">Current:</div>
            <v-chip 
              :color="currentTenant.is_personal ? 'purple' : 'blue'" 
              variant="tonal" 
              size="small"
              class="mt-1"
            >
              <v-icon start size="small">
                {{ currentTenant.is_personal ? 'mdi-account' : 'mdi-office-building' }}
              </v-icon>
              {{ currentTenant.name }}
              <v-chip size="x-small" color="success" class="ms-2">
                {{ currentTenant.role?.name }}
              </v-chip>
            </v-chip>
          </div>

          <div v-if="availableTenants.length > 1">
            <div class="text-caption text-medium-emphasis mb-2">Switch to:</div>
            <v-list density="compact">
              <v-list-item
                v-for="tenant in otherTenants"
                :key="tenant.id"
                @click="switchToTenant(tenant)"
                class="px-0"
              >
                <template v-slot:prepend>
                  <v-icon :color="tenant.is_personal ? 'purple' : 'blue'">
                    {{ tenant.is_personal ? 'mdi-account' : 'mdi-office-building' }}
                  </v-icon>
                </template>
                
                <v-list-item-title>{{ tenant.name }}</v-list-item-title>
                <v-list-item-subtitle>{{ tenant.role?.name }}</v-list-item-subtitle>
              </v-list-item>
            </v-list>
          </div>
        </v-card-text>
      </v-card>
    </v-menu>

    <v-menu>
      <template v-slot:activator="{ props }">
        <v-btn icon v-bind="props">
          <v-avatar size="32">
            <v-icon>mdi-account</v-icon>
          </v-avatar>
        </v-btn>
      </template>

      <v-list>
        <v-list-item
          v-if="account"
          :title="account.username"
          :subtitle="account.email"
        />
        <v-divider />
        
        <!-- Permission-based Menu Items -->
        <v-list-item 
          v-if="canAccess('users', 'view')" 
          prepend-icon="mdi-account-multiple" 
          title="Users" 
          @click="$router.push('/admin/accounts')" 
        />
        <v-list-item 
          v-if="canAccess('roles', 'view')" 
          prepend-icon="mdi-shield-account" 
          title="Roles & Permissions" 
          @click="$router.push('/admin/roles')" 
        />
        <v-list-item 
          v-if="canAccess('settings', 'view')" 
          prepend-icon="mdi-cog" 
          title="Settings" 
          @click="$router.push('/admin/settings')" 
        />
        
        <v-divider v-if="hasAnyAdminAccess" />
        
        <v-list-item prepend-icon="mdi-account" title="Profile" @click="$router.push('/profile')" />
        <v-list-item prepend-icon="mdi-logout" title="Logout" @click="handleLogout" />
      </v-list>
    </v-menu>
  </v-app-bar>
</template>

<script setup lang="ts">
  import { computed } from 'vue'
  import { useRouter } from 'vue-router'
  import { useAuthStore } from '../../infrastructure/stores/authStore'
  import { useLayoutStore } from '../../infrastructure/stores/layoutStore'

  const router = useRouter()
  const authStore = useAuthStore()
  const layoutStore = useLayoutStore()

  const account = computed(() => authStore.account)
  const currentTenant = computed(() => authStore.currentTenant)
  
  // Mock available tenants for demo - in real app this would come from the store
  const availableTenants = computed(() => {
    if (!account.value) return []
    return [
      currentTenant.value,
      // Add other tenants the user has access to
    ].filter(Boolean)
  })
  
  const otherTenants = computed(() => 
    availableTenants.value.filter(tenant => tenant.id !== currentTenant.value?.id)
  )

  // Permission-based computed properties
  const canAccess = (resource: string, action: string) => 
    authStore.canAccess(resource, action)
  
  const hasAnyAdminAccess = computed(() => 
    canAccess('users', 'view') || canAccess('roles', 'view') || canAccess('settings', 'view')
  )

  const switchToTenant = (tenant: any) => {
    authStore.switchTenant(tenant)
    // Optionally redirect to appropriate page for the tenant
    router.push('/dashboard')
  }

  const handleLogout = async () => {
    try {
      await authStore.logout()
      // Force a full page reload to ensure clean state
      window.location.href = '/auth/login'
    } catch (error) {
      console.error('Logout failed:', error)
      // Force redirect even if logout fails
      window.location.href = '/auth/login'
    }
  }
</script>

<style scoped>
.header-logo {
  height: 32px;
  width: auto;
  filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.2));
}

/* Mobile responsive */
@media (max-width: 600px) {
  .header-logo {
    height: 28px;
  }
  
  /* Hide text on mobile, show only logo */
  .text-h6 {
    display: none;
  }
}

/* Hover effect for logo link */
.router-link:hover {
  opacity: 0.9;
  transition: opacity 0.2s ease;
}
</style>
