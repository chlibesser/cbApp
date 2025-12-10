<template>
  <v-navigation-drawer
    v-model="layoutStore.rightDrawer.open"
    location="right"
    :width="drawerWidth"
    temporary
    elevation="8"
    class="right-drawer"
  >
    <!-- Header -->
    <div class="drawer-header d-flex align-center pa-4 border-b">
      <div class="flex-grow-1">
        <h3 class="text-h6 font-weight-medium">
          {{ layoutStore.rightDrawer.title }}
        </h3>
      </div>
      
      <v-btn
        icon="mdi-close"
        variant="text"
        size="small"
        @click="layoutStore.closeRightDrawer()"
        class="ms-2"
      />
    </div>

    <!-- Content -->
    <div class="drawer-content">
      <component
        v-if="layoutStore.rightDrawer.component"
        :is="getComponent(layoutStore.rightDrawer.component)"
        v-bind="layoutStore.rightDrawer.props"
        @close="layoutStore.closeRightDrawer()"
        @update:props="layoutStore.updateRightDrawerProps($event)"
      />
      
      <!-- Fallback content -->
      <div v-else class="pa-4 text-center text-medium-emphasis">
        <v-icon size="64" class="mb-4">mdi-information-outline</v-icon>
        <p>Kein Inhalt verfügbar</p>
      </div>
    </div>

    <!-- Footer Actions (if provided by component) -->
    <template v-if="hasFooterActions" #append>
      <div class="drawer-footer pa-4 border-t">
        <slot name="footer-actions" />
      </div>
    </template>
  </v-navigation-drawer>
</template>

<script setup lang="ts">
import { computed, useSlots } from 'vue'
import { useDisplay } from 'vuetify'
import { useLayoutStore } from '../../infrastructure/stores/layoutStore'

// Import your CRUD components here as needed
// import UserForm from '@/domains/admin/components/UserForm.vue'
// import TenantForm from '@/domains/admin/components/TenantForm.vue'
// import ProfileForm from '@/domains/identity/components/ProfileForm.vue'

const { mobile } = useDisplay()
const slots = useSlots()
const layoutStore = useLayoutStore()

// Dynamic component mapping
const componentMap: Record<string, any> = {
  // Add your components here as they're created
  // 'UserForm': UserForm,
  // 'TenantForm': TenantForm,
  // 'ProfileForm': ProfileForm,
}

const getComponent = (componentName: string) => {
  return componentMap[componentName] || null
}

const drawerWidth = computed(() => {
  if (mobile.value) return '100vw'
  return layoutStore.rightDrawer.width || 600
})

const hasFooterActions = computed(() => {
  return !!slots['footer-actions']
})
</script>

<style scoped>
.right-drawer {
  z-index: 2100 !important;
}

.drawer-header {
  background-color: rgba(var(--v-theme-surface), 1);
  border-bottom: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  min-height: 64px;
}

.drawer-content {
  height: calc(100vh - 64px);
  overflow-y: auto;
}

.drawer-footer {
  background-color: rgba(var(--v-theme-surface), 1);
  border-top: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}

/* Mobile optimizations */
@media (max-width: 960px) {
  .drawer-header {
    min-height: 56px;
  }
  
  .drawer-content {
    height: calc(100vh - 56px);
  }
}

/* Smooth animations */
.v-navigation-drawer--right.v-navigation-drawer--temporary {
  box-shadow: -4px 0 8px rgba(0, 0, 0, 0.12);
}

/* Dark mode adjustments */
.theme--dark .drawer-header,
.theme--dark .drawer-footer {
  border-color: rgba(255, 255, 255, 0.12);
}
</style>