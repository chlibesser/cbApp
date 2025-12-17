import { apiClient } from '@/core/api/apiClient'
import type { Partner, PartnerForm, PartnerFilters, PartnerContact, PartnerInteraction } from '../types'

export const partnerService = {
  // Partner CRUD
  async getPartners(filters?: PartnerFilters & { page?: number; per_page?: number }) {
    let endpoint = '/tenant/partners'
    
    // Build query string from filters
    if (filters && Object.keys(filters).length > 0) {
      const params = new URLSearchParams()
      Object.entries(filters).forEach(([key, value]) => {
        if (value !== undefined && value !== null) {
          if (Array.isArray(value)) {
            value.forEach(v => params.append(key, v.toString()))
          } else {
            params.append(key, value.toString())
          }
        }
      })
      const queryString = params.toString()
      if (queryString) {
        endpoint += `?${queryString}`
      }
    }
    
    const { data } = await apiClient.get(endpoint)
    return data
  },

  async getPartner(id: string): Promise<Partner> {
    const { data } = await apiClient.get(`/tenant/partners/${id}`)
    return data
  },

  async createPartner(partner: PartnerForm): Promise<Partner> {
    const { data } = await apiClient.post('/tenant/partners', partner)
    return data.partner
  },

  async updatePartner(id: string, partner: Partial<PartnerForm>): Promise<Partner> {
    const { data } = await apiClient.patch(`/tenant/partners/${id}`, partner)
    return data.partner
  },

  async deletePartner(id: string): Promise<void> {
    await apiClient.delete(`/tenant/partners/${id}`)
  },

  // Search & Statistics
  async searchPartners(query: string, limit?: number) {
    let endpoint = '/tenant/partners/search'
    const params = new URLSearchParams()
    params.append('query', query)
    if (limit) {
      params.append('limit', limit.toString())
    }
    endpoint += `?${params.toString()}`
    
    const { data } = await apiClient.get(endpoint)
    return data
  },

  async getStatistics() {
    const { data } = await apiClient.get('/tenant/partners/statistics')
    return data
  },

  async getPartnersNeedingAttention(days: number = 90) {
    const endpoint = `/tenant/partners/needs-attention?days=${days}`
    const { data } = await apiClient.get(endpoint)
    return data
  },

  // Contacts
  async createContact(partnerId: string, contact: Partial<PartnerContact>) {
    const { data } = await apiClient.post(`/tenant/partners/${partnerId}/contacts`, contact)
    return data
  },

  async updateContact(partnerId: string, contactId: string, contact: Partial<PartnerContact>) {
    const { data } = await apiClient.patch(`/tenant/partners/${partnerId}/contacts/${contactId}`, contact)
    return data
  },

  async deleteContact(partnerId: string, contactId: string): Promise<void> {
    await apiClient.delete(`/tenant/partners/${partnerId}/contacts/${contactId}`)
  },

  // Interactions
  async createInteraction(partnerId: string, interaction: Partial<PartnerInteraction>) {
    const { data } = await apiClient.post(`/tenant/partners/${partnerId}/interactions`, interaction)
    return data
  },

  async getInteractions(partnerId: string, limit?: number) {
    let endpoint = `/tenant/partners/${partnerId}/interactions`
    if (limit) {
      endpoint += `?limit=${limit}`
    }
    const { data } = await apiClient.get(endpoint)
    return data
  }
}