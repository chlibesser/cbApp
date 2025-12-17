<template>
  <div class="partner-view-details">
    <!-- Header with Edit Action -->
    <div class="d-flex align-center justify-space-between mb-4">
      <div>
        <h3 class="text-h5 mb-1">{{ partner.name }}</h3>
        <div class="d-flex align-center gap-2">
          <v-chip
            v-if="partner.category"
            :color="partner.category.color || 'primary'"
            size="small"
            variant="tonal"
          >
            {{ partner.category.name }}
          </v-chip>
          <v-chip
            :color="getStatusColor(partner.status)"
            size="small"
            variant="flat"
          >
            {{ getStatusLabel(partner.status) }}
          </v-chip>
        </div>
      </div>
      <v-btn
        color="primary"
        variant="outlined"
        prepend-icon="mdi-pencil"
        @click="editPartner"
      >
        Bearbeiten
      </v-btn>
    </div>

    <!-- Partner Information -->
    <v-card class="mb-4">
      <v-card-title class="d-flex align-center">
        <v-icon class="mr-2">mdi-information</v-icon>
        Grunddaten
      </v-card-title>
      <v-card-text>
        <v-row dense>
          <v-col cols="12" md="6">
            <div class="mb-3">
              <div class="text-caption text-medium-emphasis mb-1">Name</div>
              <div>{{ partner.name }}</div>
            </div>
          </v-col>
          
          <v-col cols="12" md="6">
            <div class="mb-3">
              <div class="text-caption text-medium-emphasis mb-1">Kategorie</div>
              <div v-if="partner.category">
                <v-chip
                  :color="partner.category.color || 'primary'"
                  size="small"
                  variant="tonal"
                >
                  {{ partner.category.name }}
                </v-chip>
              </div>
              <div v-else class="text-medium-emphasis">—</div>
            </div>
          </v-col>

          <v-col cols="12" md="6">
            <div class="mb-3">
              <div class="text-caption text-medium-emphasis mb-1">E-Mail</div>
              <div v-if="partner.email">
                <a :href="`mailto:${partner.email}`" class="text-primary">
                  {{ partner.email }}
                </a>
              </div>
              <div v-else class="text-medium-emphasis">—</div>
            </div>
          </v-col>

          <v-col cols="12" md="6">
            <div class="mb-3">
              <div class="text-caption text-medium-emphasis mb-1">Telefon</div>
              <div v-if="partner.phone">
                <a :href="`tel:${partner.phone}`" class="text-primary">
                  {{ partner.phone }}
                </a>
              </div>
              <div v-else class="text-medium-emphasis">—</div>
            </div>
          </v-col>

          <v-col cols="12">
            <div class="mb-3">
              <div class="text-caption text-medium-emphasis mb-1">Website</div>
              <div v-if="partner.website">
                <a :href="partner.website" target="_blank" class="text-primary">
                  {{ partner.website }}
                  <v-icon size="small" class="ml-1">mdi-open-in-new</v-icon>
                </a>
              </div>
              <div v-else class="text-medium-emphasis">—</div>
            </div>
          </v-col>

          <v-col cols="12" md="6">
            <div class="mb-3">
              <div class="text-caption text-medium-emphasis mb-1">Status</div>
              <v-chip
                :color="getStatusColor(partner.status)"
                size="small"
                variant="flat"
              >
                {{ getStatusLabel(partner.status) }}
              </v-chip>
            </div>
          </v-col>

          <v-col cols="12" md="6">
            <div class="mb-3">
              <div class="text-caption text-medium-emphasis mb-1">Tags</div>
              <div v-if="partner.tags && partner.tags.length > 0" class="d-flex flex-wrap gap-1">
                <v-chip
                  v-for="tag in partner.tags"
                  :key="tag"
                  size="small"
                  variant="outlined"
                >
                  {{ tag }}
                </v-chip>
              </div>
              <div v-else class="text-medium-emphasis">—</div>
            </div>
          </v-col>

          <v-col v-if="partner.description" cols="12">
            <div class="mb-3">
              <div class="text-caption text-medium-emphasis mb-1">Beschreibung</div>
              <div class="white-space-pre-wrap">{{ partner.description }}</div>
            </div>
          </v-col>

          <v-col v-if="partner.notes" cols="12">
            <div class="mb-3">
              <div class="text-caption text-medium-emphasis mb-1">Notizen</div>
              <div class="white-space-pre-wrap">{{ partner.notes }}</div>
            </div>
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>

    <!-- Metadata -->
    <v-card>
      <v-card-title class="d-flex align-center">
        <v-icon class="mr-2">mdi-clock</v-icon>
        Metadaten
      </v-card-title>
      <v-card-text>
        <v-row dense>
          <v-col cols="12" md="6">
            <div class="mb-3">
              <div class="text-caption text-medium-emphasis mb-1">Erstellt am</div>
              <div>{{ formatDate(partner.created_at) }}</div>
            </div>
          </v-col>
          
          <v-col cols="12" md="6">
            <div class="mb-3">
              <div class="text-caption text-medium-emphasis mb-1">Zuletzt bearbeitet</div>
              <div>{{ formatDate(partner.updated_at) }}</div>
            </div>
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>

    <!-- Action Buttons -->
    <div class="d-flex justify-end gap-2 mt-4">
      <v-btn
        variant="outlined"
        prepend-icon="mdi-eye"
        @click="viewFullDetails"
      >
        Detail-Ansicht
      </v-btn>
      <v-btn
        color="primary"
        prepend-icon="mdi-pencil"
        @click="editPartner"
      >
        Bearbeiten
      </v-btn>
    </div>
  </div>
</template>

<script setup lang="ts">
import { toRefs } from 'vue'
import { useRouter } from 'vue-router'
import { useRSDStore } from '@/infrastructure/stores/rsdStore'
import type { Partner } from '../types'

interface Props {
  data: Partner
}

const props = defineProps<Props>()
const { data: partner } = toRefs(props)

// Composables
const router = useRouter()
const rsdStore = useRSDStore()

// Methods
function getStatusColor(status: string): string {
  switch (status) {
    case 'active': return 'success'
    case 'inactive': return 'error'
    case 'potential': return 'warning'
    default: return 'grey'
  }
}

function getStatusLabel(status: string): string {
  switch (status) {
    case 'active': return 'Aktiv'
    case 'inactive': return 'Inaktiv'
    case 'potential': return 'Potentiell'
    default: return status
  }
}

function formatDate(dateString: string): string {
  return new Date(dateString).toLocaleString('de-DE', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit'
  })
}

function editPartner() {
  rsdStore.openEdit('partner', partner.value)
}

function viewFullDetails() {
  rsdStore.close()
  router.push({ name: 'partner-detail', params: { id: partner.value.id } })
}
</script>

<style scoped>
.partner-view-details {
  padding: 0;
}

.white-space-pre-wrap {
  white-space: pre-wrap;
  word-break: break-word;
}
</style>