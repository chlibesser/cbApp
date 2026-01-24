<template>
  <div class="d-flex align-center gap-4">
    <!-- Search Field -->
    <v-text-field
      v-if="enableSearch"
      v-model="searchInput"
      prepend-inner-icon="mdi-magnify"
      :label="t('buttons.search')"
      variant="outlined"
      density="compact"
      hide-details
      clearable
      color="primary"
      style="max-width: 300px;"
      @update:model-value="handleSearchInput"
    />

    <!-- Filter Quick-Access Buttons -->
    <template v-if="enableFilters && tableKey && buttonFilters.length > 0">
      <v-btn
        v-for="filter in buttonFilters"
        :key="filter.id"
        :color="activeFilterId === filter.id ? filter.color : undefined"
        :variant="activeFilterId === filter.id ? 'flat' : 'outlined'"
        size="small"
        @click="handleQuickFilterClick(filter)"
      >
        {{ filter.name }}
      </v-btn>
    </template>

    <!-- Combined Settings Menu -->
    <v-menu
      v-model="settingsMenuOpen"
      :close-on-content-click="false"
      location="bottom end"
    >
      <template #activator="{ props: menuProps }">
        <v-btn
          v-bind="menuProps"
          variant="outlined"
          size="small"
          color="primary"
        >
          <v-icon start>mdi-filter-variant</v-icon>
          Filter
          <v-badge
            v-if="filterCount > 0"
            :content="filterCount"
            color="primary"
            inline
          />
          <v-icon end>mdi-cog</v-icon>
        </v-btn>
      </template>

      <v-card min-width="280">
        <!-- Filter Section -->
        <template v-if="enableFilters && tableKey">
          <v-card-title class="text-subtitle-1 d-flex align-center justify-space-between py-2">
            Gespeicherte Filter
            <v-btn
              icon
              variant="text"
              size="small"
              @click="handleOpenSaveDialog"
            >
              <v-icon>mdi-plus</v-icon>
              <v-tooltip activator="parent" location="top">
                Aktuellen Filter speichern
              </v-tooltip>
            </v-btn>
          </v-card-title>

          <v-list v-if="filters.length > 0" density="compact" class="py-0">
            <v-list-item
              v-for="filter in filters"
              :key="filter.id"
              :active="activeFilterId === filter.id"
              @click="handleFilterClick(filter)"
            >
              <template #prepend>
                <v-avatar :color="filter.color" size="24">
                  <v-icon size="small" color="white">mdi-filter</v-icon>
                </v-avatar>
              </template>

              <v-list-item-title>{{ filter.name }}</v-list-item-title>

              <template #append>
                <v-btn
                  icon
                  variant="text"
                  size="x-small"
                  @click.stop="emit('filter-edit', filter)"
                >
                  <v-icon size="small">mdi-pencil</v-icon>
                </v-btn>
                <v-btn
                  icon
                  variant="text"
                  size="x-small"
                  color="error"
                  @click.stop="emit('filter-delete', filter)"
                >
                  <v-icon size="small">mdi-delete</v-icon>
                </v-btn>
              </template>
            </v-list-item>
          </v-list>

          <v-card-text v-else class="text-center text-medium-emphasis py-3">
            Keine Filter gespeichert
          </v-card-text>

          <template v-if="activeFilterId">
            <v-list-item @click="handleResetFilters">
              <template #prepend>
                <v-icon>mdi-filter-off</v-icon>
              </template>
              <v-list-item-title>Filter zurücksetzen</v-list-item-title>
            </v-list-item>
          </template>

          <v-divider />
        </template>

        <!-- Column Settings Section -->
        <v-list-subheader>Spalten</v-list-subheader>

        <v-list-item
          :disabled="!hasCustomColumnOrder"
          @click="handleResetColumnOrder"
        >
          <template #prepend>
            <v-icon>mdi-table-column</v-icon>
          </template>
          <v-list-item-title>Spaltenreihenfolge zurücksetzen</v-list-item-title>
        </v-list-item>

        <v-list-item
          :disabled="!hasCustomColumnWidths"
          @click="handleResetColumnWidths"
        >
          <template #prepend>
            <v-icon>mdi-arrow-expand-horizontal</v-icon>
          </template>
          <v-list-item-title>Spaltenbreiten zurücksetzen</v-list-item-title>
        </v-list-item>

        <v-divider class="my-1" />

        <v-list-item
          :disabled="!hasCustomSettings"
          color="error"
          @click="handleResetAllSettings"
        >
          <template #prepend>
            <v-icon>mdi-refresh</v-icon>
          </template>
          <v-list-item-title>Alles zurücksetzen</v-list-item-title>
        </v-list-item>
      </v-card>
    </v-menu>

    <!-- Create Button -->
    <v-btn
      v-if="enableCreate"
      color="primary"
      prepend-icon="mdi-plus"
      @click="emit('create')"
    >
      {{ createButtonText || t('buttons.create') }}
    </v-btn>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useTranslations } from '@/core/localization/composables/useTranslations'
import type { TableFilter } from '@/types/tableFilter'

const { t } = useTranslations('admin.common')

interface Props {
  tableKey?: string
  modelValue?: string
  enableSearch?: boolean
  enableFilters?: boolean
  enableCreate?: boolean
  createButtonText?: string
  activeFilterId?: string | null
  filters?: TableFilter[]
  buttonFilters?: TableFilter[]
  hasCustomColumnOrder?: boolean
  hasCustomColumnWidths?: boolean
  hasCustomSettings?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  tableKey: '',
  modelValue: '',
  enableSearch: true,
  enableFilters: true,
  enableCreate: false,
  createButtonText: '',
  activeFilterId: null,
  filters: () => [],
  buttonFilters: () => [],
  hasCustomColumnOrder: false,
  hasCustomColumnWidths: false,
  hasCustomSettings: false
})

const emit = defineEmits<{
  'update:modelValue': [search: string]
  'filter-apply': [filter: TableFilter]
  'filter-reset': []
  'filter-save': []
  'filter-edit': [filter: TableFilter]
  'filter-delete': [filter: TableFilter]
  'create': []
  'reset-column-order': []
  'reset-column-widths': []
  'reset-all-settings': []
}>()

// Local state
const searchInput = ref(props.modelValue)
const settingsMenuOpen = ref(false)
let searchTimeout: ReturnType<typeof setTimeout> | null = null

// Computed
const filterCount = computed(() => props.filters?.length || 0)

// Sync modelValue to local state
watch(() => props.modelValue, (val) => {
  searchInput.value = val
})

// Debounced Search Handler
const handleSearchInput = (value: string | null) => {
  if (searchTimeout) {
    clearTimeout(searchTimeout)
  }

  searchTimeout = setTimeout(() => {
    emit('update:modelValue', value || '')
  }, 300)
}

// Filter Handlers
const handleQuickFilterClick = (filter: TableFilter) => {
  if (props.activeFilterId === filter.id) {
    emit('filter-reset')
  } else {
    emit('filter-apply', filter)
  }
}

const handleFilterClick = (filter: TableFilter) => {
  if (props.activeFilterId === filter.id) {
    emit('filter-reset')
  } else {
    emit('filter-apply', filter)
  }
  settingsMenuOpen.value = false
}

const handleResetFilters = () => {
  emit('filter-reset')
  settingsMenuOpen.value = false
}

const handleOpenSaveDialog = () => {
  settingsMenuOpen.value = false
  emit('filter-save')
}

// Column Settings Handlers
const handleResetColumnOrder = () => {
  emit('reset-column-order')
  settingsMenuOpen.value = false
}

const handleResetColumnWidths = () => {
  emit('reset-column-widths')
  settingsMenuOpen.value = false
}

const handleResetAllSettings = () => {
  emit('reset-all-settings')
  settingsMenuOpen.value = false
}
</script>
