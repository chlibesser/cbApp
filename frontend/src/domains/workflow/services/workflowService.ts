import { apiClient } from '@/core/api/apiClient'

export interface WorkflowData {
  id?: string
  name: string
  description?: string
  nodes: any[]
  edges: any[]
  metadata?: any
  status?: 'draft' | 'active' | 'inactive'
}

export interface Workflow extends WorkflowData {
  id: string
  created_at: string
  updated_at: string
  created_by: string
  tenant_id: string
}

class WorkflowService {
  private readonly baseUrl = '/tenant/workflows'

  /**
   * Get all workflows for current tenant
   */
  async getWorkflows(): Promise<{ data: Workflow[]; total: number }> {
    const response = await apiClient.get(this.baseUrl)
    return response.data
  }

  /**
   * Get a specific workflow by ID
   */
  async getWorkflow(id: string): Promise<Workflow> {
    const response = await apiClient.get(`${this.baseUrl}/${id}`)
    return response.data.data
  }

  /**
   * Create a new workflow
   */
  async createWorkflow(data: WorkflowData): Promise<Workflow> {
    const response = await apiClient.post(this.baseUrl, data)
    return response.data.data
  }

  /**
   * Update an existing workflow
   */
  async updateWorkflow(id: string, data: Partial<WorkflowData>): Promise<Workflow> {
    const response = await apiClient.patch(`${this.baseUrl}/${id}`, data)
    return response.data.data
  }

  /**
   * Delete a workflow
   */
  async deleteWorkflow(id: string): Promise<void> {
    await apiClient.delete(`${this.baseUrl}/${id}`)
  }

  /**
   * Execute/run a workflow
   */
  async executeWorkflow(id: string): Promise<{ message: string; execution_id: string }> {
    const response = await apiClient.post(`${this.baseUrl}/${id}/execute`)
    return response.data
  }
}

export const workflowService = new WorkflowService()