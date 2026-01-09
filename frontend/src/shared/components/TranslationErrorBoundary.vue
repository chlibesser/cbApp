<!--
  ===========================================================================
  TRANSLATION ERROR BOUNDARY COMPONENT
  ===========================================================================

  A wrapper component that handles translation loading errors gracefully.

  PURPOSE:
  - Show error alerts when translations fail to load
  - Never block child components from rendering
  - Provide retry mechanism
  - Show offline status
  - Auto-retry when connection restored

  USAGE:
  Wrap router-view or major sections:
  <TranslationErrorBoundary>
    <router-view />
  </TranslationErrorBoundary>
-->

<template>
  <div>
    <!-- Network Status Alert -->
    <v-alert
      v-if="!isOnline"
      type="info"
      variant="tonal"
      density="compact"
      class="mb-4"
    >
      <div class="d-flex align-center">
        <v-icon>mdi-wifi-off</v-icon>
        <span class="ml-2">You are offline. Using cached translations.</span>
      </div>
    </v-alert>

    <!-- Translation Error Alert -->
    <v-alert
      v-if="error && !initialized"
      type="warning"
      variant="tonal"
      closable
      class="mb-4"
      @click:close="error = null"
    >
      <!-- Alert Title -->
      <v-alert-title>
        <div class="d-flex align-center">
          <v-icon>mdi-translate-off</v-icon>
          <span class="ml-2">Translation Loading Error</span>
        </div>
      </v-alert-title>

      <!-- Alert Body -->
      <div>
        Failed to load translations from server.
        <span v-if="hasCachedData">Using cached version.</span>
        <span v-else>Some text may appear in English.</span>
      </div>

      <!-- Retry Button -->
      <template #append>
        <v-btn
          size="small"
          variant="text"
          @click="retry"
          :loading="retrying"
          :disabled="!isOnline"
        >
          <v-icon class="mr-1">mdi-refresh</v-icon>
          Retry
        </v-btn>
      </template>
    </v-alert>

    <!-- Loading State -->
    <v-progress-linear
      v-if="loading && initialized"
      indeterminate
      color="primary"
      class="mb-4"
    />
    <slot />
  </div>
</template>

<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import { useLocaleStore } from '@/core/localization/stores/localeStore'
import { useNetworkStatus } from '@/core/localization/composables/useNetworkStatus'

// Get locale store state
const localeStore = useLocaleStore()

// Get network status
const { isOnline, wasOffline } = useNetworkStatus()

// Local reactive state
const error = ref(localeStore.error)
const initialized = ref(localeStore.initialized)
const loading = ref(localeStore.loading)
const retrying = ref(false)

//Check if we have any cached data
const hasCachedData = computed(() => {
  const cached = localeStore.loadedNamespaces
  return cached.size > 0
})

// Watch store error state
watch(
  () => localeStore.error,
  newError => {
    error.value = newError
  }
)

// Watch store initialized state
watch(
  () => localeStore.initialized,
  newInitialized => {
    initialized.value = newInitialized
  }
)

// Watch store loading state
watch(
  () => localeStore.loading,
  newLoading => {
    loading.value = newLoading
  }
)

// Auto-retry when coming back online
watch(isOnline, online => {
  if (online && wasOffline.value && error.value) {
    console.log('[TranslationErrorBoundary] Connection restored - auto-retrying')
    retry()
  }
})

// Manual retry function
const retry = async () => {
  if (!isOnline.value) {
    console.log('[TranslationErrorBoundary] Cannot retry while offline')
    return
  }

  retrying.value = true
  error.value = null

  try {
    console.log('[TranslationErrorBoundary] Retrying translation loading...')
    await localeStore.initialize()
    console.log('[TranslationErrorBoundary] Retry successful')
  } catch (err) {
    console.error('[TranslationErrorBoundary] Retry failed:', err)
    error.value = 'Retry failed. Please check your connection.'
  } finally {
    retrying.value = false
  }
}
</script>

<style scoped>
.v-alert {
  border-radius: 4px;
}

.d-flex {
  display: flex;
}

.align-center {
  align-items: center;
}

.ml-1 {
  margin-left: 4px;
}

.ml-2 {
  margin-left: 8px;
}

.mr-1 {
  margin-right: 4px;
}

.mb-4 {
  margin-bottom: 16px;
}
</style>
