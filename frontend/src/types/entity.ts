/**
 * TypeScript Definitionen für Entity-Konfigurationen
 * Portiert von V0 nach V1 - erweitert für cbApp V1
 */

import type { TableColumn, ColumnType, FilterType } from './table'

export interface EntityField {
  key: string
  title: string
  type: ColumnType
  sortable?: boolean
  filterable?: boolean
  filterType?: FilterType
  filterOptions?: Array<{ value: any; text: string }>
  width?: number
  minWidth?: number
  align?: 'start' | 'center' | 'end'
  format?: (value: any) => string
  visible?: boolean
  required?: boolean
  resizable?: boolean
}

export interface EntityConfig {
  name: string
  apiEndpoint: string
  itemKey: string
  fields: EntityField[]
  searchPlaceholder?: string
  emptyText?: string
  defaultSort?: {
    field: string
    direction: 'asc' | 'desc'
  }
  itemsPerPage?: number
}

export interface PaginatedResponse<T = any> {
  data: T[]
  meta: {
    current_page: number
    from: number
    last_page: number
    per_page: number
    to: number
    total: number
  }
  links: {
    first: string
    last: string
    next?: string
    prev?: string
  }
}

// V1 spezifische Entity-Typen

export interface Account {
  id: string
  username: string
  email: string
  email_verified_at?: string
  system_role: string
  is_active: boolean
  created_at: string
  updated_at: string
}

export interface Tenant {
  id: string
  name: string
  slug: string
  description?: string
  logo_url?: string
  is_personal: boolean
  settings: Record<string, any>
  is_active: boolean
  created_at: string
  updated_at: string
}

export interface Profile {
  id: string
  tenant_id: string
  account_id: string
  first_name: string
  last_name: string
  email: string
  phone?: string
  avatar_url?: string
  system_role: string
  is_active: boolean
  created_at: string
  updated_at: string
  tenant?: Tenant
  account?: Account
}

export interface Permission {
  id: string
  key: string
  name: string
  description?: string
  category: string
  created_at: string
  updated_at: string
}

export interface Role {
  id: string
  tenant_id: string
  name: string
  description?: string
  created_at: string
  updated_at: string
  tenant?: Tenant
  permissions?: Permission[]
}

// Entity-zu-TableColumn Converter
export function entityFieldsToColumns(fields: EntityField[]): TableColumn[] {
  return fields.map(field => ({
    key: field.key,
    title: field.title,
    type: field.type,
    sortable: field.sortable ?? true,
    filterable: field.filterable ?? true,
    filterType: field.filterType,
    filterOptions: field.filterOptions,
    width: field.width,
    minWidth: field.minWidth ?? 100,
    align: field.align ?? 'start',
    format: field.format,
    resizable: field.resizable ?? true,
    visible: field.visible ?? true,
    required: field.required ?? false
  }))
}