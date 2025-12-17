<template>
  <div class="partner-list-view">
    <!-- Header -->
    <div class="mb-4">
      <h1 class="text-h4 mb-2">Partner-Verwaltung</h1>
      <p class="text-subtitle-1 text-medium-emphasis">
        Verwalten Sie Ihre Partner, Lieferanten, Kunden und Dienstleister
      </p>
      <v-chip 
        class="mr-2"
        size="small"
        variant="tonal"
      >
        {{ statistics.total }} Partner insgesamt
      </v-chip>
      <v-chip 
        color="success"
        size="small"
        variant="tonal"
      >
        {{ statistics.active }} aktiv
      </v-chip>
    </div>

    <!-- Data Table -->
    <v-card rounded="lg">
      <AdvancedDataTable
        :columns="tableColumns"
        :api-endpoint="`/tenant/partners`"
        :enable-create="true"
        create-button-text="Partner erstellen"
        @create="handleCreate"
        @itemSelected="handleItemSelected"
      >
        <!-- Status slot -->
        <template #item.status="{ item, value }">
          <v-chip
            :color="getStatusColor(value)"
            size="small"
            variant="flat"
          >
            {{ getStatusLabel(value) }}
          </v-chip>
        </template>

        <!-- Category slot -->
        <template #item.category="{ item, value }">
          <v-chip
            v-if="value"
            :color="value.color || 'primary'"
            size="small"
            variant="tonal"
          >
            {{ value.name }}
          </v-chip>
          <span v-else class="text-medium-emphasis">—</span>
        </template>

        <!-- Tags slot -->
        <template #item.tags="{ item, value }">
          <div v-if="value && value.length > 0" class="d-flex flex-wrap gap-1">
            <v-chip
              v-for="tag in value.slice(0, 3)"
              :key="tag"
              size="x-small"
              variant="outlined"
            >
              {{ tag }}
            </v-chip>
            <v-chip
              v-if="value.length > 3"
              size="x-small"
              variant="outlined"
              color="grey"
            >
              +{{ value.length - 3 }}
            </v-chip>
          </div>
          <span v-else class="text-medium-emphasis">—</span>
        </template>

        <!-- Actions slot -->
        <template #item.actions="{ item }">
          <v-btn
            icon="mdi-eye"
            size="small"
            variant="text"
            @click.stop="viewPartner(item)"
          />
          <v-btn
            icon="mdi-pencil"
            size="small"
            variant="text"
            @click.stop="editPartner(item)"
          />
          <v-btn
            v-if="canDelete"
            icon="mdi-delete"
            size="small"
            variant="text"
            color="error"
            @click.stop="deletePartner(item)"
          />
        </template>
      </AdvancedDataTable>
    </v-card>

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
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/infrastructure/stores/authStore'
import { useRSDStore } from '@/infrastructure/stores/rsdStore'
// Snackbar wird lokal verwaltet
import { partnerService } from '../services/partnerService'
import type { Partner, PartnerStatus } from '../types'
// ADT hat eigene TableColumn Definition
import AdvancedDataTable from '@/shared/components/tables/AdvancedDataTable.vue'

const router = useRouter()
const authStore = useAuthStore()
const rsdStore = useRSDStore()
// State
const statistics = ref({
  total: 0,
  active: 0
})

// Snackbar state
const snackbar = ref(false)
const snackbarText = ref('')
const snackbarColor = ref('success')

// Computed
const canDelete = computed(() => {
  return authStore.isAdmin || authStore.account?.system_role === 'global_admin'
})

// Table columns configuration für ADT
const tableColumns = [
  {
    key: 'name',
    label: 'Name',
    sortable: true,
    type: 'text',
    width: '250px'
  },
  {
    key: 'category',
    label: 'Kategorie',
    sortable: false,
    width: '150px'
  },
  {
    key: 'email',
    label: 'E-Mail',
    sortable: true,
    type: 'email',
    width: '200px'
  },
  {
    key: 'phone',
    label: 'Telefon',
    sortable: true,
    type: 'text',
    width: '150px'
  },
  {
    key: 'status',
    label: 'Status',
    sortable: true,
    width: '120px'
  },
  {
    key: 'tags',
    label: 'Tags',
    sortable: false,
    width: '200px'
  },
  {
    key: 'created_at',
    label: 'Erstellt am',
    sortable: true,
    type: 'date',
    width: '120px'
  }
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

function handleCreate() {
  rsdStore.openCreate('partner')
}

function handleItemSelected(partner: Partner) {
  router.push({ name: 'partner-detail', params: { id: partner.id } })
}

function viewPartner(partner: Partner) {
  router.push({ name: 'partner-detail', params: { id: partner.id } })
}

function editPartner(partner: Partner) {
  rsdStore.openEdit('partner', partner)
}

async function deletePartner(partner: Partner) {
  if (!confirm(`Möchten Sie den Partner "${partner.name}" wirklich löschen?`)) {
    return
  }

  try {
    await partnerService.deletePartner(partner.id)
    showSuccess('Partner erfolgreich gelöscht')
    // ADT will auto-refresh
  } catch (error: any) {
    showError(error.response?.data?.message || 'Fehler beim Löschen des Partners')
  }
}

async function loadStatistics() {
  try {
    statistics.value = await partnerService.getStatistics()
  } catch (error) {
    console.error('Failed to load statistics:', error)
  }
}

// Lifecycle
onMounted(() => {
  loadStatistics()
})
</script>

<style scoped>
.partner-list-view {
  padding: 24px;
}
</style>