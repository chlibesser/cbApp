<?php

/**
 * German translations for permission management
 * Used in admin interface for permission CRUD operations
 * Namespace: admin.permissions
 */
return [
    'components' => [
        'rsd' => [
            'view' => [
                'title' => 'Permission-Details',
                'name' => 'Name',
                'description' => 'Beschreibung',
                'created' => 'Erstellt am',
                'updated' => 'Aktualisiert am',
            ],
            'create' => [
                'title' => 'Neue Permission erstellen',
            ],
            'edit' => [
                'title' => 'Permission bearbeiten',
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
                'not_implemented' => 'Permission-Funktionalität wird noch implementiert',
                'save_error' => 'Fehler beim Speichern der Permission',
            ],
        ],
    ],
];
