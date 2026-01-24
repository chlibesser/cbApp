<template>
  <v-app>
    <v-main>
      <router-view />
    </v-main>
  </v-app>
</template>

<script setup lang="ts">
import { onMounted } from 'vue'
import { useAuthStore } from './infrastructure/stores/authStore'

const authStore = useAuthStore()

onMounted(async () => {
  // Initialize auth state similar to V0 approach
  // This loads user data after the component is mounted and navigation is stable
  if (authStore.isAuthenticated && !authStore.account) {
    try {
      await authStore.loadUserData()
    } catch (error) {
      console.warn('Failed to load user data on app mount:', error)
    }
  }
})
</script>

<style>
/* Global Layout Fixes - Fixed Header System */
html, body, #app {
  height: 100vh;
  overflow: hidden; /* Remove global scrollbar */
}

/* Ensure v-app takes full height */
.v-application {
  height: 100vh !important;
}

/* Tonal Chips mit farbigem Rand */
.v-chip.v-chip--variant-tonal {
  border: 1px solid currentColor !important;
}
</style>
