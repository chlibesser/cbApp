<template>
  <div class="snackbar-container">
    <v-snackbar
      v-for="notification in layoutStore.notifications"
      :key="notification.id"
      :model-value="true"
      :timeout="notification.persistent ? -1 : notification.timeout"
      :color="notification.type"
      location="bottom end"
      multi-line
      class="mb-2"
      @update:model-value="layoutStore.removeNotification(notification.id)"
    >
      <div class="d-flex align-center">
        <v-icon :icon="getIcon(notification.type)" class="mr-3" />
        <span>{{ notification.message }}</span>
      </div>

      <template #actions>
        <!-- Custom Actions -->
        <template v-if="notification.actions && notification.actions.length > 0">
          <v-btn
            v-for="(action, index) in notification.actions"
            :key="index"
            variant="text"
            size="small"
            @click="handleActionClick(action, notification.id)"
          >
            {{ action.text }}
          </v-btn>
        </template>

        <!-- Close Button -->
        <v-btn
          variant="text"
          size="small"
          icon="mdi-close"
          @click="layoutStore.removeNotification(notification.id)"
        />
      </template>
    </v-snackbar>
  </div>
</template>

<script setup lang="ts">
import { useLayoutStore } from '@/infrastructure/stores/layoutStore'

const layoutStore = useLayoutStore()

// Icon mapping for different notification types
const getIcon = (type: string): string => {
  const iconMap: Record<string, string> = {
    success: 'mdi-check-circle',
    error: 'mdi-alert-circle',
    warning: 'mdi-alert',
    info: 'mdi-information'
  }
  return iconMap[type] || 'mdi-information'
}

// Handle action button clicks
const handleActionClick = (action: { text: string; action: () => void }, notificationId: string) => {
  try {
    action.action()
  } catch (error) {
    console.error('Error executing notification action:', error)
  }
  layoutStore.removeNotification(notificationId)
}
</script>

<style scoped>
.snackbar-container {
  position: fixed;
  bottom: 0;
  right: 0;
  z-index: 9999;
  display: flex;
  flex-direction: column-reverse;
  gap: 8px;
  padding: 16px;
  pointer-events: none;
}

.snackbar-container :deep(.v-snackbar) {
  position: relative !important;
  pointer-events: auto;
}
</style>
