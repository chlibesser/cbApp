<template>
  <v-container class="fill-height" fluid>
    <v-row justify="center" align="center">
      <v-col cols="12" md="6" lg="4">
        <v-card>
          <v-card-title class="text-center py-6">
            <!-- cbApp Logo -->
            <div class="mb-4">
              <img 
                src="/Logo_cbApp.svg" 
                alt="cbApp Logo" 
                style="height: 60px; width: auto;"
              />
            </div>
            <h2>Login</h2>
          </v-card-title>
          <v-card-text>
            <v-form @submit.prevent="handleLogin" v-model="isFormValid">
              <v-text-field
                v-model="credentials.identifier"
                label="Username or Email"
                type="text"
                :rules="identifierRules"
                required
                variant="outlined"
                class="mb-3"
                hint="You can login with your username or email address"
              />
              
              <v-text-field
                v-model="credentials.password"
                label="Password"
                type="password"
                :rules="passwordRules"
                required
                variant="outlined"
                class="mb-3"
              />
              
              <v-alert
                v-if="errorMessage"
                type="error"
                class="mb-3"
                dismissible
                @click:close="errorMessage = ''"
              >
                {{ errorMessage }}
              </v-alert>
              
              <v-btn
                type="submit"
                color="primary"
                block
                :loading="isLoading"
                :disabled="!isFormValid"
                size="large"
              >
                Login
              </v-btn>
            </v-form>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../../infrastructure/stores/authStore'
import type { LoginCredentials } from '../../../core/auth'

const router = useRouter()
const authStore = useAuthStore()

const credentials = ref<LoginCredentials>({
  identifier: '',
  password: ''
})

const isFormValid = ref(false)
const isLoading = ref(false)
const errorMessage = ref('')

const identifierRules = [
  (v: string) => !!v || 'Username or Email is required',
  (v: string) => v.length >= 3 || 'Must be at least 3 characters'
]

const passwordRules = [
  (v: string) => !!v || 'Password is required',
  (v: string) => v.length >= 6 || 'Password must be at least 6 characters'
]

const handleLogin = async () => {
  if (!isFormValid.value) return
  
  isLoading.value = true
  errorMessage.value = ''
  
  try {
    await authStore.login(credentials.value)
    router.push('/dashboard')
  } catch (error: any) {
    errorMessage.value = error.message || 'Login failed'
  } finally {
    isLoading.value = false
  }
}
</script>