/**
 * Account Entity Configuration für ADT
 * Stores translation keys that will be translated in components
 */

import type { EntityConfig } from '../../types/entity'

export const accountEntityConfig: EntityConfig = {
  name: 'account',
  apiEndpoint: '/admin/accounts',
  itemKey: 'id',
  searchPlaceholder: 'admin.accounts.search_placeholder',
  emptyText: 'admin.accounts.empty_text',
  defaultSort: {
    field: 'created_at',
    direction: 'desc'
  },
  itemsPerPage: 25,
  fields: [
    {
      key: 'id',
      title: 'admin.common.fields.id',
      type: 'text',
      sortable: true,
      filterable: false,
      width: 100,
      visible: false
    },
    {
      key: 'username',
      title: 'admin.accounts.table.username',
      type: 'text',
      sortable: true,
      filterable: true,
      filterType: 'text',
      width: 150,
      minWidth: 120,
      required: true
    },
    {
      key: 'email',
      title: 'admin.accounts.table.email',
      type: 'email',
      sortable: true,
      filterable: true,
      filterType: 'text',
      width: 200,
      minWidth: 150,
      required: true
    },
    {
      key: 'email_verified_at',
      title: 'admin.accounts.table.verified',
      type: 'boolean',
      sortable: true,
      filterable: true,
      filterType: 'select',
      filterOptions: [
        { value: true, text: 'admin.accounts.verification_status.verified' },
        { value: false, text: 'admin.accounts.verification_status.not_verified' }
      ],
      width: 140
    },
    {
      key: 'system_role',
      title: 'admin.accounts.table.role',
      type: 'select',
      sortable: true,
      filterable: true,
      filterType: 'select',
      filterOptions: [
        { value: 'global_admin', text: 'admin.accounts.roles.global_admin' },
        { value: 'tenant_admin', text: 'admin.accounts.roles.tenant_admin' },
        { value: 'tenant_member', text: 'admin.accounts.roles.member' }
      ],
      width: 140
    },
    {
      key: 'is_active',
      title: 'admin.common.fields.status',
      type: 'boolean',
      sortable: true,
      filterable: true,
      filterType: 'select',
      filterOptions: [
        { value: true, text: 'admin.common.status.active' },
        { value: false, text: 'admin.common.status.inactive' }
      ],
      width: 100
    },
    {
      key: 'tenants_count',
      title: 'admin.common.fields.tenants',
      type: 'number',
      sortable: true,
      filterable: false,
      width: 100,
      align: 'center'
    },
    {
      key: 'last_login_at',
      title: 'admin.accounts.table.last_login',
      type: 'datetime',
      sortable: true,
      filterable: true,
      filterType: 'date',
      width: 160,
      minWidth: 140
    },
    {
      key: 'created_at',
      title: 'admin.common.fields.created_at',
      type: 'datetime',
      sortable: true,
      filterable: true,
      filterType: 'date',
      width: 160,
      minWidth: 140
    },
    {
      key: 'updated_at',
      title: 'admin.common.fields.updated_at',
      type: 'datetime',
      sortable: true,
      filterable: false,
      width: 160,
      minWidth: 140,
      visible: false
    }
  ]
}
