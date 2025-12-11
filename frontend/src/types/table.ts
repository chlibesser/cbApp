/**
 * TypeScript Definitionen für Advanced Data Table (ADT)
 * Portiert von V0 nach V1
 */

export type FilterType = 'text' | 'select' | 'boolean' | 'date' | 'datetime' | 'number'
export type SortDirection = 'asc' | 'desc'
export type ColumnType = 'text' | 'email' | 'phone' | 'url' | 'boolean' | 'select' | 'date' | 'datetime' | 'number' | 'currency' | 'percentage'

export interface TableColumn {
  key: string
  title: string
  type?: ColumnType
  sortable?: boolean
  filterable?: boolean
  filterType?: FilterType
  filterOptions?: Array<{ value: any; text: string }>
  width?: number
  minWidth?: number
  align?: 'start' | 'center' | 'end'
  format?: (value: any) => string
  resizable?: boolean
  visible?: boolean
  required?: boolean
}

export interface TableState {
  page: number
  itemsPerPage: number
  sortBy: string | null
  sortDirection: SortDirection | null
  filters: Record<string, any>
}

export interface FilterState {
  [key: string]: {
    value: any
    type: FilterType
    options?: Array<{ value: any; text: string }>
  }
}

export interface PaginatedResponse<T = any> {
  data: T[]
  current_page: number
  from: number
  last_page: number
  per_page: number
  to: number
  total: number
  first_page_url: string
  last_page_url: string
  next_page_url?: string
  prev_page_url?: string
  path: string
  links: Array<{
    url?: string
    label: string
    page?: number
    active: boolean
  }>
}

export interface TableProps {
  columns?: TableColumn[]
  entityConfig?: import('./entity').EntityConfig
  apiEndpoint?: string
  itemKey?: string
  itemsPerPage?: number
  searchPlaceholder?: string
  showSearch?: boolean
  showFilters?: boolean
  showPagination?: boolean
  showColumnSettings?: boolean
  allowColumnResize?: boolean
  allowRowSelection?: boolean
  dense?: boolean
  height?: string | number
  loading?: boolean
  emptyText?: string
  errorText?: string
}

export interface TableEmits {
  'item-selected': [item: any]
  'item-double-click': [item: any]
  'items-selected': [items: any[]]
  'create': []
  'edit': [item: any]
  'delete': [item: any]
  'refresh': []
  'update:count': [count: number]
}

export interface ColumnResizeState {
  isResizing: boolean
  startX: number
  startWidth: number
  column: TableColumn
}

export interface TableSettings {
  visibleColumns: string[]
  columnWidths: Record<string, number>
  filtersVisible: boolean
  itemsPerPage: number
}