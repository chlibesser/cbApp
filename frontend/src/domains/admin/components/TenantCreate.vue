<template>
  <div class="tenant-create">
    <v-form ref="form" v-model="formValid" @submit.prevent="handleSubmit">
      <v-card>
        <v-card-text>
          <v-row>
            <!-- Basic Information -->
            <v-col cols="12">
              <h3 class="text-h6 mb-4">
                <v-icon class="mr-2">mdi-information</v-icon>
                Neuen Tenant erstellen
              </h3>
              <p class="text-body-2 text-medium-emphasis mb-4">
                Erstellen Sie einen neuen Tenant für Ihr System. Der Slug wird automatisch generiert, kann aber angepasst werden.
              </p>
            </v-col>

            <v-col cols="12" md="6">
              <v-text-field
                v-model="formData.name"
                label="Tenant-Name"
                :rules="nameRules"
                variant="outlined"
                required
                :disabled="loading"
                @input="generateSlug"
                hint="Der Name des Tenants, z.B. 'Meine Firma GmbH'"
                persistent-hint
              />
            </v-col>

            <v-col cols="12" md="6">
              <v-text-field
                v-model="formData.slug"
                label="Slug (URL-Bezeichnung)"
                :rules="slugRules"
                variant="outlined"
                :disabled="loading"
                hint="URL-freundlicher Name, z.B. 'meine-firma'"
                persistent-hint
              />
            </v-col>

            <v-col cols="12">
              <v-textarea
                v-model="formData.description"
                label="Beschreibung (optional)"
                variant="outlined"
                :disabled="loading"
                rows="3"
                counter="500"
                :rules="descriptionRules"
                hint="Kurze Beschreibung des Tenants"
                persistent-hint
              />
            </v-col>

            <!-- Tenant Settings -->
            <v-col cols="12">
              <h3 class="text-h6 mb-4 mt-4">
                <v-icon class="mr-2">mdi-cog</v-icon>
                Grundeinstellungen
              </h3>
            </v-col>

            <v-col cols="12" md="6">
              <v-select
                v-model="formData.is_personal"
                label="Tenant-Typ"
                :items="tenantTypes"
                variant="outlined"
                :disabled="loading"
                item-title="text"
                item-value="value"
                hint="Art des Tenants - beeinflusst verfügbare Features"
                persistent-hint
              />
            </v-col>

            <v-col cols="12" md="6">
              <v-switch
                v-model="formData.is_active"
                label="Sofort aktivieren"
                :disabled="loading"
                color="success"
                hide-details
              />
              <p class="text-caption text-medium-emphasis mt-1">
                Der Tenant kann sich sofort nach der Erstellung anmelden
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
                      <v-list-item-title>Name:</v-list-item-title>
                      <template #append>
                        <span class="font-weight-medium">{{ formData.name || '(Noch kein Name)' }}</span>
                      </template>
                    </v-list-item>
                    
                    <v-list-item>
                      <v-list-item-title>Slug:</v-list-item-title>
                      <template #append>
                        <span class="font-weight-medium">{{ formData.slug || '(Wird automatisch generiert)' }}</span>
                      </template>
                    </v-list-item>
                    
                    <v-list-item>
                      <v-list-item-title>Typ:</v-list-item-title>
                      <template #append>
                        <v-chip size="small" :color="formData.is_personal ? 'info' : 'primary'">
                          {{ formData.is_personal ? 'Persönlich' : 'Unternehmen' }}
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
            :disabled="!formValid"
          >
            <v-icon class="mr-1">mdi-plus</v-icon>
            Tenant erstellen
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-form>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import type { CreateTenantRequest } from '../types'
import { tenantService } from '../services/tenantService'

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

// Form data
const formData = ref<CreateTenantRequest>({
  name: '',
  slug: '',
  description: '',
  is_personal: false,
  is_active: true,
  settings: {}
})

// Computed
const tenantTypes = [
  { 
    value: false, 
    text: 'Unternehmen',
    description: 'Für Firmen und Organisationen'
  },
  { 
    value: true, 
    text: 'Persönlich',
    description: 'Für Einzelpersonen'
  }
]

// Validation rules
const nameRules = [
  (v: string) => !!v || 'Name ist erforderlich',
  (v: string) => v.length <= 255 || 'Name darf maximal 255 Zeichen haben',
  (v: string) => v.length >= 2 || 'Name muss mindestens 2 Zeichen haben'
]

const slugRules = [
  (v: string) => !v || v.length <= 100 || 'Slug darf maximal 100 Zeichen haben',
  (v: string) => !v || /^[a-z0-9-]+$/.test(v) || 'Slug darf nur Kleinbuchstaben, Zahlen und Bindestriche enthalten',
  (v: string) => !v || !v.startsWith('-') || 'Slug darf nicht mit einem Bindestrich beginnen',
  (v: string) => !v || !v.endsWith('-') || 'Slug darf nicht mit einem Bindestrich enden'
]

const descriptionRules = [
  (v: string) => !v || v.length <= 500 || 'Beschreibung darf maximal 500 Zeichen haben'
]

// Methods
const generateSlug = () => {
  if (formData.value.name && !formData.value.slug) {
    // Auto-generate slug from name
    let slug = formData.value.name
      .toLowerCase()
      .replace(/[^a-z0-9\s-]/g, '') // Remove special characters
      .replace(/\s+/g, '-') // Replace spaces with hyphens
      .replace(/-+/g, '-') // Replace multiple hyphens with single
      .trim()
    
    // Remove leading/trailing hyphens
    slug = slug.replace(/^-+|-+$/g, '')
    
    formData.value.slug = slug
  }
}

const handleSubmit = async () => {
  if (!form.value?.validate()) return

  loading.value = true
  error.value = null

  try {
    const response = await tenantService.createTenant(formData.value)
    emit('success', response.message)
  } catch (err: any) {
    error.value = err.response?.data?.message || err.message || 'Fehler beim Erstellen des Tenants'
    emit('error', error.value)
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.tenant-create {
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