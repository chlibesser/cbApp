<template>
  <v-container class="fill-height" fluid>
    <v-row justify="center" align="center">
      <v-col cols="12" md="6" lg="5">
        <div class="text-center mb-4">
          <h1 class="text-h4 font-weight-light">
            <v-icon class="me-2">mdi-account-plus</v-icon>
            Registrierung
          </h1>
        </div>
            <v-form @submit.prevent="handleRegister">
              <v-text-field
                v-model="formData.first_name"
                label="Vorname"
                variant="outlined"
                class="mb-3"
              />

              <v-text-field
                v-model="formData.last_name"
                label="Nachname"
                variant="outlined"
                class="mb-3"
              />

              <v-text-field
                v-model="formData.email"
                label="E-Mail-Adresse"
                type="email"
                variant="outlined"
                class="mb-3"
              />

              <v-text-field
                v-model="formData.username"
                label="Benutzername (optional)"
                variant="outlined"
                class="mb-3"
                hint="Leer lassen für automatische Generierung"
                persistent-hint
              />

              <v-text-field
                v-model="formData.company_name"
                label="Firmenname (optional)"
                variant="outlined"
                class="mb-3"
                hint="Leer lassen für persönlichen Workspace"
                persistent-hint
              />

              <v-text-field
                v-model="formData.password"
                label="Passwort"
                type="password"
                variant="outlined"
                class="mb-3"
              />

              <v-text-field
                v-model="formData.password_confirmation"
                label="Passwort bestätigen"
                type="password"
                variant="outlined"
                class="mb-3"
              />

              <v-checkbox
                v-model="formData.accept_terms"
              >
                <template #label>
                  <span class="text-body-2">
                    Ich akzeptiere die 
                    <v-btn variant="text" size="small" class="pa-0" style="height: auto;">
                      Nutzungsbedingungen
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
                Registrieren
              </v-btn>

              <div class="text-center">
                <span class="text-body-2 text-medium-emphasis">
                  Bereits ein Konto? 
                </span>
                <router-link to="/auth/login" class="text-primary text-decoration-none">
                  Hier anmelden
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

const router = useRouter()
const authStore = useAuthStore()

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
    errorMessage.value = error.message || 'Registrierung fehlgeschlagen'
  } finally {
    isLoading.value = false
  }
}
</script>