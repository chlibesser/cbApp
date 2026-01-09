<template>
  <v-app>
    <v-navigation-drawer permanent app>
      <v-list-item :title="$t('admin.layout.title')" :subtitle="$t('admin.layout.subtitle')" />

      <v-divider />

      <v-list nav>
        <v-list-item prepend-icon="mdi-domain" :title="$t('admin.layout.nav.tenants')" to="/admin/tenants" />
        <v-list-item prepend-icon="mdi-account-multiple" :title="$t('admin.layout.nav.accounts')" to="/admin/accounts" />
      </v-list>
    </v-navigation-drawer>

    <v-app-bar app>
      <v-toolbar-title>{{ $t('admin.layout.toolbar_title') }}</v-toolbar-title>
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
