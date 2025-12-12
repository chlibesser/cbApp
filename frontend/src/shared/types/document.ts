import type { Category } from './category'
import type { Account } from './auth'

export interface Document {
  id: string
  tenant_id: string
  uploaded_by: string
  original_filename: string
  stored_filename: string
  mime_type: string
  file_size: number
  file_hash: string
  storage_path: string
  title?: string
  description?: string
  metadata?: Record<string, any>
  
  // AI-enhanced fields
  ai_classification?: Record<string, any>
  ai_metadata?: Record<string, any>
  ai_tags?: string[]
  ai_summary?: string
  ai_confidence_score?: number
  ai_processed_at?: string
  ai_processing_status: 'pending' | 'processing' | 'completed' | 'failed'
  
  // Content extraction
  extracted_text?: string
  structured_data?: Record<string, any>
  language?: string
  page_count?: number
  
  // Access control
  visibility: 'public' | 'internal' | 'confidential' | 'restricted'
  access_permissions?: Record<string, any>
  requires_approval: boolean
  is_encrypted: boolean
  
  // Document lifecycle
  status: 'draft' | 'active' | 'archived' | 'deleted'
  expires_at?: string
  archived_at?: string
  archived_by?: string
  
  // Versioning
  parent_document_id?: string
  version: number
  is_latest_version: boolean
  
  // Workflow integration
  workflow_data?: Record<string, any>
  workflow_status?: string
  workflow_started_at?: string
  workflow_completed_at?: string
  
  // Analytics
  download_count: number
  view_count: number
  last_accessed_at?: string
  last_accessed_by?: string
  
  // Timestamps
  created_at: string
  updated_at: string
  deleted_at?: string
  
  // Relationships
  uploader?: Account
  archived_by_user?: Account
  last_accessed_by_user?: Account
  categories?: Category[]
  shares?: DocumentShare[]
  activities?: DocumentActivity[]
  parent_document?: Document
  versions?: Document[]
  
  // Computed properties (added by frontend)
  file_size_human?: string
  is_image?: boolean
  is_pdf?: boolean
  is_processable?: boolean
  ai_processing_complete?: boolean
  has_high_confidence_ai?: boolean
}

export interface DocumentShare {
  id: string
  document_id: string
  shared_by: string
  tenant_id: string
  shared_with_user?: string
  shared_with_email?: string
  permission_level: 'view' | 'download' | 'comment' | 'edit'
  specific_permissions?: string[]
  share_token?: string
  requires_password: boolean
  allow_public_access: boolean
  expires_at?: string
  download_limit?: number
  view_limit?: number
  downloads_used: number
  views_used: number
  notify_on_access: boolean
  notify_on_download: boolean
  notification_email?: string
  is_active: boolean
  first_accessed_at?: string
  last_accessed_at?: string
  access_log?: AccessLogEntry[]
  share_message?: string
  share_context?: Record<string, any>
  created_at: string
  updated_at: string
  deleted_at?: string
  
  // Relationships
  document?: Document
  shared_by_user?: Account
  shared_with_user_data?: Account
  
  // Computed properties
  is_expired?: boolean
  is_limit_reached?: boolean
  is_accessible?: boolean
  share_url?: string
  recipient_display_name?: string
  share_type?: 'internal' | 'external' | 'public'
}

export interface DocumentActivity {
  id: string
  document_id: string
  tenant_id: string
  actor_id?: string
  actor_type: 'user' | 'system' | 'ai' | 'external'
  actor_name?: string
  activity_type: string
  activity_description: string
  activity_data?: Record<string, any>
  ip_address?: string
  user_agent?: string
  session_id?: string
  request_metadata?: Record<string, any>
  activity_category: 'access' | 'modification' | 'sharing' | 'workflow' | 'ai_processing' | 'admin' | 'security'
  activity_level: 'info' | 'warning' | 'error' | 'critical'
  related_entity_type?: string
  related_entity_id?: string
  related_entities?: Record<string, any>
  processing_time_ms?: number
  was_successful: boolean
  error_code?: string
  error_message?: string
  occurred_at: string
  created_at: string
  updated_at: string
  
  // Relationships
  document?: Document
  actor?: Account
  
  // Computed properties
  actor_display_name?: string
  is_system_action?: boolean
  is_user_action?: boolean
  is_external_action?: boolean
  formatted_occurred_at?: string
  activity_icon?: string
  activity_color?: string
}

export interface AccessLogEntry {
  type: 'view' | 'download'
  timestamp: string
  ip: string
  user_agent: string
  metadata?: Record<string, any>
}

export interface DocumentCategoryAssignment {
  id: string
  document_id: string
  category_id: string
  tenant_id: string
  assignment_type: 'manual' | 'ai_auto' | 'ai_assisted' | 'rule_based' | 'bulk'
  confidence_score?: number
  assigned_by?: string
  assignment_reason?: string
  assignment_context?: Record<string, any>
  assigned_at: string
  created_at: string
  updated_at: string
  
  // Relationships
  document?: Document
  category?: Category
  assigned_by_user?: Account
}

export interface DocumentStatistics {
  total_documents?: number
  total_size?: number
  documents_by_type?: Record<string, number>
  documents_by_month?: Record<string, number>
  ai_processing_status?: Record<string, number>
}

export interface DocumentUploadSettings {
  visibility: 'public' | 'internal' | 'confidential' | 'restricted'
  expires_at?: string
  apply_to_all: boolean
  auto_categorize: boolean
  manual_category_id?: string
}

export interface DocumentFileSettings {
  title: string
  description: string
}

export interface DocumentSearchFilters {
  search?: string
  mime_type?: string
  visibility?: string
  category_id?: string
  uploaded_by?: string
  date_from?: string
  date_to?: string
  status?: string
  ai_processing_status?: string
}

export interface DocumentBulkAction {
  action: 'categorize' | 'delete' | 'archive' | 'restore'
  document_ids: string[]
  parameters?: Record<string, any>
}

export interface DocumentBulkResult {
  success: number
  failed: number
  errors: Array<{
    document_id: string
    filename: string
    error: string
  }>
}

// API Response types
export interface DocumentListResponse {
  data: Document[]
  meta: {
    total: number
    per_page: number
    current_page: number
    last_page: number
  }
}

export interface DocumentResponse {
  data: Document
  message?: string
}

export interface DocumentStatisticsResponse {
  data: DocumentStatistics
}

export interface DocumentBulkResponse {
  message: string
  data: DocumentBulkResult
}