<?php

/**
 * German translations for role management
 * Used in admin interface for role CRUD operations
 * Namespace: admin.roles
 */
return [
    'components' => [
        'rsd' => [
            'view' => [
                'title' => 'Role-Details',
                'name' => 'Name',
                'description' => 'Beschreibung',
                'tenant' => 'Tenant',
                'created' => 'Erstellt am',
                'updated' => 'Aktualisiert am',
            ],
            'create' => [
                'title' => 'Neue Role erstellen',
            ],
            'edit' => [
                'title' => 'Role bearbeiten',
            ],
            'fields' => [
                'name' => 'Name',
                'description' => 'Beschreibung',
            ],
            'buttons' => [
                'create' => 'Erstellen',
                'save' => 'Speichern',
                'cancel' => 'Abbrechen',
            ],
            'messages' => [
                'not_implemented' => 'Role-Funktionalität wird noch implementiert',
                'save_error' => 'Fehler beim Speichern der Role',
            ],
        ],
    ],
];
