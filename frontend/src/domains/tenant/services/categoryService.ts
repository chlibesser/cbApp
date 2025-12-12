import { useApi } from '../../../core/api'
import type { CategoryGroup, Category, ApiResponse } from '../types'

const api = useApi()

export interface CategoryGroupFilters {
  include_inactive?: boolean
}

export interface CategoryFilters {
  include_inactive?: boolean
}

class CategoryGroupService {
  private basePath = '/tenant/category-groups'

  async getAll(filters: CategoryGroupFilters = {}): Promise<CategoryGroup[]> {
    const params = new URLSearchParams()
    if (filters.include_inactive) {
      params.append('include_inactive', 'true')
    }

    const response = await api.get<ApiResponse<CategoryGroup[]>>(
      `${this.basePath}?${params.toString()}`
    )
    return response.data.data
  }

  async getById(id: string): Promise<CategoryGroup> {
    const response = await api.get<ApiResponse<CategoryGroup>>(`${this.basePath}/${id}`)
    return response.data.data
  }

  async create(data: Partial<CategoryGroup>): Promise<CategoryGroup> {
    const response = await api.post<ApiResponse<CategoryGroup>>(this.basePath, data)
    return response.data.data
  }

  async update(id: string, data: Partial<CategoryGroup>): Promise<CategoryGroup> {
    const response = await api.put<ApiResponse<CategoryGroup>>(`${this.basePath}/${id}`, data)
    return response.data.data
  }

  async delete(id: string): Promise<void> {
    await api.delete(`${this.basePath}/${id}`)
  }

  async toggle(id: string): Promise<CategoryGroup> {
    const response = await api.patch<ApiResponse<CategoryGroup>>(`${this.basePath}/${id}/toggle`)
    return response.data.data
  }

  async reorder(groups: Array<{ id: string; display_order: number }>): Promise<void> {
    await api.post(`${this.basePath}/reorder`, { groups })
  }
}

class CategoryService {
  async getAll(groupId: string, filters: CategoryFilters = {}): Promise<Category[]> {
    const params = new URLSearchParams()
    if (filters.include_inactive) {
      params.append('include_inactive', 'true')
    }

    const response = await api.get<ApiResponse<Category[]>>(
      `/tenant/category-groups/${groupId}/categories?${params.toString()}`
    )
    return response.data.data
  }

  async getById(groupId: string, id: string): Promise<Category> {
    const response = await api.get<ApiResponse<Category>>(
      `/tenant/category-groups/${groupId}/categories/${id}`
    )
    return response.data.data
  }

  async create(groupId: string, data: Partial<Category>): Promise<Category> {
    const response = await api.post<ApiResponse<Category>>(
      `/tenant/category-groups/${groupId}/categories`,
      data
    )
    return response.data.data
  }

  async update(groupId: string, id: string, data: Partial<Category>): Promise<Category> {
    const response = await api.put<ApiResponse<Category>>(
      `/tenant/category-groups/${groupId}/categories/${id}`,
      data
    )
    return response.data.data
  }

  async delete(groupId: string, id: string): Promise<void> {
    await api.delete(`/tenant/category-groups/${groupId}/categories/${id}`)
  }

  async toggle(groupId: string, id: string): Promise<Category> {
    const response = await api.patch<ApiResponse<Category>>(
      `/tenant/category-groups/${groupId}/categories/${id}/toggle`
    )
    return response.data.data
  }

  async setDefault(groupId: string, id: string): Promise<Category> {
    const response = await api.patch<ApiResponse<Category>>(
      `/tenant/category-groups/${groupId}/categories/${id}/set-default`
    )
    return response.data.data
  }

  async reorder(groupId: string, categories: Array<{ id: string; display_order: number }>): Promise<void> {
    await api.post(`/tenant/category-groups/${groupId}/categories/reorder`, { categories })
  }
}

export const categoryService = {
  groups: new CategoryGroupService(),
  categories: new CategoryService(),
}