<template>
  <div class="account-edit">
    <v-form ref="form" v-model="formValid" @submit.prevent="handleSubmit">
      <v-card>
        <v-card-text>
          <v-row>
            <!-- Basic Information -->
            <v-col cols="12">
              <h3 class="text-h6 mb-4">
                <v-icon class="mr-2">mdi-information</v-icon>
                Allgemeine Informationen
              </h3>
            </v-col>

            <v-col cols="12" md="6">
              <v-text-field
                v-model="formData.username"
                label="Benutzername"
                :rules="usernameRules"
                variant="outlined"
                required
                :disabled="loading"
              />
            </v-col>

            <v-col cols="12" md="6">
              <v-text-field
                v-model="formData.email"
                label="E-Mail"
                type="email"
                :rules="emailRules"
                variant="outlined"
                required
                :disabled="loading"
              />
            </v-col>

            <!-- Account Settings -->
            <v-col cols="12">
              <h3 class="text-h6 mb-4 mt-4">
                <v-icon class="mr-2">mdi-cog</v-icon>
                Einstellungen
              </h3>
            </v-col>

            <v-col cols="12" md="6">
              <v-select
                v-model="formData.system_role"
                label="System-Rolle"
                :items="systemRoles"
                variant="outlined"
                :disabled="loading"
                item-title="text"
                item-value="value"
              />
            </v-col>

            <v-col cols="12" md="6">
              <v-switch
                v-model="formData.is_active"
                label="Account aktiv"
                :disabled="loading"
                color="success"
                hide-details
              />
              <p class="text-caption text-medium-emphasis mt-1">
                Inaktive Accounts können sich nicht anmelden
              </p>
            </v-col>

            <!-- Password Change -->
            <v-col cols="12">
              <v-expansion-panels v-model="passwordPanel">
                <v-expansion-panel>
                  <v-expansion-panel-title>
                    <v-icon class="mr-2">mdi-lock</v-icon>
                    Passwort ändern
                  </v-expansion-panel-title>
                  <v-expansion-panel-text>
                    <v-row>
                      <v-col cols="12" md="6">
                        <v-text-field
                          v-model="passwordData.password"
                          label="Neues Passwort"
                          type="password"
                          variant="outlined"
                          :disabled="loading"
                          :rules="changePassword ? passwordRules : []"
                        />
                      </v-col>

                      <v-col cols="12" md="6">
                        <v-text-field
                          v-model="passwordData.password_confirmation"
                          label="Passwort bestätigen"
                          type="password"
                          variant="outlined"
                          :disabled="loading"
                          :rules="changePassword ? confirmPasswordRules : []"
                        />
                      </v-col>
                    </v-row>
                  </v-expansion-panel-text>
                </v-expansion-panel>
              </v-expansion-panels>
            </v-col>

            <!-- Error Display -->
            <v-col v-if="error" cols="12">
              <v-alert type="error" dismissible @click:close="error = null">
                {{ error }}
              </v-alert>
            </v-col>
          </v-row>
        </v-card-text>

        <v-card-actions>
          <v-spacer />
          <v-btn
            variant="text"
            @click="$emit('close')"
            :disabled="loading"
          >
            Abbrechen
          </v-btn>
          <v-btn
            type="submit"
            color="primary"
            :loading="loading"
            :disabled="!formValid"
          >
            <v-icon class="mr-1">mdi-content-save</v-icon>
            Speichern
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-form>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import type { Account, UpdateAccountRequest } from '../types'
import { accountService } from '../services/accountService'

interface Props {
  data: Account | null
}

const props = defineProps<Props>()

const emit = defineEmits<{
  success: [message: string]
  error: [message: string]
  close: []
}>()

// Form state
const form = ref()
const formValid = ref(false)
const loading = ref(false)
const error = ref<string | null>(null)
const passwordPanel = ref()

// Form data
const formData = ref<UpdateAccountRequest>({
  username: '',
  email: '',
  system_role: null,
  is_active: true
})

const passwordData = ref({
  password: '',
  password_confirmation: ''
})

// Computed
const changePassword = computed(() => {
  return passwordPanel.value === 0 && (passwordData.value.password || passwordData.value.password_confirmation)
})

const systemRoles = [
  { value: null, text: 'Keine System-Rolle' },
  { value: 'admin', text: 'Administrator' },
  { value: 'tenant_admin', text: 'Tenant Administrator' },
  { value: 'member', text: 'Mitglied' }
]

// Validation rules
const usernameRules = [
  (v: string) => !!v || 'Benutzername ist erforderlich',
  (v: string) => v.length >= 3 || 'Benutzername muss mindestens 3 Zeichen haben'
]

const emailRules = [
  (v: string) => !!v || 'E-Mail ist erforderlich',
  (v: string) => /.+@.+\..+/.test(v) || 'E-Mail muss gültig sein'
]

const passwordRules = [
  (v: string) => !changePassword.value || !!v || 'Passwort ist erforderlich',
  (v: string) => !changePassword.value || v.length >= 8 || 'Passwort muss mindestens 8 Zeichen haben'
]

const confirmPasswordRules = [
  (v: string) => !changePassword.value || !!v || 'Passwortbestätigung ist erforderlich',
  (v: string) => !changePassword.value || v === passwordData.value.password || 'Passwörter stimmen nicht überein'
]

// Methods
const initializeForm = () => {
  if (props.data) {
    formData.value = {
      username: props.data.username,
      email: props.data.email,
      system_role: props.data.system_role,
      is_active: props.data.is_active
    }
  }
}

const handleSubmit = async () => {
  if (!form.value?.validate() || !props.data) return

  loading.value = true
  error.value = null

  try {
    const updateData: UpdateAccountRequest = { ...formData.value }
    
    // Add password if changed
    if (changePassword.value && passwordData.value.password) {
      updateData.password = passwordData.value.password
      updateData.password_confirmation = passwordData.value.password_confirmation
    }

    const response = await accountService.updateAccount(props.data.id, updateData)
    emit('success', response.message || 'Account erfolgreich aktualisiert')
  } catch (err: any) {
    error.value = err.response?.data?.message || err.message || 'Fehler beim Speichern'
    emit('error', error.value)
  } finally {
    loading.value = false
  }
}

// Initialize form data when component mounts
onMounted(() => {
  initializeForm()
})
</script>

<style scoped>
.account-edit {
  max-width: 100%;
}

.v-text-field,
.v-select {
  margin-bottom: 8px;
}

.v-expansion-panel-text :deep(.v-expansion-panel-text__wrapper) {
  padding-top: 16px;
}
</style>