<template>
  <div class="account-create">
    <v-form ref="form" @submit.prevent="handleSubmit">
      <v-card>
        <v-card-text>
          <v-row>
            <!-- Basic Information -->
            <v-col cols="12">
              <h3 class="text-h6 mb-4">
                <v-icon class="mr-2">mdi-information</v-icon>
                Neuen Account erstellen
              </h3>
              <p class="text-body-2 text-medium-emphasis mb-4">
                Erstellen Sie einen neuen Account im System. Der Account erhält standardmäßig die Mitglieder-Rolle.
              </p>
            </v-col>

            <v-col cols="12" md="6">
              <v-text-field
                v-model="formData.username"
                label="Benutzername"
                variant="outlined"
                :disabled="loading"
                hint="Eindeutiger Benutzername für die Anmeldung"
                persistent-hint
              />
            </v-col>

            <v-col cols="12" md="6">
              <v-text-field
                v-model="formData.email"
                label="E-Mail"
                type="email"
                variant="outlined"
                :disabled="loading"
                hint="E-Mail-Adresse für Benachrichtigungen"
                persistent-hint
              />
            </v-col>

            <v-col cols="12" md="6">
              <v-text-field
                v-model="formData.password"
                label="Passwort"
                type="password"
                variant="outlined"
                :disabled="loading"
                hint="Mindestens 8 Zeichen"
                persistent-hint
              />
            </v-col>

            <v-col cols="12" md="6">
              <v-text-field
                v-model="formData.password_confirmation"
                label="Passwort bestätigen"
                type="password"
                variant="outlined"
                :disabled="loading"
                hint="Passwort erneut eingeben"
                persistent-hint
              />
            </v-col>

            <!-- Account Settings -->
            <v-col cols="12">
              <h3 class="text-h6 mb-4 mt-4">
                <v-icon class="mr-2">mdi-cog</v-icon>
                Grundeinstellungen
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
                hint="Globale Berechtigungen im System"
                persistent-hint
              />
            </v-col>

            <v-col cols="12" md="6">
              <v-switch
                v-model="formData.is_active"
                label="Account sofort aktivieren"
                :disabled="loading"
                color="success"
                hide-details
              />
              <p class="text-caption text-medium-emphasis mt-1">
                Der Account kann sich sofort nach der Erstellung anmelden
              </p>
            </v-col>

            <!-- Preview Card -->
            <v-col cols="12">
              <v-card variant="outlined" class="mt-4">
                <v-card-title class="text-subtitle-1 bg-grey-lighten-5">
                  <v-icon class="mr-2">mdi-eye</v-icon>
                  Vorschau
                </v-card-title>
                <v-card-text>
                  <v-list lines="one" density="compact">
                    <v-list-item>
                      <v-list-item-title>Benutzername:</v-list-item-title>
                      <template #append>
                        <span class="font-weight-medium">{{ formData.username || '(Noch kein Benutzername)' }}</span>
                      </template>
                    </v-list-item>
                    
                    <v-list-item>
                      <v-list-item-title>E-Mail:</v-list-item-title>
                      <template #append>
                        <span class="font-weight-medium">{{ formData.email || '(Noch keine E-Mail)' }}</span>
                      </template>
                    </v-list-item>
                    
                    <v-list-item>
                      <v-list-item-title>System-Rolle:</v-list-item-title>
                      <template #append>
                        <v-chip size="small" :color="getSystemRoleColor(formData.system_role)">
                          {{ getSystemRoleLabel(formData.system_role) }}
                        </v-chip>
                      </template>
                    </v-list-item>
                    
                    <v-list-item>
                      <v-list-item-title>Status:</v-list-item-title>
                      <template #append>
                        <v-chip size="small" :color="formData.is_active ? 'success' : 'warning'">
                          {{ formData.is_active ? 'Aktiv' : 'Inaktiv' }}
                        </v-chip>
                      </template>
                    </v-list-item>
                  </v-list>
                </v-card-text>
              </v-card>
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
            :disabled="false"
          >
            <v-icon class="mr-1">mdi-plus</v-icon>
            Account erstellen
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-form>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import type { CreateAccountRequest } from '../types'
import { accountService } from '../services/accountService'

const emit = defineEmits<{
  success: [message: string]
  error: [message: string] 
  close: []
}>()

// Form state
const form = ref()
const loading = ref(false)
const error = ref<string | null>(null)

// Form data
const formData = ref<CreateAccountRequest>({
  username: '',
  email: '',
  password: '',
  password_confirmation: '',
  system_role: 'member',
  is_active: true
})

// Computed
const systemRoles = [
  { 
    value: 'member', 
    text: 'Mitglied',
    description: 'Standard-Benutzer ohne besondere Rechte'
  },
  { 
    value: 'tenant_admin', 
    text: 'Tenant Administrator',
    description: 'Kann eigene Tenants verwalten'
  },
  { 
    value: 'admin', 
    text: 'Administrator',
    description: 'Vollzugriff auf alle Funktionen'
  }
]


// Methods
const getSystemRoleLabel = (role: string | null) => {
  if (!role) return 'Keine Rolle'
  const roleItem = systemRoles.find(r => r.value === role)
  return roleItem?.text || role
}

const getSystemRoleColor = (role: string | null) => {
  if (!role) return 'grey'
  const colorMap: Record<string, string> = {
    'admin': 'error',
    'tenant_admin': 'warning',
    'member': 'info'
  }
  return colorMap[role] || 'grey'
}

const handleSubmit = async () => {

  loading.value = true
  error.value = null

  try {
    const response = await accountService.createAccount(formData.value)
    emit('success', response.message || 'Account erfolgreich erstellt')
  } catch (err: any) {
    error.value = err.response?.data?.message || err.message || 'Fehler beim Erstellen des Accounts'
    emit('error', error.value)
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.account-create {
  max-width: 100%;
}

.v-text-field,
.v-textarea,
.v-select {
  margin-bottom: 8px;
}

.v-list-item {
  min-height: 40px;
}
</style>