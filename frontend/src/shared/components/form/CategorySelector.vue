<template>
  <v-select
    :model-value="modelValue"
    @update:model-value="$emit('update:modelValue', $event)"
    :items="categoriesForSelect"
    item-title="display_name"
    item-value="id"
    :label="label"
    :placeholder="placeholder"
    :required="required"
    :clearable="clearable"
    :loading="loading"
    :error-messages="errorMessages"
    :prepend-icon="prependIcon"
    :multiple="multiple"
    :chips="multiple"
    :closable-chips="multiple"
  >
    <template #item="{ props: itemProps, item }">
      <v-list-item
        v-bind="itemProps"
        :prepend-icon="item.raw.icon"
        :subtitle="item.raw.category_group_name"
      >
        <v-list-item-title class="d-flex align-center">
          <v-chip
            v-if="item.raw.color"
            :color="item.raw.color"
            size="x-small"
            class="mr-2"
          />
          {{ item.raw.name }}
        </v-list-item-title>
      </v-list-item>
    </template>

    <template #selection="{ item }">
      <v-chip
        v-if="multiple"
        size="small"
        :color="item.raw.color"
        :prepend-icon="item.raw.icon"
        closable
        @click:close="removeCategory(item.raw.id)"
      >
        {{ item.raw.name }}
      </v-chip>
      <span v-else class="d-flex align-center">
        <v-icon
          v-if="item.raw.icon"
          :icon="item.raw.icon"
          :color="item.raw.color"
          size="16"
          class="mr-2"
        />
        {{ item.raw.name }}
      </span>
    </template>

    <template #prepend-item v-if="showGroupHeaders">
      <div class="px-4 py-2 text-caption text-medium-emphasis font-weight-bold">
        Kategorien nach Gruppen
      </div>
    </template>

    <!-- Group headers in dropdown -->
    <template #no-data>
      <v-list-item>
        <v-list-item-title class="text-center text-medium-emphasis">
          {{ loading ? 'Kategorien werden geladen...' : 'Keine Kategorien verfügbar' }}
        </v-list-item-title>
      </v-list-item>
    </template>
  </v-select>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'

interface Category {
  id: string
  name: string
  icon?: string
  color?: string
  category_group_id: string
  category_group_name?: string
  is_active: boolean
}

interface CategoryGroup {
  id: string
  name: string
  categories: Category[]
}

interface Props {
  modelValue?: string | string[] | null
  tenantId?: string
  categoryGroupId?: string // Filter by specific category group
  label?: string
  placeholder?: string
  required?: boolean
  clearable?: boolean
  multiple?: boolean
  errorMessages?: string[]
  prependIcon?: string
  showGroupHeaders?: boolean
  includeInactive?: boolean
}

interface Emits {
  (e: 'update:modelValue', value: string | string[] | null): void
}

const props = withDefaults(defineProps<Props>(), {
  label: 'Kategorie auswählen',
  placeholder: 'Kategorie wählen...',
  required: false,
  clearable: false,
  multiple: false,
  prependIcon: 'mdi-tag',
  showGroupHeaders: true,
  includeInactive: false
})

const emit = defineEmits<Emits>()

// Reactive data
const categories = ref<Category[]>([])
const categoryGroups = ref<CategoryGroup[]>([])
const loading = ref(false)

// Computed
const categoriesForSelect = computed(() => {
  let filteredCategories = categories.value

  // Filter by active status
  if (!props.includeInactive) {
    filteredCategories = filteredCategories.filter(cat => cat.is_active)
  }

  // Filter by specific category group if specified
  if (props.categoryGroupId) {
    filteredCategories = filteredCategories.filter(cat => cat.category_group_id === props.categoryGroupId)
  }

  // Add display name and group information
  return filteredCategories.map(category => ({
    ...category,
    display_name: `${category.name}${category.category_group_name ? ` (${category.category_group_name})` : ''}`,
  }))
})

// Methods
const loadCategories = async () => {
  if (!props.tenantId) return

  loading.value = true
  
  try {
    // Load category groups with their categories
    const response = await fetch(`/tenant/category-groups`, {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Content-Type': 'application/json'
      }
    })

    if (response.ok) {
      const data = await response.json()
      categoryGroups.value = data.data || []
      
      // Flatten categories and add group information
      categories.value = categoryGroups.value.flatMap(group => 
        group.categories.map(category => ({
          ...category,
          category_group_name: group.name
        }))
      )
    } else {
      console.error('Failed to load categories:', response.statusText)
    }
  } catch (error) {
    console.error('Error loading categories:', error)
  } finally {
    loading.value = false
  }
}

const removeCategory = (categoryId: string) => {
  if (!props.multiple || !Array.isArray(props.modelValue)) return

  const newValue = props.modelValue.filter(id => id !== categoryId)
  emit('update:modelValue', newValue)
}

// Watchers
watch(() => props.tenantId, (newTenantId) => {
  if (newTenantId) {
    loadCategories()
  } else {
    categories.value = []
    categoryGroups.value = []
  }
}, { immediate: true })

watch(() => props.categoryGroupId, () => {
  // Re-filter categories when group filter changes
  // The computed property will handle this automatically
})

// Lifecycle
onMounted(() => {
  if (props.tenantId) {
    loadCategories()
  }
})

// Expose methods for parent component
defineExpose({
  loadCategories,
  categories: computed(() => categories.value),
  categoryGroups: computed(() => categoryGroups.value)
})
</script>

<style scoped>
:deep(.v-select__selection) {
  max-width: 200px;
}

:deep(.v-chip) {
  max-width: 180px;
}

:deep(.v-chip .v-chip__content) {
  overflow: hidden;
  text-overflow: ellipsis;
}
</style>