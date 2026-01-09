<template>
  <!-- Toast Container - Direct rendering without overlay -->
  <Teleport to="body">
    <div v-if="hasNotifications" class="toast-container">
      <transition-group
        name="toast"
        tag="div"
        class="toast-list"
      >
        <v-alert
          v-for="notification in layoutStore.notifications"
          :key="notification.id"
          :type="notification.type"
          :variant="alertVariant(notification.type)"
          :closable="!notification.persistent"
          class="toast-item mb-3"
          elevation="6"
          @click:close="layoutStore.removeNotification(notification.id)"
        >
          <!-- Icon -->
          <template #prepend>
            <v-icon :icon="getIcon(notification.type)" />
          </template>

          <!-- Message -->
          <div class="toast-content">
            <div class="toast-message">{{ notification.message }}</div>
            
            <!-- Actions -->
            <div v-if="notification.actions && notification.actions.length > 0" class="toast-actions mt-2">
              <v-btn
                v-for="(action, index) in notification.actions"
                :key="index"
                size="small"
                variant="text"
                :color="getActionColor(notification.type)"
                @click="handleActionClick(action, notification.id)"
                class="me-2"
              >
                {{ action.text }}
              </v-btn>
            </div>
          </div>

          <!-- Close button for persistent notifications -->
          <template v-if="notification.persistent" #append>
            <v-btn
              icon="mdi-close"
              size="x-small"
              variant="text"
              @click="layoutStore.removeNotification(notification.id)"
            />
          </template>
        </v-alert>
      </transition-group>

      <!-- Clear All Button (when multiple notifications) -->
      <v-btn
        v-if="layoutStore.notifications.length > 1"
        size="small"
        variant="outlined"
        color="surface-variant"
        @click="layoutStore.clearAllNotifications()"
        class="mt-2 clear-all-btn"
        block
      >
        <v-icon start>mdi-broom</v-icon>
        Alle schließen
      </v-btn>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useLayoutStore } from '../../infrastructure/stores/layoutStore'

const layoutStore = useLayoutStore()

const hasNotifications = computed(() => layoutStore.notifications.length > 0)

// Icon mapping for different notification types
const getIcon = (type: string): string => {
  const iconMap = {
    success: 'mdi-check-circle',
    error: 'mdi-alert-circle',
    warning: 'mdi-alert',
    info: 'mdi-information'
  }
  return iconMap[type as keyof typeof iconMap] || 'mdi-information'
}

// Alert variant for different types
const alertVariant = (type: string): string => {
  // Use 'tonal' variant for better visibility
  return 'tonal'
}

// Action button color based on notification type
const getActionColor = (type: string): string => {
  const colorMap = {
    success: 'success',
    error: 'error',
    warning: 'warning',
    info: 'info'
  }
  return colorMap[type as keyof typeof colorMap] || 'primary'
}

// Handle action button clicks
const handleActionClick = (action: any, notificationId: string) => {
  try {
    action.action()
  } catch (error) {
    console.error('Error executing notification action:', error)
  }
  
  // Remove notification after action
  layoutStore.removeNotification(notificationId)
}
</script>

<style scoped>
.toast-container {
  position: fixed;
  bottom: 24px;
  right: 24px;
  max-width: 400px;
  width: auto;
  min-width: 300px;
  z-index: 9999;
  pointer-events: none;
}

.toast-list {
  display: flex;
  flex-direction: column-reverse;
  gap: 12px;
  pointer-events: none;
}

.toast-item {
  pointer-events: auto;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
  backdrop-filter: blur(8px);
  border-radius: 8px !important;
}

.toast-content {
  flex: 1;
}

.toast-message {
  font-weight: 500;
  line-height: 1.4;
}

.toast-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.clear-all-btn {
  backdrop-filter: blur(8px);
  background-color: rgba(var(--v-theme-surface), 0.8) !important;
}

/* Mobile responsive */
@media (max-width: 600px) {
  .toast-container {
    bottom: 16px;
    right: 16px;
    left: 16px;
    width: auto;
    max-width: none;
    min-width: 0;
  }

  .toast-item {
    margin-bottom: 12px;
  }
}

/* Toast animations */
.toast-enter-active {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.toast-leave-active {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.toast-enter-from {
  opacity: 0;
  transform: translateY(100%);
}

.toast-leave-to {
  opacity: 0;
  transform: translateY(100%);
}

.toast-move {
  transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Dark mode adjustments */
.theme--dark .toast-item {
  background-color: rgba(var(--v-theme-surface), 0.95) !important;
}
</style>