<template>
  <v-app>
    <v-navigation-drawer permanent app>
      <v-list-item
        title="cbApp Admin"
        subtitle="Administration Panel"
      />
      
      <v-divider />
      
      <v-list nav>
        <v-list-item
          prepend-icon="mdi-domain"
          title="Tenants"
          to="/admin/tenants"
        />
        <v-list-item
          prepend-icon="mdi-account-multiple"
          title="Accounts"
          to="/admin/accounts"
        />
      </v-list>
    </v-navigation-drawer>
    
    <v-app-bar app>
      <v-toolbar-title>Admin Panel</v-toolbar-title>
      <v-spacer />
      <v-btn icon @click="logout">
        <v-icon>mdi-logout</v-icon>
      </v-btn>
    </v-app-bar>
    
    <v-main>
      <v-container fluid>
        <router-view />
      </v-container>
    </v-main>
  </v-app>
</template>

<script setup lang="ts">
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../../infrastructure/stores/authStore'

const router = useRouter()
const authStore = useAuthStore()

const logout = async () => {
  await authStore.logout()
  router.push('/login')
}
</script>