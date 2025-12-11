/**
 * Tenant Entity Configuration für ADT
 * Definiert Spalten und Verhalten der Tenant-Tabelle
 */

import type { EntityConfig } from '../../types/entity'

export const tenantEntityConfig: EntityConfig = {
  name: 'tenant',
  apiEndpoint: '/admin/tenants',
  itemKey: 'id',
  searchPlaceholder: 'Tenants durchsuchen...',
  emptyText: 'Keine Tenants gefunden',
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
      key: 'name',
      label: 'Name',
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
      label: 'Slug',
      type: 'text',
      sortable: true,
      filterable: true,
      filterType: 'text',
      width: 180,
      minWidth: 120
    },
    {
      key: 'description',
      label: 'Beschreibung',
      type: 'text',
      sortable: false,
      filterable: true,
      filterType: 'text',
      width: 200,
      minWidth: 150
    },
    {
      key: 'is_personal',
      label: 'Typ',
      type: 'boolean',
      sortable: true,
      filterable: true,
      filterType: 'select',
      filterOptions: [
        { value: false, text: 'Unternehmen' },
        { value: true, text: 'Persönlich' }
      ],
      width: 120,
      format: (value: boolean) => value ? 'Persönlich' : 'Unternehmen'
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
      key: 'users_count',
      label: 'Benutzer',
      type: 'number',
      sortable: true,
      filterable: false,
      width: 100,
      align: 'center'
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