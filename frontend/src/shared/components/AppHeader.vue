<template>
  <v-app-bar app>
    <v-toolbar-title>
      <router-link to="/dashboard" class="text-decoration-none text-white">
        cbApp
      </router-link>
    </v-toolbar-title>
    
    <v-spacer />
    
    <v-menu>
      <template v-slot:activator="{ props }">
        <v-btn
          icon
          v-bind="props"
        >
          <v-avatar size="32">
            <v-icon>mdi-account</v-icon>
          </v-avatar>
        </v-btn>
      </template>
      
      <v-list>
        <v-list-item
          v-if="profile"
          :title="`${profile.first_name} ${profile.last_name}`"
          :subtitle="profile.email"
        />
        <v-divider />
        <v-list-item
          prepend-icon="mdi-account"
          title="Profile"
          @click="$router.push('/profile')"
        />
        <v-list-item
          v-if="isAdmin"
          prepend-icon="mdi-shield-crown"
          title="Admin"
          @click="$router.push('/admin')"
        />
        <v-list-item
          prepend-icon="mdi-logout"
          title="Logout"
          @click="handleLogout"
        />
      </v-list>
    </v-menu>
  </v-app-bar>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../infrastructure/stores/authStore'

const router = useRouter()
const authStore = useAuthStore()

const profile = computed(() => authStore.profile)
const isAdmin = computed(() => {
  // TODO: Implement proper role checking
  return true // Placeholder
})

const handleLogout = async () => {
  await authStore.logout()
  router.push('/login')
}
</script>