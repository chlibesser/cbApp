/**
 * Profile Entity Configuration für ADT
 * Definiert Spalten und Verhalten der Profile-Tabelle
 */

import type { EntityConfig } from '../../types/entity'

export const profileEntityConfig: EntityConfig = {
  name: 'profile',
  apiEndpoint: '/admin/profiles',
  itemKey: 'id',
  searchPlaceholder: 'Profile durchsuchen...',
  emptyText: 'Keine Profile gefunden',
  defaultSort: {
    field: 'created_at',
    direction: 'desc'
  },
  itemsPerPage: 25,
  fields: [
    {
      key: 'id',
      label: 'ID',
      type: 'text',
      sortable: true,
      filterable: false,
      width: 100,
      visible: false
    },
    {
      key: 'first_name',
      label: 'Vorname',
      type: 'text',
      sortable: true,
      filterable: true,
      filterType: 'text',
      width: 150,
      minWidth: 120,
      required: true
    },
    {
      key: 'last_name',
      label: 'Nachname',
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
      label: 'E-Mail',
      type: 'email',
      sortable: true,
      filterable: true,
      filterType: 'text',
      width: 200,
      minWidth: 150,
      required: true
    },
    {
      key: 'phone',
      label: 'Telefon',
      type: 'phone',
      sortable: false,
      filterable: true,
      filterType: 'text',
      width: 140,
      minWidth: 120
    },
    {
      key: 'system_role',
      label: 'System-Rolle',
      type: 'select',
      sortable: true,
      filterable: true,
      filterType: 'select',
      filterOptions: [
        { value: 'global_admin', text: 'Global Admin' },
        { value: 'tenant_admin', text: 'Tenant Admin' },
        { value: 'tenant_member', text: 'Tenant Member' }
      ],
      width: 140,
      format: (value: string) => {
        const roleMap: Record<string, string> = {
          'global_admin': 'Global Admin',
          'tenant_admin': 'Tenant Admin',
          'tenant_member': 'Tenant Member'
        }
        return roleMap[value] || value
      }
    },
    {
      key: 'tenant.name',
      label: 'Tenant',
      type: 'text',
      sortable: true,
      filterable: true,
      filterType: 'text',
      width: 150,
      minWidth: 120
    },
    {
      key: 'is_active',
      label: 'Status',
      type: 'boolean',
      sortable: true,
      filterable: true,
      filterType: 'select',
      filterOptions: [
        { value: true, text: 'Aktiv' },
        { value: false, text: 'Inaktiv' }
      ],
      width: 100,
      format: (value: boolean) => value ? 'Aktiv' : 'Inaktiv'
    },
    {
      key: 'created_at',
      label: 'Erstellt am',
      type: 'datetime',
      sortable: true,
      filterable: true,
      filterType: 'date',
      width: 160,
      minWidth: 140
    },
    {
      key: 'updated_at',
      label: 'Aktualisiert am',
      type: 'datetime',
      sortable: true,
      filterable: false,
      width: 160,
      minWidth: 140,
      visible: false
    }
  ]
}