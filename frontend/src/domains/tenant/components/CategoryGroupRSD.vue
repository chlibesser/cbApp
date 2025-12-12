<template>
  <div class="category-group-rsd">
    <!-- View Mode -->
    <template v-if="rsdStore.isView">
      <v-container>
        <v-row>
          <v-col cols="12">
            <div class="d-flex align-center mb-4">
              <v-icon 
                :icon="data.icon || 'mdi-tag-multiple'" 
                :color="data.color || 'primary'"
                size="large"
                class="mr-3"
              />
              <div>
                <h3 class="text-h5 mb-1">{{ data.name }}</h3>
                <p class="text-body-2 text-medium-emphasis mb-0">{{ data.description }}</p>
              </div>
            </div>
          </v-col>
          
          <v-col cols="12">
            <v-list density="compact">
              <v-list-item>
                <v-list-item-title>Auswahltyp</v-list-item-title>
                <template v-slot:append>
                  <v-chip size="small" :color="data.selection_type === 'single' ? 'info' : 'warning'">
                    {{ data.selection_type === 'single' ? 'Einfachauswahl' : 'Mehrfachauswahl' }}
                  </v-chip>
                </template>
              </v-list-item>
              
              <v-list-item>
                <v-list-item-title>Status</v-list-item-title>
                <template v-slot:append>
                  <v-chip size="small" :color="data.is_active ? 'success' : 'error'">
                    {{ data.is_active ? 'Aktiv' : 'Inaktiv' }}
                  </v-chip>
                </template>
              </v-list-item>
              
              <v-list-item v-if="data.is_required">
                <v-list-item-title>Pflichtfeld</v-list-item-title>
                <template v-slot:append>
                  <v-icon color="warning">mdi-alert-circle</v-icon>
                </template>
              </v-list-item>
              
              <v-list-item>
                <v-list-item-title>KI-Kategorisierung</v-list-item-title>
                <template v-slot:append>
                  <v-chip size="small" :color="data.ai_enabled ? 'success' : 'grey'">
                    {{ data.ai_enabled ? 'Aktiviert' : 'Deaktiviert' }}
                  </v-chip>
                </template>
              </v-list-item>
              
              <v-list-item v-if="data.ai_enabled">
                <v-list-item-title>KI-Vertrauensschwelle</v-list-item-title>
                <template v-slot:append>
                  {{ Math.round((data.ai_confidence_threshold || 0.7) * 100) }}%
                </template>
              </v-list-item>
            </v-list>
          </v-col>
          
          <v-col cols="12" v-if="data.ai_enabled && data.ai_prompt_context">
            <v-card variant="tonal" color="info">
              <v-card-text>
                <div class="text-caption font-weight-medium mb-1">KI-Kontext</div>
                <div class="text-body-2">{{ data.ai_prompt_context }}</div>
              </v-card-text>
            </v-card>
          </v-col>
          
          <v-col cols="12" v-if="data.categories && data.categories.length > 0">
            <h4 class="text-h6 mb-3">Kategorien ({{ data.categories.length }})</h4>
            <v-chip-group>
              <v-chip
                v-for="category in data.categories"
                :key="category.id"
                :color="category.color || 'grey'"
                variant="outlined"
              >
                <v-icon start size="small">{{ category.icon || 'mdi-tag' }}</v-icon>
                {{ category.name }}
              </v-chip>
            </v-chip-group>
          </v-col>
        </v-row>
      </v-container>
    </template>
    
    <!-- Edit/Create Mode -->
    <template v-else>
      <CategoryGroupForm
        :model-value="data"
        :loading="loading"
        @submit="handleSubmit"
        @cancel="rsdStore.close"
      />
    </template>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRSDStore } from '../../../infrastructure/stores/rsdStore'
import { categoryService } from '../services/categoryService'
import { useToast } from '../../../shared/composables/useToast'
import CategoryGroupForm from './CategoryGroupForm.vue'

const rsdStore = useRSDStore()
const { showToast } = useToast()

const loading = ref(false)

const data = computed(() => rsdStore.data || {})

const handleSubmit = async (formData: any) => {
  loading.value = true
  
  try {
    if (rsdStore.isEdit) {
      await categoryService.groups.update(data.value.id, formData)
      showToast({
        message: 'Kategorie-Gruppe erfolgreich aktualisiert',
        type: 'success'
      })
    } else {
      await categoryService.groups.create(formData)
      showToast({
        message: 'Kategorie-Gruppe erfolgreich erstellt',
        type: 'success'
      })
    }
    
    rsdStore.close(true)
  } catch (error) {
    console.error('Error saving category group:', error)
    showToast({
      message: error instanceof Error ? error.message : 'Fehler beim Speichern',
      type: 'error'
    })
  } finally {
    loading.value = false
  }
}
</script>