<template>
  <v-navigation-drawer
    :model-value="rsdStore.isOpen"
    location="right"
    temporary
    width="600"
    class="rsd-wrapper"
    @update:model-value="handleDrawerUpdate"
  >
    <template v-if="rsdStore.isOpen">
      <!-- Header -->
      <div class="rsd-header">
        <v-card flat class="pa-4">
          <div class="d-flex align-center justify-space-between">
            <div>
              <h2 class="text-h6 font-weight-bold">{{ rsdStore.title }}</h2>
              <p v-if="rsdStore.subtitle" class="text-body-2 text-medium-emphasis mb-0">
                {{ rsdStore.subtitle }}
              </p>
            </div>
            
            <div class="d-flex gap-2">
              <v-btn
                v-if="rsdStore.isViewMode && rsdStore.data"
                icon="mdi-pencil"
                variant="outlined"
                size="small"
                color="primary"
                title="Bearbeiten"
                @click="handleEdit"
              />
              
              <v-btn
                icon="mdi-close"
                variant="text"
                size="small"
                @click="rsdStore.close"
              />
            </div>
          </div>
        </v-card>
        <v-divider />
      </div>

      <!-- Content -->
      <div class="rsd-content">
        <!-- Loading State -->
        <div v-if="rsdStore.loading" class="d-flex justify-center align-center pa-8">
          <v-progress-circular indeterminate size="48" />
        </div>

        <!-- Error State -->
        <v-alert
          v-else-if="rsdStore.error"
          type="error"
          class="ma-4"
        >
          {{ rsdStore.error }}
        </v-alert>

        <!-- Dynamic Component based on Entity and Mode -->
        <component
          v-else-if="currentComponent"
          :is="currentComponent"
          :entity="rsdStore.entity"
          :mode="rsdStore.mode"
          :data="rsdStore.data"
          @success="handleSuccess"
          @error="handleError"
          @close="rsdStore.close"
        />

        <!-- Fallback for unimplemented combinations -->
        <div v-else class="pa-4">
          <v-card>
            <v-card-text class="text-center pa-8">
              <v-icon size="48" color="grey-lighten-1">mdi-construction</v-icon>
              <p class="text-h6 mt-4">Komponente in Entwicklung</p>
              <p class="text-body-2 text-medium-emphasis">
                Die {{ rsdStore.entity }}-{{ rsdStore.mode }} Komponente wird noch implementiert.
              </p>
            </v-card-text>
          </v-card>
        </div>
      </div>
    </template>
  </v-navigation-drawer>
</template>

<script setup lang="ts">
import { computed, defineAsyncComponent } from 'vue'
import { useRSDStore } from '@/infrastructure/stores/rsdStore'
import { useLayoutStore } from '@/infrastructure/stores/layoutStore'

// Stores
const rsdStore = useRSDStore()
const layoutStore = useLayoutStore()

// Dynamic component mapping based on entity and mode
const componentMap = {
  // Admin domain
  'tenant': defineAsyncComponent(() => import('@/domains/admin/components/TenantRSD.vue')),
  'account': defineAsyncComponent(() => import('@/domains/admin/components/AccountRSD.vue')),
  'permission': defineAsyncComponent(() => import('@/domains/admin/components/PermissionRSD.vue')),
  'role': defineAsyncComponent(() => import('@/domains/admin/components/RoleRSD.vue')),
  
  // Identity domain
  'profile': defineAsyncComponent(() => import('@/domains/identity/components/ProfileRSD.vue')),

  // User management (separate components for each mode)
  'user-create': defineAsyncComponent(() => import('@/domains/tenant/components/UserCreateForm.vue')),
  'user-edit': defineAsyncComponent(() => import('@/domains/tenant/components/UserEditForm.vue')),
  'user-view': defineAsyncComponent(() => import('@/domains/tenant/components/UserViewDetails.vue')),

  // Partner management (separate components for each mode)
  'partner-create': defineAsyncComponent(() => import('@/domains/partner/components/PartnerCreateForm.vue')),
  'partner-edit': defineAsyncComponent(() => import('@/domains/partner/components/PartnerEditForm.vue')),
  'partner-view': defineAsyncComponent(() => import('@/domains/partner/components/PartnerViewDetails.vue')),
}

// Computed component
const currentComponent = computed(() => {
  if (!rsdStore.entity || !rsdStore.mode) return null
  
  // For user and partner entities, use mode-specific components
  if (rsdStore.entity === 'user' || rsdStore.entity === 'partner') {
    const componentKey = `${rsdStore.entity}-${rsdStore.mode}` as keyof typeof componentMap
    return componentMap[componentKey] || null
  }
  
  // For other entities, use entity-based components (legacy)
  const componentKey = rsdStore.entity as keyof typeof componentMap
  return componentMap[componentKey] || null
})

// Event handlers
const handleDrawerUpdate = (isOpen: boolean) => {
  if (!isOpen) {
    rsdStore.close()
  }
}

const handleSuccess = (message?: string, shouldClose = true) => {
  if (message) {
    layoutStore.showSuccess(message)
  }
  rsdStore.handleSuccess(message, shouldClose)
}

const handleError = (error: string) => {
  layoutStore.showError(error)
  rsdStore.handleError(error)
}

const handleEdit = () => {
  if (rsdStore.data && rsdStore.entity) {
    rsdStore.open(rsdStore.entity, 'edit', rsdStore.data)
  }
}
</script>

<style scoped>
.rsd-wrapper {
  z-index: 2000;
}

.rsd-header {
  position: sticky;
  top: 0;
  z-index: 1;
  background: rgb(var(--v-theme-surface));
  border-bottom: 1px solid rgb(var(--v-border-color));
}

.rsd-content {
  height: calc(100vh - 73px); /* Subtract header height */
  overflow-y: auto;
}

:deep(.v-navigation-drawer__content) {
  display: flex;
  flex-direction: column;
  height: 100%;
}
</style>