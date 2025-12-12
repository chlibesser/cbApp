<template>
  <div class="category-management">
    <!-- Auth Check -->
    <div v-if="!authStore.isAuthenticated" class="text-center pa-8">
      <v-alert type="warning" class="mb-4">
        Sie müssen angemeldet sein, um Kategorien zu verwalten.
      </v-alert>
      <v-btn 
        color="primary" 
        prepend-icon="mdi-login"
        @click="showQuickLogin = true"
      >
        Anmelden
      </v-btn>
    </div>

    <!-- Main Content (when authenticated) -->
    <div v-else class="category-content">
      <!-- Sticky Header -->
      <div class="category-header">
        <div class="d-flex justify-space-between align-center">
          <div>
            <h2 class="text-h5 font-weight-bold mb-1">
              Kategorien verwalten
              <v-chip 
                size="x-small" 
                color="primary" 
                variant="flat"
                class="ml-2"
              >
                {{ categoryGroups.length }}
              </v-chip>
            </h2>
            <p class="text-body-2 text-medium-emphasis mb-0">
              Kategorie-Gruppen für automatische Klassifizierung
            </p>
          </div>
          
          <v-btn
            color="primary"
            prepend-icon="mdi-plus"
            @click="createCategoryGroup"
          >
            Gruppe erstellen
          </v-btn>
        </div>
      </div>

      <!-- Scrollable Content -->
      <div class="category-body">

    <!-- Empty State -->
    <v-card v-if="categoryGroups.length === 0" class="text-center pa-12">
      <v-icon size="64" color="grey-lighten-1" class="mb-4">
        mdi-tag-multiple-outline
      </v-icon>
      <h3 class="text-h6 mb-2">Keine Kategorie-Gruppen vorhanden</h3>
      <p class="text-body-2 text-medium-emphasis mb-4">
        Erstellen Sie Ihre erste Kategorie-Gruppe, um mit der automatischen Klassifizierung zu beginnen.
      </p>
      <v-btn
        color="primary"
        prepend-icon="mdi-plus"
        @click="createCategoryGroup"
      >
        Erste Kategorie-Gruppe erstellen
      </v-btn>
    </v-card>

    <!-- Category Groups List -->
    <div v-else>
      <v-expansion-panels v-model="openPanels" multiple class="mb-4">
        <v-expansion-panel
          v-for="group in categoryGroups"
          :key="group.id"
          :value="group.id"
          elevation="1"
          class="mb-4"
        >
          <v-expansion-panel-title>
            <div class="d-flex align-center w-100">
              <v-icon 
                :icon="group.icon || 'mdi-tag-multiple'" 
                :color="group.color || 'primary'"
                class="mr-4" 
                size="24"
              />
              
              <div class="flex-grow-1">
                <div class="d-flex align-center">
                  <h4 class="text-h6 mb-1">{{ group.name }}</h4>
                  <v-chip 
                    size="small" 
                    :color="group.selection_type === 'single' ? 'info' : 'warning'"
                    variant="outlined"
                    class="ml-2"
                  >
                    {{ group.selection_type === 'single' ? 'Einfachauswahl' : 'Mehrfachauswahl' }}
                  </v-chip>
                  <v-chip 
                    v-if="group.ai_enabled"
                    size="small" 
                    color="success"
                    variant="outlined"
                    class="ml-1"
                  >
                    KI aktiv
                  </v-chip>
                </div>
                <p class="text-body-2 text-medium-emphasis mb-0">
                  {{ group.description || 'Keine Beschreibung' }}
                </p>
                <div class="d-flex align-center mt-1">
                  <v-chip 
                    size="x-small" 
                    color="primary" 
                    variant="flat"
                  >
                    {{ group.categories?.length || 0 }} Kategorien
                  </v-chip>
                  <span class="text-caption text-medium-emphasis ml-2">
                    {{ group.is_required ? 'Pflichtfeld' : 'Optional' }}
                  </span>
                </div>
              </div>
              
              <div class="d-flex align-center">
                <v-btn
                  icon="mdi-pencil"
                  variant="text"
                  size="small"
                  @click.stop="editCategoryGroup(group)"
                />
                <v-btn
                  icon="mdi-delete"
                  variant="text"
                  size="small"
                  color="error"
                  @click.stop="deleteCategoryGroup(group)"
                />
                <v-switch
                  v-model="group.is_active"
                  hide-details
                  color="success"
                  @click.stop
                  @change="toggleGroupActive(group)"
                  class="ml-2"
                />
              </div>
            </div>
          </v-expansion-panel-title>
          
          <v-expansion-panel-text>
            <!-- Categories List -->
            <div class="categories-section">
              <div class="d-flex justify-space-between align-center mb-4">
                <h5 class="text-h6">Kategorien</h5>
                <v-btn
                  color="primary"
                  variant="outlined"
                  prepend-icon="mdi-plus"
                  size="small"
                  @click="createCategory(group)"
                >
                  Kategorie hinzufügen
                </v-btn>
              </div>

              <!-- Categories Grid -->
              <div v-if="group.categories && group.categories.length > 0" class="categories-grid">
                <v-card
                  v-for="category in group.categories"
                  :key="category.id"
                  variant="outlined"
                  class="category-card pa-4 mb-3"
                >
                  <div class="d-flex justify-space-between align-start">
                    <div class="flex-grow-1">
                      <div class="d-flex align-center mb-2">
                        <v-icon 
                          :icon="category.icon || 'mdi-tag'" 
                          :color="category.color || 'grey'"
                          size="20"
                          class="mr-2"
                        />
                        <h6 class="text-h6">{{ category.name }}</h6>
                        <v-chip 
                          v-if="category.is_default"
                          size="x-small" 
                          color="warning"
                          class="ml-2"
                        >
                          Standard
                        </v-chip>
                      </div>
                      
                      <p class="text-body-2 text-medium-emphasis mb-2">
                        {{ category.description || 'Keine Beschreibung' }}
                      </p>
                      
                      <!-- AI Descriptions Preview -->
                      <div v-if="category.ai_positive_description" class="ai-preview">
                        <v-chip size="x-small" color="success" variant="outlined" class="mb-1">
                          KI-Definition
                        </v-chip>
                        <p class="text-caption">
                          {{ truncateText(category.ai_positive_description, 100) }}
                        </p>
                      </div>
                      
                      <!-- Keywords Preview -->
                      <div v-if="category.ai_keywords && category.ai_keywords.length > 0" class="keywords-preview mt-2">
                        <div class="d-flex flex-wrap gap-1">
                          <v-chip
                            v-for="keyword in category.ai_keywords.slice(0, 3)"
                            :key="keyword"
                            size="x-small"
                            variant="outlined"
                            color="info"
                          >
                            {{ keyword }}
                          </v-chip>
                          <v-chip
                            v-if="category.ai_keywords.length > 3"
                            size="x-small"
                            variant="outlined"
                            color="grey"
                          >
                            +{{ category.ai_keywords.length - 3 }}
                          </v-chip>
                        </div>
                      </div>
                      
                      <!-- Usage Stats -->
                      <div class="d-flex align-center mt-2 text-caption text-medium-emphasis">
                        <span>{{ category.usage_count || 0 }} mal verwendet</span>
                        <v-divider vertical class="mx-2" />
                        <span>
                          {{ category.last_used_at ? formatDate(category.last_used_at) : 'Nie verwendet' }}
                        </span>
                      </div>
                    </div>
                    
                    <div class="d-flex flex-column align-end">
                      <div class="d-flex">
                        <v-btn
                          icon="mdi-pencil"
                          variant="text"
                          size="small"
                          @click="editCategory(category, group)"
                        />
                        <v-btn
                          icon="mdi-delete"
                          variant="text"
                          size="small"
                          color="error"
                          @click="deleteCategory(category, group)"
                        />
                      </div>
                      <v-switch
                        v-model="category.is_active"
                        hide-details
                        color="success"
                        @change="toggleCategoryActive(category, group)"
                        class="mt-2"
                      />
                    </div>
                  </div>
                </v-card>
              </div>

              <!-- Empty Categories State -->
              <v-card v-else variant="outlined" class="text-center pa-8">
                <v-icon size="48" color="grey-lighten-1" class="mb-2">
                  mdi-tag-plus-outline
                </v-icon>
                <h6 class="text-h6 mb-2">Keine Kategorien</h6>
                <p class="text-body-2 text-medium-emphasis mb-3">
                  Fügen Sie Kategorien zu dieser Gruppe hinzu.
                </p>
                <v-btn
                  color="primary"
                  variant="outlined"
                  prepend-icon="mdi-plus"
                  @click="createCategory(group)"
                >
                  Erste Kategorie erstellen
                </v-btn>
              </v-card>
            </div>

            <!-- AI Settings (if enabled) -->
            <div v-if="group.ai_enabled" class="ai-settings-section mt-6">
              <v-divider class="mb-4" />
              <div class="d-flex justify-space-between align-center mb-3">
                <h5 class="text-h6">KI-Einstellungen</h5>
                <v-btn
                  variant="outlined"
                  prepend-icon="mdi-cog"
                  size="small"
                  @click="configureAI(group)"
                >
                  KI konfigurieren
                </v-btn>
              </div>
              
              <v-row>
                <v-col cols="6">
                  <v-card variant="outlined" class="pa-3">
                    <div class="text-body-2 text-medium-emphasis mb-1">Vertrauensschwelle</div>
                    <div class="text-h6">{{ (group.ai_confidence_threshold * 100).toFixed(0) }}%</div>
                  </v-card>
                </v-col>
                <v-col cols="6">
                  <v-card variant="outlined" class="pa-3">
                    <div class="text-body-2 text-medium-emphasis mb-1">KI-Status</div>
                    <div class="text-h6">
                      <v-icon color="success" class="mr-1">mdi-check-circle</v-icon>
                      Aktiv
                    </div>
                  </v-card>
                </v-col>
              </v-row>
            </div>
          </v-expansion-panel-text>
        </v-expansion-panel>
      </v-expansion-panels>
    </div>
    </div> <!-- End category-body -->
    </div> <!-- End category-content -->

    <!-- Quick Login Dialog -->
    <v-dialog v-model="showQuickLogin" max-width="500">
      <v-card>
        <v-card-title>Quick Login (Development)</v-card-title>
        <v-card-text>
          <div v-if="quickLoginAccounts.length > 0">
            <p class="mb-4">Wählen Sie einen Account für Quick Login:</p>
            <v-list>
              <v-list-item
                v-for="account in quickLoginAccounts"
                :key="account.username"
                @click="performQuickLogin(account.username)"
                :prepend-avatar="account.avatar || undefined"
              >
                <v-list-item-title>{{ account.name }}</v-list-item-title>
                <v-list-item-subtitle>{{ account.username }} ({{ account.system_role }})</v-list-item-subtitle>
              </v-list-item>
            </v-list>
          </div>
          <div v-else>
            <v-progress-circular indeterminate />
            <p>Lade Quick Login Accounts...</p>
          </div>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn @click="showQuickLogin = false">Abbrechen</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>


    <!-- RSD (Right Side Drawer) -->
    <RightDrawer />
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, onBeforeUnmount } from 'vue'
import { useApi } from '../../../core/api'
import { useToast } from '../../../shared/composables/useToast'
import { useAuthStore } from '../../../infrastructure/stores/authStore'
import { useRSDStore } from '../../../infrastructure/stores/rsdStore'
import RightDrawer from '../../../shared/components/RightDrawer.vue'

// Types
interface CategoryGroup {
  id: string
  tenant_id: string
  name: string
  slug: string
  description?: string
  icon?: string
  color?: string
  selection_type: 'single' | 'multi'
  is_required: boolean
  is_active: boolean
  display_order: number
  ai_enabled: boolean
  ai_prompt_context?: string
  ai_confidence_threshold: number
  categories?: Category[]
  created_at?: string
  updated_at?: string
}

interface Category {
  id: string
  category_group_id: string
  name: string
  slug: string
  description?: string
  icon?: string
  color?: string
  is_default: boolean
  is_active: boolean
  display_order: number
  ai_positive_description?: string
  ai_negative_description?: string
  ai_keywords?: string[]
  custom_prompt?: string
  usage_count: number
  last_used_at?: string
  created_at?: string
  updated_at?: string
}

const { showToast } = useToast()
const authStore = useAuthStore()
const rsdStore = useRSDStore()
const api = useApi()
const loading = ref(false)

// Quick Login for development
const showQuickLogin = ref(false)
const quickLoginAccounts = ref<any[]>([])

// Real data from API
const categoryGroups = ref<CategoryGroup[]>([])

const openPanels = ref<string[]>([]) // Dynamic based on loaded groups

// API Methods
const loadCategoryGroups = async () => {
  // Check if user is authenticated, if not show quick login
  if (!authStore.isAuthenticated) {
    showQuickLogin.value = true
    await loadQuickLoginAccounts()
    return
  }

  try {
    loading.value = true
    const response = await api.get('/tenant/category-groups')
    const groups = response.data.data || response.data
    categoryGroups.value = groups
    
    // Open all panels by default for better UX
    openPanels.value = groups.map(group => group.id)
    
    console.log('Category groups loaded:', groups)
  } catch (error) {
    console.error('Error loading category groups:', error)
    showToast({
      message: error instanceof Error ? error.message : 'Fehler beim Laden der Kategorie-Gruppen',
      type: 'error'
    })
  } finally {
    loading.value = false
  }
}

// Quick Login Functions
const loadQuickLoginAccounts = async () => {
  try {
    const response = await fetch('http://localhost:8004/api/auth/quick-login')
    if (response.ok) {
      const data = await response.json()
      quickLoginAccounts.value = data.accounts || []
    }
  } catch (error) {
    console.error('Error loading quick login accounts:', error)
  }
}

const performQuickLogin = async (username: string) => {
  try {
    const response = await fetch('http://localhost:8004/api/auth/quick-login', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ username })
    })

    if (response.ok) {
      const data = await response.json()
      
      // Store the token and user data
      localStorage.setItem('auth_token', data.token)
      authStore.setAccount(data.account)
      
      showQuickLogin.value = false
      showToast({
        message: `Erfolgreich eingeloggt als ${data.account.name}`,
        type: 'success'
      })
      
      // Now load the category groups
      await loadCategoryGroups()
    } else {
      throw new Error('Quick Login failed')
    }
  } catch (error) {
    console.error('Quick login error:', error)
    showToast({
      message: 'Fehler beim Quick Login',
      type: 'error'
    })
  }
}


// Event Handlers
const createCategoryGroup = () => {
  rsdStore.openCreate('category-group')
}

const editCategoryGroup = (group: CategoryGroup) => {
  rsdStore.openEdit('category-group', group)
}

const deleteCategoryGroup = async (group: CategoryGroup) => {
  if (confirm(`Sind Sie sicher, dass Sie die Kategorie-Gruppe "${group.name}" löschen möchten?`)) {
    try {
      await api.delete(`/tenant/category-groups/${group.id}`)
      
      // Remove from local state
      const index = categoryGroups.value.findIndex(g => g.id === group.id)
      if (index !== -1) {
        categoryGroups.value.splice(index, 1)
      }
      
      showToast({
        message: `Kategorie-Gruppe "${group.name}" erfolgreich gelöscht`,
        type: 'success'
      })
    } catch (error) {
      console.error('Error deleting group:', error)
      showToast({
        message: error instanceof Error ? error.message : 'Fehler beim Löschen der Kategorie-Gruppe',
        type: 'error'
      })
    }
  }
}

const toggleGroupActive = async (group: CategoryGroup) => {
  try {
    const response = await api.patch(`/tenant/category-groups/${group.id}/toggle`)
    const updatedGroup = response.data.data || response.data
    
    // Update local state
    const index = categoryGroups.value.findIndex(g => g.id === group.id)
    if (index !== -1) {
      categoryGroups.value[index] = updatedGroup
    }
    
    showToast({
      message: updatedGroup.is_active 
        ? `Kategorie-Gruppe "${group.name}" aktiviert`
        : `Kategorie-Gruppe "${group.name}" deaktiviert`,
      type: 'success'
    })
  } catch (error) {
    console.error('Error toggling group status:', error)
    showToast({
      message: error instanceof Error ? error.message : 'Fehler beim Ändern des Status',
      type: 'error'
    })
  }
}

const createCategory = (group: CategoryGroup) => {
  rsdStore.openCreate('category', { group })
}

const editCategory = (category: Category, group: CategoryGroup) => {
  rsdStore.openEdit('category', { ...category, group })
}

const deleteCategory = async (category: Category, group: CategoryGroup) => {
  if (confirm(`Sind Sie sicher, dass Sie die Kategorie "${category.name}" löschen möchten?`)) {
    try {
      await api.delete(`/tenant/category-groups/${group.id}/categories/${category.id}`)
      
      // Remove from local state
      const groupIndex = categoryGroups.value.findIndex(g => g.id === group.id)
      if (groupIndex !== -1 && categoryGroups.value[groupIndex].categories) {
        const categoryIndex = categoryGroups.value[groupIndex].categories!.findIndex(c => c.id === category.id)
        if (categoryIndex !== -1) {
          categoryGroups.value[groupIndex].categories!.splice(categoryIndex, 1)
        }
      }
      
      showToast({
        message: `Kategorie "${category.name}" erfolgreich gelöscht`,
        type: 'success'
      })
    } catch (error) {
      console.error('Error deleting category:', error)
      showToast({
        message: error instanceof Error ? error.message : 'Fehler beim Löschen der Kategorie',
        type: 'error'
      })
    }
  }
}

const toggleCategoryActive = async (category: Category, group: CategoryGroup) => {
  try {
    const response = await api.patch(`/tenant/category-groups/${group.id}/categories/${category.id}/toggle`)
    const updatedCategory = response.data.data || response.data
    
    // Update local state
    const groupIndex = categoryGroups.value.findIndex(g => g.id === group.id)
    if (groupIndex !== -1 && categoryGroups.value[groupIndex].categories) {
      const categoryIndex = categoryGroups.value[groupIndex].categories!.findIndex(c => c.id === category.id)
      if (categoryIndex !== -1) {
        categoryGroups.value[groupIndex].categories![categoryIndex] = updatedCategory
      }
    }
    
    showToast({
      message: updatedCategory.is_active 
        ? `Kategorie "${category.name}" aktiviert`
        : `Kategorie "${category.name}" deaktiviert`,
      type: 'success'
    })
  } catch (error) {
    console.error('Error toggling category status:', error)
    showToast({
      message: error instanceof Error ? error.message : 'Fehler beim Ändern des Status',
      type: 'error'
    })
  }
}

const configureAI = (group: any) => {
  console.log('Configure AI for group:', group)
  // TODO: Open AI configuration dialog/RSD
}

// Utility functions
const truncateText = (text: string, maxLength: number) => {
  if (text.length <= maxLength) return text
  return text.substring(0, maxLength) + '...'
}

const formatDate = (dateString: string) => {
  const date = new Date(dateString)
  return date.toLocaleDateString('de-DE', { 
    day: '2-digit', 
    month: '2-digit', 
    year: 'numeric' 
  })
}


// Event listener for RSD success
const handleTableRefresh = (event: any) => {
  console.log('Table refresh event:', event.detail)
  // Reload data when RSD operations succeed
  if (event.detail.entity === 'category-group' || event.detail.entity === 'category') {
    loadCategoryGroups()
  }
}

onMounted(() => {
  console.log('CategoryManagementView mounted')
  loadCategoryGroups()
  
  // Add event listener for table refresh
  window.addEventListener('table-refresh', handleTableRefresh)
})

// Clean up event listener when component unmounts
onBeforeUnmount(() => {
  window.removeEventListener('table-refresh', handleTableRefresh)
})
</script>

<style scoped>
.category-management {
  height: 100%;
  overflow: hidden; /* No own scrollbar */
  display: flex;
  flex-direction: column;
}

.category-content {
  flex: 1;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.category-header {
  position: sticky;
  top: 0;
  z-index: 10;
  background: rgb(var(--v-theme-background));
  padding: 16px 24px;
  border-bottom: 1px solid rgba(var(--v-border-color), 0.12);
  backdrop-filter: blur(10px);
  flex-shrink: 0;
}

.category-body {
  flex: 1;
  overflow-y: auto;
  overflow-x: hidden;
  padding: 16px 24px 24px;
}

.categories-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 16px;
}

.category-card {
  transition: all 0.2s ease;
  border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}

.category-card:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  transform: translateY(-2px);
}

.ai-preview {
  background: rgba(var(--v-theme-success), 0.05);
  border-radius: 6px;
  padding: 8px;
  margin-top: 8px;
}

.keywords-preview {
  margin-top: 8px;
}

.categories-section {
  background: rgba(var(--v-theme-surface), 0.5);
  border-radius: 8px;
  padding: 16px;
}

.ai-settings-section {
  background: rgba(var(--v-theme-primary), 0.02);
  border-radius: 8px;
  padding: 16px;
}

.v-expansion-panel {
  border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  border-radius: 8px !important;
}

.v-expansion-panel:not(:last-child) {
  margin-bottom: 16px;
}

.gap-1 {
  gap: 4px;
}
</style>