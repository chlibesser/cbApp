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
import { computed } from 'vue'
import { useRSDStore } from '@/infrastructure/stores/rsdStore'
import { useLayoutStore } from '@/infrastructure/stores/layoutStore'

// Stores
const rsdStore = useRSDStore()
const layoutStore = useLayoutStore()

// Dynamic component mapping (V0-style: single component per entity)
const componentMap = {
  // Admin domain
  'tenant': () => import('@/domains/admin/components/TenantRSD.vue'),
  'account': () => import('@/domains/admin/components/AccountRSD.vue'),
  'permission': () => import('@/domains/admin/components/PermissionRSD.vue'),
  'role': () => import('@/domains/admin/components/RoleRSD.vue'),
  
  // Identity domain
  'profile': () => import('@/domains/identity/components/ProfileRSD.vue'),
}

// Computed component
const currentComponent = computed(() => {
  if (!rsdStore.entity) return null
  
  const componentKey = rsdStore.entity as keyof typeof componentMap
  const componentLoader = componentMap[componentKey]
  
  if (componentLoader) {
    return componentLoader
  }
  
  return null
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