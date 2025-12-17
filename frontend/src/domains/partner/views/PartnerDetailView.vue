<template>
  <div class="partner-detail-view" v-if="partner">
    <!-- Header -->
    <div class="d-flex align-center justify-space-between mb-4">
      <div class="d-flex align-center">
        <v-btn
          icon="mdi-arrow-left"
          variant="text"
          size="small"
          class="mr-3"
          @click="handleBack"
        />
        <div>
          <h1 class="text-h4">{{ partner.name }}</h1>
          <div class="d-flex align-center mt-2">
            <v-chip
              v-if="partner.category"
              :color="partner.category.color || 'primary'"
              size="small"
              variant="tonal"
              class="mr-2"
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
      </div>
      
      <div class="d-flex align-center gap-2">
        <v-btn
          color="primary"
          prepend-icon="mdi-pencil"
          @click="editPartner"
        >
          Bearbeiten
        </v-btn>
      </div>
    </div>

    <!-- Content Cards -->
    <v-row>
      <!-- Partner Information -->
      <v-col cols="12" md="6">
        <v-card>
          <v-card-title class="d-flex align-center">
            <v-icon class="mr-2">mdi-information</v-icon>
            Partner-Informationen
          </v-card-title>
          <v-card-text>
            <div class="info-grid">
              <div v-if="partner.description" class="mb-3">
                <strong>Beschreibung:</strong>
                <p class="mt-1">{{ partner.description }}</p>
              </div>
              
              <div v-if="partner.website" class="mb-3">
                <strong>Website:</strong>
                <div class="mt-1">
                  <a :href="partner.website" target="_blank" class="text-primary">
                    {{ partner.website }}
                    <v-icon size="small" class="ml-1">mdi-open-in-new</v-icon>
                  </a>
                </div>
              </div>
              
              <div v-if="partner.email" class="mb-3">
                <strong>E-Mail:</strong>
                <div class="mt-1">
                  <a :href="`mailto:${partner.email}`" class="text-primary">
                    {{ partner.email }}
                  </a>
                </div>
              </div>
              
              <div v-if="partner.phone" class="mb-3">
                <strong>Telefon:</strong>
                <div class="mt-1">
                  <a :href="`tel:${partner.phone}`" class="text-primary">
                    {{ partner.phone }}
                  </a>
                </div>
              </div>
              
              <div v-if="partner.tags && partner.tags.length > 0" class="mb-3">
                <strong>Tags:</strong>
                <div class="mt-2 d-flex flex-wrap gap-1">
                  <v-chip
                    v-for="tag in partner.tags"
                    :key="tag"
                    size="small"
                    variant="outlined"
                  >
                    {{ tag }}
                  </v-chip>
                </div>
              </div>
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- Contacts -->
      <v-col cols="12" md="6">
        <v-card>
          <v-card-title class="d-flex align-center justify-space-between">
            <div class="d-flex align-center">
              <v-icon class="mr-2">mdi-account-multiple</v-icon>
              Ansprechpartner
            </div>
            <v-btn
              size="small"
              prepend-icon="mdi-plus"
              variant="outlined"
              @click="addContact"
            >
              Kontakt hinzufügen
            </v-btn>
          </v-card-title>
          <v-card-text>
            <div v-if="partner.contacts && partner.contacts.length > 0">
              <div
                v-for="contact in partner.contacts"
                :key="contact.id"
                class="contact-item mb-3 pa-3"
                :class="{ 'primary-contact': contact.is_primary }"
              >
                <div class="d-flex align-center justify-space-between">
                  <div class="flex-grow-1">
                    <div class="d-flex align-center">
                      <strong class="mr-2">{{ contact.name }}</strong>
                      <v-chip
                        v-if="contact.is_primary"
                        size="x-small"
                        color="primary"
                        variant="flat"
                      >
                        Hauptkontakt
                      </v-chip>
                    </div>
                    <div v-if="contact.position" class="text-caption text-medium-emphasis">
                      {{ contact.position }}
                    </div>
                    <div v-if="contact.email" class="mt-1">
                      <v-icon size="small" class="mr-1">mdi-email</v-icon>
                      <a :href="`mailto:${contact.email}`" class="text-primary">
                        {{ contact.email }}
                      </a>
                    </div>
                    <div v-if="contact.phone" class="mt-1">
                      <v-icon size="small" class="mr-1">mdi-phone</v-icon>
                      {{ contact.phone }}
                    </div>
                  </div>
                  <v-menu>
                    <template #activator="{ props }">
                      <v-btn
                        icon="mdi-dots-vertical"
                        size="small"
                        variant="text"
                        v-bind="props"
                      />
                    </template>
                    <v-list>
                      <v-list-item @click="editContact(contact)">
                        <v-list-item-title>Bearbeiten</v-list-item-title>
                      </v-list-item>
                      <v-list-item @click="deleteContact(contact)">
                        <v-list-item-title>Löschen</v-list-item-title>
                      </v-list-item>
                    </v-list>
                  </v-menu>
                </div>
              </div>
            </div>
            <div v-else class="text-center text-medium-emphasis py-4">
              <v-icon size="large" class="mb-2">mdi-account-plus</v-icon>
              <div>Noch keine Ansprechpartner hinzugefügt</div>
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- Recent Interactions -->
      <v-col cols="12">
        <v-card>
          <v-card-title class="d-flex align-center justify-space-between">
            <div class="d-flex align-center">
              <v-icon class="mr-2">mdi-timeline</v-icon>
              Letzte Interaktionen
            </div>
            <v-btn
              size="small"
              prepend-icon="mdi-plus"
              variant="outlined"
              @click="addInteraction"
            >
              Interaktion hinzufügen
            </v-btn>
          </v-card-title>
          <v-card-text>
            <div v-if="partner.interactions && partner.interactions.length > 0">
              <div
                v-for="interaction in partner.interactions.slice(0, 10)"
                :key="interaction.id"
                class="interaction-item mb-3 pa-3"
              >
                <div class="d-flex align-center justify-space-between mb-2">
                  <div class="d-flex align-center">
                    <v-icon
                      :icon="getInteractionIcon(interaction.type)"
                      size="small"
                      class="mr-2"
                    />
                    <strong>{{ interaction.subject }}</strong>
                  </div>
                  <div class="text-caption text-medium-emphasis">
                    {{ formatDate(interaction.interaction_date) }}
                  </div>
                </div>
                <div v-if="interaction.content" class="text-body-2 mb-2">
                  {{ interaction.content }}
                </div>
                <div v-if="interaction.follow_up_date" class="text-caption">
                  <v-icon size="small" class="mr-1">mdi-calendar-clock</v-icon>
                  Follow-up: {{ formatDate(interaction.follow_up_date) }}
                </div>
              </div>
            </div>
            <div v-else class="text-center text-medium-emphasis py-4">
              <v-icon size="large" class="mb-2">mdi-timeline-plus</v-icon>
              <div>Noch keine Interaktionen erfasst</div>
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- Notes (if exists) -->
      <v-col v-if="partner.notes" cols="12">
        <v-card>
          <v-card-title class="d-flex align-center">
            <v-icon class="mr-2">mdi-note-text</v-icon>
            Notizen
          </v-card-title>
          <v-card-text>
            <div class="white-space-pre-wrap">{{ partner.notes }}</div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </div>

  <!-- Loading -->
  <div v-else-if="loading" class="text-center py-8">
    <v-progress-circular indeterminate />
    <div class="mt-3">Partner wird geladen...</div>
  </div>

  <!-- Error -->
  <div v-else class="text-center py-8">
    <v-icon size="large" color="error" class="mb-2">mdi-alert-circle</v-icon>
    <div class="text-h6">Partner nicht gefunden</div>
    <v-btn class="mt-3" @click="handleBack">Zurück zur Übersicht</v-btn>
  </div>

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
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useRSDStore } from '@/infrastructure/stores/rsdStore'
// Snackbar wird lokal verwaltet
import { partnerService } from '../services/partnerService'
import type { Partner, PartnerContact, PartnerInteraction } from '../types'

interface Props {
  id: string
}

const props = defineProps<Props>()
const router = useRouter()
const rsdStore = useRSDStore()
// State
const partner = ref<Partner | null>(null)
const loading = ref(true)

// Snackbar state
const snackbar = ref(false)
const snackbarText = ref('')
const snackbarColor = ref('success')

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

function getInteractionIcon(type: string): string {
  switch (type) {
    case 'meeting': return 'mdi-account-group'
    case 'call': return 'mdi-phone'
    case 'email': return 'mdi-email'
    case 'document': return 'mdi-file-document'
    default: return 'mdi-note-text'
  }
}

function formatDate(dateString: string): string {
  return new Date(dateString).toLocaleDateString('de-DE', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

function handleBack() {
  router.push({ name: 'partners' })
}

function editPartner() {
  if (partner.value) {
    rsdStore.openEdit('partner', partner.value)
  }
}

function addContact() {
  // TODO: Implement contact creation
  console.log('Add contact')
}

function editContact(contact: PartnerContact) {
  // TODO: Implement contact editing
  console.log('Edit contact:', contact)
}

function deleteContact(contact: PartnerContact) {
  // TODO: Implement contact deletion
  console.log('Delete contact:', contact)
}

function addInteraction() {
  // TODO: Implement interaction creation
  console.log('Add interaction')
}

async function loadPartner() {
  try {
    loading.value = true
    partner.value = await partnerService.getPartner(props.id)
  } catch (error: any) {
    console.error('Failed to load partner:', error)
    showError('Partner konnte nicht geladen werden')
  } finally {
    loading.value = false
  }
}

// Lifecycle
onMounted(() => {
  loadPartner()
})
</script>

<style scoped>
.partner-detail-view {
  padding: 24px;
}

.info-grid {
  line-height: 1.6;
}

.contact-item {
  border: 1px solid rgba(var(--v-border-color), 0.12);
  border-radius: 8px;
}

.primary-contact {
  background-color: rgba(var(--v-theme-primary), 0.04);
  border-color: rgba(var(--v-theme-primary), 0.2);
}

.interaction-item {
  border: 1px solid rgba(var(--v-border-color), 0.12);
  border-radius: 8px;
}

.white-space-pre-wrap {
  white-space: pre-wrap;
}
</style>