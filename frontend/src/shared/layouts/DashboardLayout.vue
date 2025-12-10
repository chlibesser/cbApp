<template>
  <v-app>
    <!-- App Header -->
    <AppHeader />

    <!-- Navigation Sidebar -->
    <AppSidebar />

    <!-- Main Content Area -->
    <v-main data-testid="dashboard-content">
      <v-container fluid class="pa-4">
        <!-- Breadcrumbs -->
        <v-breadcrumbs
          v-if="breadcrumbs.length > 1"
          :items="breadcrumbs"
          class="px-0 py-2"
          data-testid="breadcrumbs"
        >
          <template v-slot:prepend>
            <v-icon size="small">mdi-home</v-icon>
          </template>
          
          <template v-slot:item="{ item }">
            <v-breadcrumbs-item
              :disabled="item.disabled"
              :to="item.to"
            >
              {{ item.title }}
            </v-breadcrumbs-item>
          </template>
        </v-breadcrumbs>

        <!-- Page Content -->
        <div class="page-content" data-testid="page-content">
          <router-view />
        </div>
      </v-container>
    </v-main>

    <!-- Right Side Drawer -->
    <RightDrawer />

    <!-- Toast Notifications -->
    <ToastNotifications />
  </v-app>
</template>

<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import AppHeader from '../components/AppHeader.vue'
import { useAuthStore } from '../../infrastructure/stores/authStore'
import { useLayoutStore } from '../../infrastructure/stores/layoutStore'

// Import layout components
import AppSidebar from '../components/AppSidebar.vue'
import RightDrawer from '../components/RightDrawer.vue'
import ToastNotifications from '../components/ToastNotifications.vue'

const route = useRoute()
const authStore = useAuthStore()
const layoutStore = useLayoutStore()

// Generate breadcrumbs from current route
const breadcrumbs = computed(() => {
  const routeParts = route.path.split('/').filter(Boolean)
  const crumbs = [{
    title: 'Dashboard',
    to: '/dashboard',
    disabled: false
  }]

  let currentPath = ''
  for (let i = 0; i < routeParts.length; i++) {
    currentPath += `/${routeParts[i]}`
    
    // Skip dashboard as it's already added as root
    if (routeParts[i] === 'dashboard') continue
    
    // Generate breadcrumb title from route part
    const title = routeParts[i]
      .split('-')
      .map(word => word.charAt(0).toUpperCase() + word.slice(1))
      .join(' ')
    
    crumbs.push({
      title,
      to: currentPath,
      disabled: i === routeParts.length - 1 // Last item is disabled (current page)
    })
  }

  return crumbs
})

onMounted(async () => {
  // Load user data if not already loaded
  if (!authStore.isAuthenticated) {
    await authStore.loadUserData()
  }
})
</script>

<style scoped>
/* Ensure smooth transitions */
.v-main {
  transition: padding-left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Mobile responsive adjustments */
@media (max-width: 959px) {
  .v-container {
    padding: 8px !important;
  }
}

/* Better breadcrumb spacing */
.v-breadcrumbs {
  padding-left: 0 !important;
  padding-right: 0 !important;
}
</style>
