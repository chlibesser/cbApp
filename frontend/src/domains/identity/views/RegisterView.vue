<template>
  <v-container class="fill-height" fluid>
    <v-row justify="center" align="center">
      <v-col cols="12" md="12" lg="12">
        <div class="text-center mb-4">
          <h1 class="text-h4 font-weight-light">
            <v-icon class="me-2">mdi-account-plus</v-icon>
            {{ t('auth.register.title') }}
          </h1>
        </div>
            <v-form @submit.prevent="handleRegister">
              <v-text-field
                v-model="formData.first_name"
                :label="t('auth.register.first_name')"
                variant="outlined"
                class="mb-3"
              />

              <v-text-field
                v-model="formData.last_name"
                :label="t('auth.register.last_name')"
                variant="outlined"
                class="mb-3"
              />

              <v-text-field
                v-model="formData.email"
                :label="t('auth.register.email')"
                type="email"
                variant="outlined"
                class="mb-3"
              />

              <v-text-field
                v-model="formData.username"
                :label="t('auth.register.username')"
                variant="outlined"
                class="mb-3"
                :hint="t('auth.register.username_hint')"
                persistent-hint
              />

              <v-text-field
                v-model="formData.company_name"
                :label="t('auth.register.company_name')"
                variant="outlined"
                class="mb-3"
                :hint="t('auth.register.company_name_hint')"
                persistent-hint
              />

              <v-text-field
                v-model="formData.password"
                :label="t('auth.register.password')"
                type="password"
                variant="outlined"
                class="mb-3"
              />

              <v-text-field
                v-model="formData.password_confirmation"
                :label="t('auth.register.password_confirmation')"
                type="password"
                variant="outlined"
                class="mb-3"
              />

              <v-checkbox
                v-model="formData.accept_terms"
              >
                <template #label>
                  <span class="text-body-2">
                    {{ t('auth.register.accept_terms') }}
                    <v-btn variant="text" size="small" class="pa-0" style="height: auto;">
                      {{ t('auth.register.terms_link') }}
                    </v-btn>
                  </span>
                </template>
              </v-checkbox>

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
                size="large"
                class="mb-3"
              >
                <v-icon start>mdi-account-plus</v-icon>
                {{ t('auth.register.register_button') }}
              </v-btn>

              <div class="text-center">
                <span class="text-body-2 text-medium-emphasis">
                  {{ t('auth.register.already_have_account') }}
                </span>
                <router-link to="/auth/login" class="text-primary text-decoration-none">
                  {{ t('auth.register.login_here') }}
                </router-link>
              </div>
            </v-form>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../../infrastructure/stores/authStore'
import { useTranslations } from '../../../core/localization/composables/useTranslations'

const router = useRouter()
const authStore = useAuthStore()
const { t } = useTranslations()

const formData = ref({
  first_name: '',
  last_name: '',
  email: '',
  username: '',
  company_name: '',
  password: '',
  password_confirmation: '',
  accept_terms: false,
})

const isLoading = ref(false)
const errorMessage = ref('')

const handleRegister = async () => {
  isLoading.value = true
  errorMessage.value = ''

  try {
    await authStore.register(formData.value)
    router.push('/dashboard')
  } catch (error: any) {
    errorMessage.value = error.message || t('auth.register.failed')
  } finally {
    isLoading.value = false
  }
}
</script>