<template>
  <v-card v-if="isDevelopment" class="ma-4" variant="outlined">
    <v-card-title class="text-h6 d-flex align-center">
      <v-icon class="me-2">mdi-account-multiple</v-icon>
      Quick Login (Development)
    </v-card-title>
    
    <v-card-text>
      <v-row>
        <v-col
          v-for="account in quickLogins"
          :key="account.username"
          cols="12"
          sm="6"
          md="4"
        >
          <v-card variant="outlined" class="mb-2">
            <v-card-title class="text-subtitle-1">{{ account.username }}</v-card-title>
            <v-card-subtitle class="text-caption">{{ account.email }}</v-card-subtitle>
            
            <v-card-text v-if="account.tenants.length > 0">
              <div class="text-caption mb-2">Available Tenants:</div>
              <v-chip-group>
                <v-chip
                  v-for="tenant in account.tenants"
                  :key="tenant.id"
                  size="small"
                  variant="outlined"
                  @click="handleQuickLogin(account.username, tenant.id)"
                  :loading="loading"
                >
                  <v-icon start size="small" :color="tenant.is_personal ? 'purple' : 'blue'">
                    {{ tenant.is_personal ? 'mdi-account' : 'mdi-office-building' }}
                  </v-icon>
                  {{ tenant.name }}
                  <v-tooltip activator="parent" location="bottom">
                    <div>
                      <strong>Role:</strong> {{ tenant.role?.name || 'No role' }}<br>
                      <strong>Type:</strong> {{ tenant.is_personal ? 'Personal' : 'Company' }}
                    </div>
                  </v-tooltip>
                </v-chip>
              </v-chip-group>
            </v-card-text>
            
            <v-card-actions v-else>
              <v-btn
                size="small"
                variant="outlined"
                @click="handleQuickLogin(account.username)"
                :loading="loading"
              >
                Login (No Tenant)
              </v-btn>
            </v-card-actions>
          </v-card>
        </v-col>
      </v-row>
      
      <v-alert v-if="error" type="error" class="mt-3">
        {{ error }}
      </v-alert>
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useAuthStore } from '../../infrastructure/stores/authStore'
import { useRouter } from 'vue-router'

const authStore = useAuthStore()
const router = useRouter()

const loading = ref(false)
const error = ref('')

const isDevelopment = computed(() => import.meta.env.DEV)
const quickLogins = computed(() => authStore.availableQuickLogins)

const handleQuickLogin = async (username: string, tenantId?: number) => {
  try {
    loading.value = true
    error.value = ''
    
    await authStore.quickLogin(username, tenantId)
    
    // Redirect to dashboard or appropriate page
    router.push('/')
  } catch (err: any) {
    error.value = err.response?.data?.message || err.message || 'Quick login failed'
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  if (isDevelopment.value) {
    await authStore.loadQuickLogins()
  }
})
</script>