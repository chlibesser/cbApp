<template>
  <v-app>
    <AppHeader />

    <v-main>
      <v-container fluid class="pa-8">
        <v-row>
          <v-col cols="12">
            <h1 class="mb-6">Dashboard</h1>

            <v-row>
              <v-col cols="12" md="6" lg="3">
                <v-card>
                  <v-card-title class="d-flex align-center">
                    <v-icon class="mr-2" color="primary">mdi-account</v-icon>
                    Profile
                  </v-card-title>
                  <v-card-text>
                    <div v-if="profile">
                      <p><strong>Name:</strong> {{ profile.first_name }} {{ profile.last_name }}</p>
                      <p><strong>Email:</strong> {{ profile.email }}</p>
                      <p><strong>Phone:</strong> {{ profile.phone || 'Not set' }}</p>
                    </div>
                    <v-skeleton-loader v-else type="text@3" />
                  </v-card-text>
                  <v-card-actions>
                    <v-btn color="primary" variant="text" @click="$router.push('/profile')">
                      Edit Profile
                    </v-btn>
                  </v-card-actions>
                </v-card>
              </v-col>

              <v-col cols="12" md="6" lg="3">
                <v-card>
                  <v-card-title class="d-flex align-center">
                    <v-icon class="mr-2" color="success">mdi-domain</v-icon>
                    Organization
                  </v-card-title>
                  <v-card-text>
                    <div v-if="tenant">
                      <p><strong>Name:</strong> {{ tenant.name }}</p>
                      <p><strong>Subdomain:</strong> {{ tenant.subdomain }}</p>
                      <p>
                        <strong>Status:</strong>
                        <v-chip :color="tenant.is_active ? 'success' : 'error'" small>
                          {{ tenant.is_active ? 'Active' : 'Inactive' }}
                        </v-chip>
                      </p>
                    </div>
                    <v-skeleton-loader v-else type="text@3" />
                  </v-card-text>
                </v-card>
              </v-col>

              <v-col cols="12" md="6" lg="3">
                <v-card>
                  <v-card-title class="d-flex align-center">
                    <v-icon class="mr-2" color="warning">mdi-cog</v-icon>
                    Quick Actions
                  </v-card-title>
                  <v-card-text>
                    <v-list density="compact">
                      <v-list-item
                        prepend-icon="mdi-account-edit"
                        title="Update Profile"
                        @click="$router.push('/profile')"
                      />
                      <v-list-item
                        v-if="isAdmin"
                        prepend-icon="mdi-shield-crown"
                        title="Admin Panel"
                        @click="$router.push('/admin')"
                      />
                    </v-list>
                  </v-card-text>
                </v-card>
              </v-col>

              <v-col cols="12" md="6" lg="3">
                <v-card>
                  <v-card-title class="d-flex align-center">
                    <v-icon class="mr-2" color="info">mdi-information</v-icon>
                    System Info
                  </v-card-title>
                  <v-card-text>
                    <p><strong>Version:</strong> cbApp V1</p>
                    <p><strong>Environment:</strong> {{ environment }}</p>
                    <p><strong>Last Login:</strong> {{ lastLogin }}</p>
                  </v-card-text>
                </v-card>
              </v-col>
            </v-row>
          </v-col>
        </v-row>
      </v-container>
    </v-main>
  </v-app>
</template>

<script setup lang="ts">
  import { computed, onMounted } from 'vue'
  import AppHeader from '../components/AppHeader.vue'
  import { useAuthStore } from '../../infrastructure/stores/authStore'

  const authStore = useAuthStore()

  const profile = computed(() => authStore.profile)
  const account = computed(() => authStore.account)
  const tenant = computed(() => {
    // TODO: Get tenant info from store
    return {
      name: 'Demo Tenant',
      subdomain: 'demo',
      is_active: true,
    }
  })

  const isAdmin = computed(() => {
    // TODO: Implement proper role checking
    return true // Placeholder
  })

  const environment = computed(() => import.meta.env.MODE)
  const lastLogin = computed(() => {
    if (account.value?.created_at) {
      return new Date(account.value.created_at).toLocaleDateString()
    }
    return 'Unknown'
  })

  onMounted(async () => {
    // Load user data if not already loaded
    if (!authStore.isAuthenticated) {
      await authStore.loadUserData()
    }
  })
</script>
