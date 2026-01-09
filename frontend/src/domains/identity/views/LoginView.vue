<template>
  <v-container class="fill-height" fluid>
    <v-row justify="center" align="center">
      <v-col cols="12" md="12" lg="12">
        <v-form @submit.prevent="handleLogin" data-testid="login-form">
              <v-text-field
                v-model="credentials.identifier"
                :label="$t('auth.login.fields.identifier.label')"
                type="text"
                variant="outlined"
                class="mb-3"
                :hint="$t('auth.login.fields.identifier.hint')"
                data-testid="login-identifier"
              />

              <v-text-field
                v-model="credentials.password"
                :label="$t('auth.login.fields.password.label')"
                type="password"
                variant="outlined"
                class="mb-3"
                data-testid="login-password"
              />

              <v-alert
                v-if="errorMessage"
                type="error"
                class="mb-3"
                dismissible
                @click:close="errorMessage = ''"
                data-testid="error-message"
              >
                {{ errorMessage }}
              </v-alert>

              <v-btn
                type="submit"
                color="primary"
                block
                :loading="isLoading"
                :disabled="false"
                size="large"
                class="mb-3"
                data-testid="login-submit"
              >
                <v-progress-circular
                  v-if="isLoading"
                  indeterminate
                  size="20"
                  data-testid="login-loading"
                />
                
                {{ $t('auth.login.buttons.submit') }}

              </v-btn>

              <div class="text-center">
                <span class="text-body-2 text-medium-emphasis">
                  {{$t('auth.login.links.no_account')}} 
                </span>
                <router-link to="/auth/register" class="text-primary text-decoration-none">
                  {{$t('auth.login.links.register')}}
                </router-link>
              </div>
            </v-form>
      </v-col>
    </v-row>
    
    <!-- Quick Login Helper for Development -->
    <QuickLoginHelper />
  </v-container>
</template>

<script setup lang="ts">
  import { ref } from 'vue'
  import { useRouter } from 'vue-router'
  import { useAuthStore } from '../../../infrastructure/stores/authStore'
  import type { LoginCredentials } from '../../../core/auth'
  import QuickLoginHelper from '../../../shared/components/QuickLoginHelper.vue'

  const router = useRouter()
  const authStore = useAuthStore()

  const credentials = ref<LoginCredentials>({
    identifier: '',
    password: '',
  })

  const isLoading = ref(false)
  const errorMessage = ref('')


  const handleLogin = async () => {

    isLoading.value = true
    errorMessage.value = ''

    try {
      await authStore.login(credentials.value)
      router.push('/dashboard')
    } catch (error: any) {
      errorMessage.value = error.message || $t('auth.login.errors.general')
    } finally {
      isLoading.value = false
    }
  }
</script>
