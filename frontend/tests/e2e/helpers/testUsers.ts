export interface TestUser {
  identifier: string
  password: string
  role?: string
  tenant?: string
  tenants?: string[]
  expectedName?: string
}

export const testUsers = {
  globalAdmin: {
    identifier: 'globaladmin@cbapp.ch',
    password: 'password123',
    role: 'global_admin',
    expectedName: 'Global Administrator'
  },
  
  tenantAdmin: {
    identifier: 'tenantadmin@cbapp.ch',
    password: 'password123',
    tenant: 'test-firma-gmbh',
    role: 'admin',
    expectedName: 'Tenant Admin'
  },
  
  regularUser: {
    identifier: 'user@cbapp.ch',
    password: 'password123',
    tenant: 'test-firma-gmbh',
    role: 'member',
    expectedName: 'Regular User'
  },
  
  multiTenantUser: {
    identifier: 'multiuser@cbapp.ch',
    password: 'password123',
    tenants: ['test-firma-gmbh', 'andere-firma-ag'],
    expectedName: 'Multi Tenant User'
  },

  // Quick Login Test Users (für Development)
  quickLoginAdmin: {
    identifier: 'admin',
    password: 'password123',
    role: 'global_admin',
    expectedName: 'Quick Admin'
  },

  quickLoginUser: {
    identifier: 'testuser',
    password: 'password123',
    tenant: 'test-firma-gmbh',
    role: 'member',
    expectedName: 'Quick User'
  }
}

export type TestUserKey = keyof typeof testUsers