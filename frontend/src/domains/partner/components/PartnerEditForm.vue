<template>
  <div class="partner-edit-form">
    <v-form ref="formRef" v-model="isFormValid" @submit.prevent="handleSubmit">
      <v-row>
        <!-- Name (Required) -->
        <v-col cols="12">
          <v-text-field
            v-model="form.name"
            label="Name *"
            variant="outlined"
            density="compact"
            :rules="nameRules"
            required
          />
        </v-col>

        <!-- Kategorie (Required) -->
        <v-col cols="12">
          <v-select
            v-model="form.category_id"
            label="Kategorie *"
            variant="outlined"
            density="compact"
            :items="categoryOptions"
            item-title="name"
            item-value="id"
            :rules="categoryRules"
            :loading="categoriesLoading"
            required
          />
        </v-col>

        <!-- E-Mail -->
        <v-col cols="12" md="6">
          <v-text-field
            v-model="form.email"
            label="E-Mail"
            type="email"
            variant="outlined"
            density="compact"
            :rules="emailRules"
          />
        </v-col>

        <!-- Telefon -->
        <v-col cols="12" md="6">
          <v-text-field
            v-model="form.phone"
            label="Telefon"
            variant="outlined"
            density="compact"
          />
        </v-col>

        <!-- Website -->
        <v-col cols="12">
          <v-text-field
            v-model="form.website"
            label="Website"
            variant="outlined"
            density="compact"
            :rules="websiteRules"
          />
        </v-col>

        <!-- Status -->
        <v-col cols="12" md="6">
          <v-select
            v-model="form.status"
            label="Status"
            variant="outlined"
            density="compact"
            :items="statusOptions"
            item-title="label"
            item-value="value"
          />
        </v-col>

        <!-- Tags -->
        <v-col cols="12" md="6">
          <v-combobox
            v-model="form.tags"
            label="Tags"
            variant="outlined"
            density="compact"
            multiple
            chips
            closable-chips
            hint="Drücken Sie Enter um Tags hinzuzufügen"
            persistent-hint
          />
        </v-col>

        <!-- Beschreibung -->
        <v-col cols="12">
          <v-textarea
            v-model="form.description"
            label="Beschreibung"
            variant="outlined"
            density="compact"
            rows="3"
            auto-grow
          />
        </v-col>

        <!-- Notizen -->
        <v-col cols="12">
          <v-textarea
            v-model="form.notes"
            label="Notizen"
            variant="outlined"
            density="compact"
            rows="2"
            auto-grow
          />
        </v-col>
      </v-row>

      <!-- Submit Button -->
      <div class="d-flex justify-end gap-2 mt-4">
        <v-btn
          variant="outlined"
          @click="handleCancel"
        >
          Abbrechen
        </v-btn>
        <v-btn
          type="submit"
          color="primary"
          :loading="isSubmitting"
          :disabled="!isFormValid"
        >
          Partner speichern
        </v-btn>
      </div>
    </v-form>

    <!-- Snackbar für Feedback -->
    <v-snackbar
      v-model="snackbar"
      :color="snackbarColor"
      :timeout="4000"
      location="bottom right"
    >
      {{ snackbarText }}
      <template v-slot:actions>
        <v-btn
          variant="text"
          @click="snackbar = false"
        >
          Schließen
        </v-btn>
      </template>
    </v-snackbar>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, toRefs } from 'vue'
import { useRSDStore } from '@/infrastructure/stores/rsdStore'
import { apiClient } from '@/core/api/apiClient'
import { partnerService } from '../services/partnerService'
import type { Partner, PartnerForm } from '../types'

interface Props {
  data: Partner
}

const props = defineProps<Props>()
const { data: partner } = toRefs(props)

// Stores
const rsdStore = useRSDStore()

// Refs
const formRef = ref()
const isFormValid = ref(false)
const isSubmitting = ref(false)
const categoriesLoading = ref(false)
const categoryOptions = ref<Array<{id: string, name: string}>>([])

// Snackbar state
const snackbar = ref(false)
const snackbarText = ref('')
const snackbarColor = ref('success')

// Form data - Pre-fill with partner data
const form = reactive<PartnerForm>({
  name: partner.value.name || '',
  category_id: partner.value.category?.id || '',
  email: partner.value.email || '',
  phone: partner.value.phone || '',
  website: partner.value.website || '',
  description: partner.value.description || '',
  status: partner.value.status || 'active',
  tags: partner.value.tags || [],
  notes: partner.value.notes || ''
})

// Form options
const statusOptions = [
  { label: 'Aktiv', value: 'active' },
  { label: 'Inaktiv', value: 'inactive' },
  { label: 'Potentiell', value: 'potential' }
]

// Validation rules
const nameRules = [
  (v: string) => !!v || 'Name ist erforderlich',
  (v: string) => v.length >= 2 || 'Name muss mindestens 2 Zeichen lang sein'
]

const categoryRules = [
  (v: string) => !!v || 'Kategorie ist erforderlich'
]

const emailRules = [
  (v: string) => !v || /.+@.+\..+/.test(v) || 'E-Mail muss gültig sein'
]

const websiteRules = [
  (v: string) => !v || /^https?:\/\/.+/.test(v) || 'Website muss mit http:// oder https:// beginnen'
]

// Snackbar functions
function showSuccess(message: string) {
  snackbarText.value = message
  snackbarColor.value = 'success'
  snackbar.value = true
}

function showError(message: string) {
  snackbarText.value = message
  snackbarColor.value = 'error'
  snackbar.value = true
}

// Methods
async function loadCategories() {
  try {
    categoriesLoading.value = true
    // First get the partner_type category group
    const { data: groupsData } = await apiClient.get('/tenant/category-groups')
    const partnerTypeGroup = groupsData.data.find((group: any) => group.slug === 'partner_type')
    
    if (partnerTypeGroup) {
      // Load partner type categories
      const { data } = await apiClient.get(`/tenant/category-groups/${partnerTypeGroup.id}/categories`)
      categoryOptions.value = data.data || []
    }
  } catch (error) {
    console.error('Failed to load categories:', error)
    showError('Kategorien konnten nicht geladen werden')
  } finally {
    categoriesLoading.value = false
  }
}

async function handleSubmit() {
  if (!formRef.value || !isFormValid.value) return

  try {
    isSubmitting.value = true
    const updatedPartner = await partnerService.updatePartner(partner.value.id, form)
    
    showSuccess('Partner erfolgreich gespeichert')
    
    // Trigger table refresh
    window.dispatchEvent(new CustomEvent('rsd-success'))
    
    // Close RSD
    setTimeout(() => {
      rsdStore.close()
    }, 1000)
    
  } catch (error: any) {
    console.error('Failed to update partner:', error)
    showError(error.response?.data?.message || 'Fehler beim Speichern des Partners')
  } finally {
    isSubmitting.value = false
  }
}

function handleCancel() {
  rsdStore.close()
}

// Lifecycle
onMounted(() => {
  loadCategories()
})
</script>

<style scoped>
.partner-edit-form {
  padding: 0;
}
</style>