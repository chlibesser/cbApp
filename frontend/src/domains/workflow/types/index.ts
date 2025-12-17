export interface BaseNodeData {
  id: string
  label: string
  description?: string
}

export interface TriggerNodeData extends BaseNodeData {
  triggerType: 'event' | 'schedule' | 'webhook' | 'manual' | 'file'
  eventType?: string
  schedule?: string
  conditions?: any[]
  webhookUrl?: string
  filePattern?: string
}

export interface ActionNodeData extends BaseNodeData {
  actionType: 'api' | 'email' | 'database' | 'file' | 'notification' | 'custom'
  method?: string
  endpoint?: string
  parameters?: Record<string, any>
  headers?: Array<{ key: string; value: string }>
  body?: {
    type: 'json' | 'form' | 'raw'
    json: string
    formData: Array<{ key: string; value: string }>
    raw: string
  }
  status?: 'ready' | 'running' | 'completed' | 'failed'
  timeout?: number
  retries?: number
}

export interface ConditionNodeData extends BaseNodeData {
  conditionType: 'if-else' | 'switch' | 'loop' | 'exists' | 'compare'
  expression?: string
  operator?: 'equals' | 'greater' | 'less' | 'contains' | 'exists' | 'not_exists'
  leftValue?: any
  rightValue?: any
  caseSensitive?: boolean
}

export interface EndNodeData extends BaseNodeData {
  endType: 'success' | 'failure' | 'stop' | 'terminate'
  message?: string
  returnValue?: any
  cleanup?: boolean
}

export type WorkflowNodeData = 
  | TriggerNodeData 
  | ActionNodeData 
  | ConditionNodeData 
  | EndNodeData

export interface WorkflowNode {
  id: string
  type: 'trigger' | 'action' | 'condition' | 'end'
  position: { x: number; y: number }
  data: WorkflowNodeData
  selected?: boolean
  dragging?: boolean
}

export interface WorkflowEdge {
  id: string
  source: string
  target: string
  sourceHandle?: string
  targetHandle?: string
  type?: 'default' | 'conditional'
  label?: string
  animated?: boolean
}

export interface WorkflowConfig {
  id: string
  name: string
  description?: string
  nodes: WorkflowNode[]
  edges: WorkflowEdge[]
  version: string
  status: 'draft' | 'active' | 'inactive' | 'archived'
  metadata?: {
    nodeCount: number
    edgeCount: number
    lastModified: string
    createdBy: string
    tags?: string[]
  }
}

// Node Type Registry for Vue Flow
export const NODE_TYPES = {
  trigger: 'TriggerNode',
  action: 'ActionNode', 
  condition: 'ConditionNode',
  end: 'EndNode'
} as const

// Node Colors
export const NODE_COLORS = {
  trigger: '#4caf50',
  action: '#2196f3',
  condition: '#ff9800', 
  end: '#f44336'
} as const

// Default Node Sizes
export const NODE_SIZES = {
  trigger: { width: 120, height: 60 },
  action: { width: 140, height: 80 },
  condition: { width: 120, height: 80 },
  end: { width: 80, height: 80 }
} as const