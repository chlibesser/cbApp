/**
 * Account Entity Configuration für ADT
 * Definiert Spalten und Verhalten der Account-Tabelle
 */

import type { EntityConfig } from '../../types/entity'

export const accountEntityConfig: EntityConfig = {
  name: 'account',
  apiEndpoint: '/admin/accounts',
  itemKey: 'id',
  searchPlaceholder: 'Accounts durchsuchen...',
  emptyText: 'Keine Accounts gefunden',
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
      visible: false // UUID nur bei Bedarf anzeigen
    },
    {
      key: 'username',
      label: 'Benutzername',
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
      key: 'email_verified_at',
      label: 'E-Mail verifiziert',
      type: 'boolean',
      sortable: true,
      filterable: true,
      filterType: 'select',
      filterOptions: [
        { value: true, text: 'Verifiziert' },
        { value: false, text: 'Nicht verifiziert' }
      ],
      width: 140,
      format: (value: string | null) => value ? 'Verifiziert' : 'Nicht verifiziert'
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
      key: 'tenants_count',
      label: 'Tenants',
      type: 'number',
      sortable: true,
      filterable: false,
      width: 100,
      align: 'center'
    },
    {
      key: 'last_login_at',
      label: 'Letzter Login',
      type: 'datetime',
      sortable: true,
      filterable: true,
      filterType: 'date',
      width: 160,
      minWidth: 140
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
      visible: false // Standardmäßig ausblenden
    }
  ]
}