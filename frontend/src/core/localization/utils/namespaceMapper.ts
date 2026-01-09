// NAMESPACE MAPPER - Route to Translation Namespace Mapping
 
const ROUTE_NAMESPACE_MAP: Record<string, string[]> = {
  
 
  login: ['auth.login', 'validation'],
  register: ['auth.register', 'validation'],

  // ADMIN ROUTES
  'admin-tenants': ['admin.tenants', 'admin.common', 'admin.layout', 'validation'],
  'admin-tenant-detail': [
    'admin.tenants',
    'admin.common',
    'admin.layout',
    'admin.roles',
    'admin.permissions',
    'validation',
  ],
  'admin-accounts': ['admin.accounts', 'admin.common', 'admin.layout', 'validation'],

  // TENANT ROUTES
  'tenant-users': ['tenant.users', 'validation'],
  'tenant-categories': ['tenant.categories', 'validation'],
  'tenant-documents': ['tenant.documents', 'validation'],
  'tenant-document-detail': ['tenant.documents', 'validation'],

  // Workflow List
  workflows: ['workflow', 'validation'],
  'workflow-builder': ['workflow', 'validation'],
  
  // Partner List
  partners: ['partner', 'validation'],
  'partner-detail': ['partner', 'validation'],

  // Dashboard Home
  dashboard: [],

  // Foundation/Landing Page
  foundation: [],
}

// Get namespaces for a given route
export function getNamespacesForRoute(routeName: string): string[] {
  const namespaces = ROUTE_NAMESPACE_MAP[routeName] || []

  if (namespaces.length === 0 && import.meta.env.DEV) {
    console.log(`[NamespaceMapper] No namespaces mapped for route: ${routeName}`)
  }

  return namespaces
}

// Get all unique namespaces from all routes
export function getAllNamespaces(): string[] {
  const allNamespaces = Object.values(ROUTE_NAMESPACE_MAP).flat()
  return Array.from(new Set(allNamespaces)) // Remove duplicates
}

// Check if a route has mapped namespaces
export function hasNamespaces(routeName: string): boolean {
  return !!ROUTE_NAMESPACE_MAP[routeName] && ROUTE_NAMESPACE_MAP[routeName].length > 0
}
