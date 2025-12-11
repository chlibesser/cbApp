<template>
  <v-app>
    <!-- App Header -->
    <AppHeader />

    <!-- Navigation Sidebar -->
    <AppSidebar />

    <!-- Main Content Area -->
    <v-main data-testid="dashboard-content" class="dashboard-main">
      <div class="dashboard-content">
        <v-container fluid class="pa-4 h-100">
          <!-- Page Content -->
          <div class="page-content" data-testid="page-content">
            <router-view />
          </div>
        </v-container>
      </div>
    </v-main>

    <!-- Right Side Drawer -->
    <RightDrawer />

    <!-- Toast Notifications -->
    <ToastNotifications />
  </v-app>
</template>

<script setup lang="ts">
import { onMounted } from 'vue'
import AppHeader from '../components/AppHeader.vue'
import { useAuthStore } from '../../infrastructure/stores/authStore'
import { useLayoutStore } from '../../infrastructure/stores/layoutStore'

// Import layout components
import AppSidebar from '../components/AppSidebar.vue'
import RightDrawer from '../components/RightDrawer.vue'
import ToastNotifications from '../components/ToastNotifications.vue'

const authStore = useAuthStore()
const layoutStore = useLayoutStore()

onMounted(async () => {
  // Load user data if not already loaded
  if (!authStore.isAuthenticated) {
    await authStore.loadUserData()
  }
})
</script>

<style scoped>
/* Fixed Header Layout System */
.dashboard-main {
  height: calc(100vh - 64px); /* Subtract app-bar height */
  overflow: hidden;
}

.dashboard-content {
  height: 100%;
  overflow-y: auto;
  overflow-x: hidden;
}

.page-content {
  height: 100%;
  min-height: 0; /* Allow flexbox shrinking */
}

/* Ensure smooth transitions */
.v-main {
  transition: padding-left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Container adjustments for fixed layout */
.v-container {
  height: 100%;
  display: flex;
  flex-direction: column;
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
