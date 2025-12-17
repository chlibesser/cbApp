export interface Partner {
  id: string
  tenant_id: string
  category_id: string
  name: string
  description?: string
  website?: string
  email?: string
  phone?: string
  status: PartnerStatus
  tags?: string[]
  custom_fields?: Record<string, any>
  notes?: string
  created_at: string
  updated_at: string
  deleted_at?: string
  
  // Relations
  category?: Category
  contacts?: PartnerContact[]
  interactions?: PartnerInteraction[]
}

export interface PartnerContact {
  id: string
  partner_id: string
  name: string
  position?: string
  email?: string
  phone?: string
  is_primary: boolean
  responsibilities?: string
  notes?: string
  created_at: string
  updated_at: string
}

export interface PartnerInteraction {
  id: string
  partner_id: string
  user_id: string
  type: InteractionType
  subject: string
  content?: string
  interaction_date: string
  participants?: string[]
  attachments?: any[]
  follow_up_date?: string
  created_at: string
  updated_at: string
  
  // Relations
  user?: any
}

export enum PartnerStatus {
  ACTIVE = 'active',
  INACTIVE = 'inactive',
  POTENTIAL = 'potential'
}

export enum InteractionType {
  MEETING = 'meeting',
  CALL = 'call',
  EMAIL = 'email',
  NOTE = 'note',
  DOCUMENT = 'document'
}

export interface Category {
  id: string
  name: string
  description?: string
  color?: string
}

// Form interfaces
export interface PartnerForm {
  category_id: string
  name: string
  description?: string
  website?: string
  email?: string
  phone?: string
  status?: PartnerStatus
  tags?: string[]
  custom_fields?: Record<string, any>
  notes?: string
  primary_contact?: {
    name: string
    position?: string
    email?: string
    phone?: string
  }
}

// Filter interfaces
export interface PartnerFilters {
  search?: string
  status?: PartnerStatus
  category_id?: string
  tags?: string[]
  sort_by?: string
  sort_order?: 'asc' | 'desc'
}