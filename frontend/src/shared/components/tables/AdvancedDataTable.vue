<template>
  <v-card>
    <!-- Toolbar -->
    <v-card-title class="d-flex align-center justify-space-between">
      <div class="d-flex align-center gap-4">
        <v-text-field
          v-model="search"
          prepend-inner-icon="mdi-magnify"
          label="Suchen..."
          variant="outlined"
          density="compact"
          hide-details
          clearable
          style="max-width: 300px;"
        />
      </div>
      
      <div>
        <v-btn
          v-if="enableCreate"
          color="primary"
          prepend-icon="mdi-plus"
          @click="$emit('create')"
        >
          {{ createButtonText || 'Neu erstellen' }}
        </v-btn>
      </div>
    </v-card-title>

    <v-divider />

    <!-- Data Table -->
    <v-data-table
      :headers="computedHeaders"
      :items="items"
      :search="search"
      :loading="loading"
      item-value="id"
      class="elevation-0"
      @click:row="handleRowClick"
    >
      <!-- Dynamic Slots for Custom Cell Content -->
      <template
        v-for="column in columns"
        :key="column.key"
        #[`item.${column.key}`]="slotProps"
      >
        <slot
          :name="`item.${column.key}`"
          v-bind="slotProps"
        >
          {{ slotProps.value }}
        </slot>
      </template>

      <!-- Loading State -->
      <template #loading>
        <v-skeleton-loader type="table-row@10" />
      </template>

      <!-- No Data State -->
      <template #no-data>
        <div class="text-center pa-8">
          <v-icon size="48" color="grey-lighten-1">mdi-database-off</v-icon>
          <p class="text-h6 mt-4 mb-2">Keine Daten vorhanden</p>
          <p class="text-body-2 text-medium-emphasis">
            Es wurden noch keine Einträge erstellt.
          </p>
          <v-btn
            v-if="enableCreate"
            color="primary"
            class="mt-4"
            @click="$emit('create')"
          >
            Ersten Eintrag erstellen
          </v-btn>
        </div>
      </template>
    </v-data-table>
  </v-card>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useApi } from '@/core/api'
import { useToast } from '@/shared/composables/useToast'

export interface TableColumn {
  key: string
  label: string
  type?: string
  sortable?: boolean
  searchable?: boolean
  filterable?: boolean
  width?: string
  options?: Array<{value: string, label: string}>
}

interface Props {
  columns: TableColumn[]
  apiEndpoint: string
  enableCreate?: boolean
  createButtonText?: string
  loading?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  enableCreate: false,
  createButtonText: 'Neu erstellen',
  loading: false
})

const emit = defineEmits<{
  create: []
  itemSelected: [item: any]
}>()

// Composables
const api = useApi()
const toast = useToast()

// State
const search = ref('')
const items = ref<any[]>([])
const internalLoading = ref(false)

// Computed
const loading = computed(() => props.loading || internalLoading.value)

const computedHeaders = computed(() => {
  return props.columns.map(column => ({
    title: column.label,
    key: column.key,
    sortable: column.sortable !== false,
    width: column.width || undefined,
  }))
})

// Methods
const loadData = async () => {
  try {
    internalLoading.value = true
    
    const response = await api.get(props.apiEndpoint)
    
    // Handle different response formats
    if (response.data.users) {
      items.value = response.data.users
    } else if (response.data.data) {
      items.value = response.data.data
    } else if (Array.isArray(response.data)) {
      items.value = response.data
    } else {
      items.value = []
    }
    
  } catch (error: any) {
    console.error('Error loading data:', error)
    toast.error('Fehler beim Laden der Daten')
    items.value = []
  } finally {
    internalLoading.value = false
  }
}

const handleRowClick = (event: Event, row: any) => {
  emit('itemSelected', row.item)
}

// Refresh function that can be called from parent
const refresh = () => {
  loadData()
}

// Watch for endpoint changes
watch(() => props.apiEndpoint, () => {
  loadData()
})

// Listen for refresh events
onMounted(() => {
  loadData()
  
  // Listen for global refresh events
  window.addEventListener('table-refresh', () => {
    refresh()
  })
  
  window.addEventListener('rsd-success', () => {
    refresh()
  })
})

// Expose refresh method
defineExpose({
  refresh
})
</script>

<style scoped>
:deep(.v-data-table__wrapper) {
  min-height: 400px;
}

:deep(.v-data-table-row:hover) {
  cursor: pointer;
}
</style>