<template>
  <div class="filter-dropdown d-flex align-center ga-2">
    <!-- Quick-Access Buttons -->
    <template v-if="buttonFilters.length > 0">
      <v-btn
        v-for="filter in buttonFilters"
        :key="filter.id"
        :color="activeFilterId === filter.id ? filter.color : undefined"
        :variant="activeFilterId === filter.id ? 'flat' : 'outlined'"
        size="small"
        @click="handleFilterClick(filter)"
      >
        {{ filter.name }}
      </v-btn>

      <v-divider vertical class="mx-1" />
    </template>

    <!-- Filter-Dropdown Menu -->
    <v-menu
      v-model="menuOpen"
      :close-on-content-click="false"
      location="bottom end"
    >
      <template #activator="{ props: menuProps }">
        <v-btn
          v-bind="menuProps"
          variant="text"
          size="small"
          :color="activeFilterId && !isActiveFilterAButton ? 'primary' : undefined"
        >
          <v-icon start>mdi-filter-variant</v-icon>
          Filter
          <v-badge
            v-if="filters.length > 0"
            :content="filters.length"
            color="primary"
            inline
          />
        </v-btn>
      </template>

      <v-card min-width="280">
        <v-card-title class="text-subtitle-1 d-flex align-center justify-space-between">
          Gespeicherte Filter
          <v-btn
            icon
            variant="text"
            size="small"
            @click="handleSaveNew"
          >
            <v-icon>mdi-plus</v-icon>
            <v-tooltip activator="parent" location="top">
              Aktuellen Filter speichern
            </v-tooltip>
          </v-btn>
        </v-card-title>

        <v-divider />

        <v-list v-if="filters.length > 0" density="compact">
          <v-list-item
            v-for="filter in filters"
            :key="filter.id"
            :active="activeFilterId === filter.id"
            @click="handleFilterClick(filter)"
          >
            <template #prepend>
              <v-avatar
                :color="filter.color"
                size="24"
              >
                <v-icon size="small" color="white">mdi-filter</v-icon>
              </v-avatar>
            </template>

            <v-list-item-title>{{ filter.name }}</v-list-item-title>

            <template #append>
              <v-btn
                icon
                variant="text"
                size="x-small"
                @click.stop="handleEditFilter(filter)"
              >
                <v-icon size="small">mdi-pencil</v-icon>
              </v-btn>
              <v-btn
                icon
                variant="text"
                size="x-small"
                color="error"
                @click.stop="handleDeleteFilter(filter)"
              >
                <v-icon size="small">mdi-delete</v-icon>
              </v-btn>
            </template>
          </v-list-item>
        </v-list>

        <v-card-text v-else class="text-center text-medium-emphasis">
          Keine Filter gespeichert
        </v-card-text>

        <template v-if="activeFilterId">
          <v-divider />
          <v-card-actions>
            <v-btn
              variant="text"
              size="small"
              block
              @click="handleReset"
            >
              <v-icon start>mdi-filter-off</v-icon>
              Filter zurücksetzen
            </v-btn>
          </v-card-actions>
        </template>
      </v-card>
    </v-menu>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useTableFilterStore } from '@/infrastructure/stores/tableFilterStore'
import { useNotifications } from '@/core/composables/useNotifications'
import type { TableFilter, TableFilterState } from '@/types/tableFilter'

interface Props {
  tableKey: string
  currentState: TableFilterState
}

const props = defineProps<Props>()

const emit = defineEmits<{
  applyFilter: [state: TableFilterState]
  reset: []
  openSaveDialog: [filter: TableFilter | null]
}>()

const filterStore = useTableFilterStore()
const { showSuccess, showError } = useNotifications()

const menuOpen = ref(false)

const filters = computed(() => filterStore.getFiltersForTable(props.tableKey))
const buttonFilters = computed(() => filterStore.getButtonFilters(props.tableKey))
const activeFilterId = computed(() => filterStore.activeFilterId)

const isActiveFilterAButton = computed(() => {
  if (!activeFilterId.value) return false
  return buttonFilters.value.some(f => f.id === activeFilterId.value)
})

const handleFilterClick = (filter: TableFilter) => {
  if (activeFilterId.value === filter.id) {
    filterStore.clearActiveFilter()
    emit('reset')
  } else {
    filterStore.setActiveFilter(filter.id)
    emit('applyFilter', filter.filter_state)
  }
  menuOpen.value = false
}

const handleSaveNew = () => {
  menuOpen.value = false
  emit('openSaveDialog', null)
}

const handleEditFilter = (filter: TableFilter) => {
  menuOpen.value = false
  emit('openSaveDialog', filter)
}

const handleDeleteFilter = async (filter: TableFilter) => {
  try {
    await filterStore.deleteFilter(filter.id, props.tableKey)
    showSuccess('Filter erfolgreich gelöscht')
  } catch (e: any) {
    showError(e.message || 'Fehler beim Löschen')
  }
}

const handleReset = () => {
  filterStore.clearActiveFilter()
  emit('reset')
  menuOpen.value = false
}

onMounted(async () => {
  try {
    await filterStore.loadFilters(props.tableKey)
  } catch (e) {
    console.warn('Fehler beim Laden der Filter:', e)
  }
})
</script>

<style scoped>
.filter-dropdown {
  flex-shrink: 0;
}
</style>
