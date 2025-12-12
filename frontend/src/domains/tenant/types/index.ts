// API Response Types
export interface ApiResponse<T> {
  data: T
  message?: string
  meta?: {
    total?: number
    page?: number
    per_page?: number
  }
}

// Category Group Types
export interface CategoryGroup {
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

// Category Types  
export interface Category {
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

// Form Data Types
export interface CategoryGroupFormData {
  name: string
  description?: string
  icon?: string
  color?: string
  selection_type: 'single' | 'multi'
  is_required: boolean
  is_active: boolean
  ai_enabled: boolean
  ai_prompt_context?: string
  ai_confidence_threshold: number
}

export interface CategoryFormData {
  name: string
  description?: string
  icon?: string
  color?: string
  is_default: boolean
  is_active: boolean
  ai_positive_description?: string
  ai_negative_description?: string
  ai_keywords?: string[]
  custom_prompt?: string
}

// Enum-like constants
export const SelectionType = {
  SINGLE: 'single' as const,
  MULTI: 'multi' as const,
} as const

export type SelectionTypeValue = typeof SelectionType[keyof typeof SelectionType]

// Color options for UI
export const CategoryColors = [
  { title: 'Grey', value: 'grey' },
  { title: 'Primary', value: 'primary' },
  { title: 'Success', value: 'success' },
  { title: 'Info', value: 'info' },
  { title: 'Warning', value: 'warning' },
  { title: 'Error', value: 'error' },
  { title: 'Purple', value: 'purple' },
  { title: 'Blue', value: 'blue' },
  { title: 'Green', value: 'green' },
  { title: 'Orange', value: 'orange' },
  { title: 'Red', value: 'red' },
] as const

export const GroupColors = [
  { title: 'Primary', value: 'primary' },
  { title: 'Success', value: 'success' },
  { title: 'Info', value: 'info' },
  { title: 'Warning', value: 'warning' },
  { title: 'Error', value: 'error' },
  { title: 'Purple', value: 'purple' },
  { title: 'Blue', value: 'blue' },
  { title: 'Green', value: 'green' },
  { title: 'Orange', value: 'orange' },
  { title: 'Red', value: 'red' },
] as const