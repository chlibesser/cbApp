import type { Tenant, CreateTenantRequest, UpdateTenantRequest } from '../types'
import type { PaginatedResponse } from '../../../core/api'
import { apiClient } from '../../../core/api'

export class TenantService {
  async getTenants(page: number = 1): Promise<PaginatedResponse<Tenant>> {
    const response = await apiClient.get<PaginatedResponse<Tenant>>(`/admin/tenants?page=${page}`)
    return response.data
  }

  async getTenant(id: number): Promise<Tenant> {
    const response = await apiClient.get<Tenant>(`/admin/tenants/${id}`)
    return response.data
  }

  async createTenant(data: CreateTenantRequest): Promise<Tenant> {
    const response = await apiClient.post<Tenant>('/admin/tenants', data)
    return response.data
  }

  async updateTenant(id: number, data: UpdateTenantRequest): Promise<Tenant> {
    const response = await apiClient.put<Tenant>(`/admin/tenants/${id}`, data)
    return response.data
  }

  async deleteTenant(id: number): Promise<void> {
    await apiClient.delete(`/admin/tenants/${id}`)
  }
}

export const tenantService = new TenantService()