/**
 * Tenant Entity Configuration für ADT
 * Definiert Spalten und Verhalten der Tenant-Tabelle
 */

import type { EntityConfig } from '../../types/entity'
export const tenantEntityConfig: EntityConfig = {
  name: 'tenant',
  apiEndpoint: '/admin/tenants',
  itemKey: 'id',
  searchPlaceholder: 'admin.tenants.search_placeholder',
  emptyText: 'admin.tenants.empty_text',
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
      visible: false // UUID nur bei Bedarf anzeigen
    },
    {
      key: 'name',
      title: 'admin.tenants.table.name',
      type: 'text',
      sortable: true,
      filterable: true,
      filterType: 'text',
      width: 200,
      minWidth: 150,
      required: true
    },
    {
      key: 'slug',
      title: 'admin.tenants.table.slug',
      type: 'text',
      sortable: true,
      filterable: true,
      filterType: 'text',
      width: 180,
      minWidth: 120
    },
    {
      key: 'description',
      title: 'admin.common.fields.description',
      type: 'text',
      sortable: false,
      filterable: true,
      filterType: 'text',
      width: 200,
      minWidth: 150
    },
    {
      key: 'is_personal',
      title: 'admin.common.fields.type',
      type: 'boolean',
      sortable: true,
      filterable: true,
      filterType: 'select',
      filterOptions: [
        { value: false, text: 'admin.tenants.types.company' },
        { value: true, text: 'admin.tenants.types.personal' }
      ],
      width: 120,
      format: (value: boolean) => value ? 'admin.tenants.types.personal' : 'admin.tenants.types.company'
    },
    {
      key: 'is_active',
      title: 'admin.tenants.table.status',
      type: 'boolean',
      sortable: true,
      filterable: true,
      filterType: 'select',
      filterOptions: [
        { value: true, text: 'admin.tenants.status.active' },
        { value: false, text: 'admin.tenants.status.inactive' }
      ],
      width: 100,
      format: (value: boolean) => value ? 'admin.tenants.status.active' : 'admin.tenants.status.inactive'
    },
    {
      key: 'users_count',
      title: 'admin.tenants.table.users_count',
      type: 'number',
      sortable: true,
      filterable: false,
      width: 100,
      align: 'center'
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
      visible: false // Standardmäßig ausblenden
    }
  ]
}