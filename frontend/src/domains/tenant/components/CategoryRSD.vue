<template>
  <div class="category-rsd">
    <v-form ref="formRef" @submit.prevent="handleSubmit">
      <v-card flat>
        <v-card-text class="pa-6">
          <!-- Group Information (Read-only) -->
          <v-alert
            v-if="categoryGroup"
            type="info"
            variant="tonal"
            class="mb-4"
          >
            <div class="d-flex align-center">
              <v-icon 
                :icon="categoryGroup.icon || 'mdi-tag-multiple'" 
                :color="categoryGroup.color || 'primary'"
                class="mr-3"
              />
              <div>
                <div class="text-subtitle-1 font-weight-bold">{{ categoryGroup.name }}</div>
                <div class="text-caption">{{ categoryGroup.description || 'Keine Beschreibung' }}</div>
              </div>
            </div>
          </v-alert>

          <v-row>
            <v-col cols="12" md="8">
              <v-text-field
                v-model="formData.name"
                label="Kategorie-Name *"
                variant="outlined"
                placeholder="z.B. Rechnung"
                :disabled="isViewMode || loading"
              />
            </v-col>
            <v-col cols="12" md="4">
              <v-switch
                v-model="formData.is_default"
                label="Standard-Kategorie"
                color="warning"
                hide-details
                :disabled="isViewMode || loading"
              />
            </v-col>
          </v-row>

          <v-textarea
            v-model="formData.description"
            label="Beschreibung"
            variant="outlined"
            rows="2"
            placeholder="Kurze Beschreibung dieser Kategorie..."
            :disabled="isViewMode || loading"
          />

          <v-row>
            <v-col cols="12" md="6">
              <v-text-field
                v-model="formData.icon"
                label="Icon"
                variant="outlined"
                placeholder="mdi-file-document"
                prepend-inner-icon="mdi-palette"
                :disabled="isViewMode || loading"
              />
            </v-col>
            <v-col cols="12" md="6">
              <v-text-field
                v-model="formData.color"
                label="Farbe"
                variant="outlined"
                placeholder="primary"
                prepend-inner-icon="mdi-palette"
                :disabled="isViewMode || loading"
              />
            </v-col>
          </v-row>

          <!-- KI-Einstellungen (wenn Gruppe KI-aktiviert ist) -->
          <div v-if="categoryGroup?.ai_enabled" class="mt-4">
            <v-divider class="mb-4" />
            <h6 class="text-h6 mb-3">KI-Definitionen</h6>
            
            <v-textarea
              v-model="formData.ai_positive_description"
              label="Positive KI-Beschreibung *"
              variant="outlined"
              rows="3"
              placeholder="Beschreiben Sie, woran die KI diese Kategorie erkennen soll..."
              :disabled="isViewMode || loading"
            />

            <v-textarea
              v-model="formData.ai_negative_description"
              label="Negative KI-Beschreibung"
              variant="outlined"
              rows="2"
              placeholder="Beschreiben Sie, was NICHT zu dieser Kategorie gehört..."
              :disabled="isViewMode || loading"
            />

            <v-combobox
              v-model="formData.ai_keywords"
              label="KI-Keywords"
              multiple
              chips
              variant="outlined"
              placeholder="Schlüsselwörter für die KI-Erkennung..."
              :disabled="isViewMode || loading"
            />

            <v-textarea
              v-model="formData.ai_examples"
              label="Beispiel-Texte"
              variant="outlined"
              rows="3"
              placeholder="Typische Textbeispiele, die zu dieser Kategorie gehören..."
              :disabled="isViewMode || loading"
            />
          </div>

          <!-- Usage Statistics (View Mode) -->
          <div v-if="isViewMode && rsdStore.data" class="mt-4">
            <v-divider class="mb-4" />
            <h6 class="text-h6 mb-3">Nutzungsstatistiken</h6>
            
            <v-row>
              <v-col cols="6">
                <v-card variant="outlined" class="pa-3 text-center">
                  <div class="text-caption text-medium-emphasis">Verwendungen</div>
                  <div class="text-h6 font-weight-bold">{{ rsdStore.data.usage_count || 0 }}</div>
                </v-card>
              </v-col>
              <v-col cols="6">
                <v-card variant="outlined" class="pa-3 text-center">
                  <div class="text-caption text-medium-emphasis">Zuletzt verwendet</div>
                  <div class="text-caption">
                    {{ rsdStore.data.last_used_at ? formatDate(rsdStore.data.last_used_at) : 'Nie verwendet' }}
                  </div>
                </v-card>
              </v-col>
            </v-row>
          </div>
        </v-card-text>

        <!-- Actions -->
        <v-divider />
        <v-card-actions class="pa-4">
          <v-spacer />
          <v-btn
            variant="text"
            @click="rsdStore.close()"
            :disabled="loading"
          >
            {{ isViewMode ? 'Schließen' : 'Abbrechen' }}
          </v-btn>
          <v-btn
            v-if="!isViewMode"
            color="primary"
            type="submit"
            :disabled="false"
            :loading="loading"
          >
            {{ isEditMode ? 'Aktualisieren' : 'Erstellen' }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-form>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, watch } from 'vue'
import { categoryService, type Category, type CategoryGroup } from '../services/categoryService'
import { useRSDStore } from '../../../infrastructure/stores/rsdStore'

const rsdStore = useRSDStore()

// Form state
const formRef = ref()
const loading = ref(false)

// Mode computed properties
const isViewMode = computed(() => rsdStore.isViewMode)
const isEditMode = computed(() => rsdStore.isEditMode)
const isCreateMode = computed(() => rsdStore.isCreateMode)

// Category group (from context or data)
const categoryGroup = computed(() => {
  return rsdStore.data?.group || null
})

// Form data
const formData = reactive({
  name: '',
  description: '',
  icon: '',
  color: '',
  is_default: false,
  is_active: true,
  ai_positive_description: '',
  ai_negative_description: '',
  ai_keywords: [] as string[],
  ai_examples: ''
})


// Initialize form data
const initializeForm = () => {
  if (rsdStore.data) {
    Object.assign(formData, {
      name: rsdStore.data.name || '',
      description: rsdStore.data.description || '',
      icon: rsdStore.data.icon || '',
      color: rsdStore.data.color || '',
      is_default: rsdStore.data.is_default || false,
      is_active: rsdStore.data.is_active !== undefined ? rsdStore.data.is_active : true,
      ai_positive_description: rsdStore.data.ai_positive_description || '',
      ai_negative_description: rsdStore.data.ai_negative_description || '',
      ai_keywords: rsdStore.data.ai_keywords || [],
      ai_examples: rsdStore.data.ai_examples || ''
    })
  } else {
    // Reset to defaults for create mode
    Object.assign(formData, {
      name: '',
      description: '',
      icon: '',
      color: '',
      is_default: false,
      is_active: true,
      ai_positive_description: '',
      ai_negative_description: '',
      ai_keywords: [],
      ai_examples: ''
    })
  }
}

// Handle form submission
const handleSubmit = async () => {
  if (!categoryGroup.value) return

  try {
    loading.value = true
    
    let result: Category
    
    if (isEditMode.value && rsdStore.data) {
      // Update existing category
      result = await categoryService.categories.update(
        categoryGroup.value.id,
        rsdStore.data.id,
        formData
      )
      rsdStore.handleSuccess(`Kategorie "${result.name}" erfolgreich aktualisiert`)
    } else {
      // Create new category
      result = await categoryService.categories.create(categoryGroup.value.id, formData)
      rsdStore.handleSuccess(`Kategorie "${result.name}" erfolgreich erstellt`)
    }
  } catch (error) {
    console.error('Error saving category:', error)
    rsdStore.handleError(error instanceof Error ? error.message : 'Fehler beim Speichern der Kategorie')
  } finally {
    loading.value = false
  }
}

// Utility functions
const formatDate = (dateString: string) => {
  const date = new Date(dateString)
  return date.toLocaleDateString('de-DE', { 
    day: '2-digit', 
    month: '2-digit', 
    year: 'numeric' 
  })
}

// Watch for data changes (when switching between items in view mode)
watch(() => rsdStore.data, () => {
  initializeForm()
}, { deep: true, immediate: true })
</script>

<style scoped>
.category-rsd {
  height: 100%;
  display: flex;
  flex-direction: column;
}
</style>